<?php
namespace Customer\Controller;
use Customer\Controller\GlobalController;

use Photonic\ContractHelper;
use Photonic\File;
use Photonic\GoogleStorage;
use Photonic\Common;

class ContractController extends GlobalController 
{
  public $field = 'c.name as c_name, md5(CONCAT(cc.sn,"_",cc.id)) as id, 
                   cc.id as cc_id, cc.invoice, sn, flag, content, allmoney, imgs, signatures, questions';

  function _initialize(){
    parent::_initialize();

    if(self::$control_sign_in!=1){
      $this->error('無此頁面');
    }
  }
  
  //簽約畫面
  function sign_in($id=""){
    if($id==""){
      $this->error('無此'.self::$system_parameter['合約']);
    }
    $this->assign('id', $id);

    $this->display();
  }

  public function aj_contract_content(){
    if(!$_POST['id']){$this->error('無此'.self::$system_parameter['合約']);}

    $contract = D("crm_contract cc")
          ->field($this->field)
          ->join('crm_crm c on c.id = cc.cid')
          ->where("md5(CONCAT(cc.sn,'_',cc.id))='{$_POST['id']}'")->find();
    if(!$contract){
      $this->error('無此'.self::$system_parameter['合約']);
    }
    // dump($contract);

    $File = new File();
    $contract['imgs'] = $contract['imgs'] ? json_decode($contract['imgs']) : [];
    $contract['imgs'] = $File->try_get_files($contract['imgs']);
    $contract['signatures'] = $contract['signatures'] ? json_decode($contract['signatures']) : [];
    foreach ($contract['signatures'] as $key => $value) {
      if(isset($value->sign)){
        $contract['signatures'][$key]->sign = $File->try_get_file($value->sign);
      }
    }
    $contract['questions'] = $contract['questions'] ? json_decode($contract['questions']) : [];
    foreach ($contract['questions'] as $key => $value) {
      if(!isset($contract['questions'][$key]->ans)){
        $contract['questions'][$key]->ans = ContractHelper::set_examinee_default_ans($value->type);
      }
    }

    $contract['types_file'] = ContractHelper::$types_file;
    $contract['types_need_limit'] = ContractHelper::$types_need_limit;
    $contract['types_need_option'] = ContractHelper::$types_need_option;

    $this->ajaxReturn($contract);
  }

  /*檢查資料有無正常填寫*/
  public function check_update(){
    $post = $_POST;

    $id = isset($post['id']) ? $post['id'] : "";
    $contract = D("crm_contract cc")->field($this->field)
                                    ->join('crm_crm c on c.id = cc.cid')
                                    ->where("md5(CONCAT(cc.sn,'_',cc.id))='".$id."'")->find();
    if(!$contract){ $this->error('無此合約'); }
    $questions_ori = json_decode($contract['questions'], true);

    $submit_type = $post['submit_type'] ?? "send";
    if($submit_type=='setting' && !session('adminId')){
      $this->error('您無法進行此操作');
    }

    /*檢查簽名*/
    if($submit_type=="send"){ /*消費者簽約*/
      $signatures = $post['signatures'];
      $unsign_count = 0;
      foreach ($signatures as $key => $value) {
        if(!isset($value['sign'])){ $unsign_count += 1; continue; }
        if(!$value['sign'] && $value['required']??''){ $unsign_count += 1; }
      }
      if($unsign_count>0){
        $this->error('還有'.$unsign_count.'處未簽名');
      }
    }

    /*檢查問題*/
    $questions = $post['questions'];
    // $this->ajaxReturn($questions);
    foreach ($questions as $key => $value) {
      if(isset($value['ans'])){ /*有輸入的話，依使用者輸入*/
        if(in_array($value['type'], ContractHelper::$types_file)){ /*上傳檔案*/
          $value['ans'] = (object)$value['ans'];
          unset($value['ans']->blob_link);
        }
        $ans = $value['ans'];
      }
      else{ /*沒輸入的話，依預設*/
        $ans = ContractHelper::set_examinee_default_ans($value['type']);
      }

      /*檢查必填*/
      if(in_array($value['type'], ContractHelper::$types_file) && $value['required']==1 && ($ans->file_name=="" || $ans->data=="")){
        if(($submit_type=="setting" && $value['staff_only']==1) || $submit_type=="send"){ /*員工設定合約且員工填寫 或 消費者簽約*/
          $this->error("請輸入必填欄位：".$value['title']);
        }
      }
      if($value['required']==1 && ($ans=="" || $ans==[])){
        if(($submit_type=="setting" && $value['staff_only']==1) || $submit_type=="send"){ /*員工設定合約且員工填寫 或 消費者簽約*/
          $this->error("請輸入必填欄位：".$value['title']);
        }
      }

      /*檢查格式*/
      if( in_array($value['type'], ContractHelper::$types_need_checked) ){ /*選項類型*/
        if($ans){
          if(is_array($ans)){
            foreach ($ans as $ans_v) {
              if( !in_array($ans_v, $value['options']) ){
                $this->error("格式有誤：".$value['title']);
              }
            }
          }else{
            if( !in_array($ans, $value['options']) ){
              $this->error("格式有誤：".$value['title']);
            }
          }
        }
      }
      else if( in_array($value['type'], ['file']) ){ /*檔案類型*/
        if($value['limit'] && $ans->data){
          $file_type = explode('.', $ans->file_name);
          $file_type = end($file_type);
          if( !preg_match("/$file_type/", $value['limit']) ){
              $this->error("格式有誤：".$value['title']);
          }
        }
      }
      else{ /*文字類型*/
        if($value['limit'] && $ans){
          $pattern = $value['limit'];
          if( !preg_match("/$pattern/", $ans) ){
            $this->error("格式有誤：".$value['title']);
          }
        }
      }

      if($submit_type=="send"){ /*消費者簽約*/
        /*檢查是否有更動員工欄位*/
        if($value['staff_only']==1){
          if(json_encode($ans)!=json_encode($questions_ori[$key]['ans'])){
            $this->error("您無法更改此欄位：".$value['title']);
          }
        }
      }

      $questions_ori[$key]['ans'] = $ans;
    }
    return $questions_ori;
  }
  /*更新合約*/
  public function update(){
    $post = $_POST;
    /*檢查修改內容*/
    $questions = $this->check_update();

    $id = isset($post['id']) ? $post['id'] : "";
    $contract = D("crm_contract cc")->field($this->field)
                                    ->join('crm_crm c on c.id = cc.cid')
                                    ->where("md5(CONCAT(cc.sn,'_',cc.id))='".$id."'")->find();
    if(!$contract){ $this->error('無此合約'); }
    if($contract['flag']){ $this->error('此合約已確認，不可再修改'); }

    /*處理簽名*/
    $GoogleStorage = new GoogleStorage();
    $signatures_update = json_decode($contract['signatures']);
    $signatures = $post['signatures'];
    foreach ($signatures as $k => $v) {
      // dump($v['sign']);
      if(isset($v['sign'])){
        if($v['sign'] && substr($v['sign'], 0, 4)=='data'){
          // $signatures_update[$k]->sign = base64_image_content($v['sign'], 'Uploads/Customer'); // 存在本主機
          $file_name = Common::geraHash(16);
          $file_path = 'Alllist/signatures/'.$contract['cc_id'];
          $signatures_update[$k]->sign = $GoogleStorage->upload_base64($v['sign'], $file_name, $file_path); // 存在google雲
        } 
      }
    }

    /*處理問題*/
    foreach ($questions as $key => $value) {
      if(in_array($value['type'], ContractHelper::$types_file)){ /*上傳檔案*/
        $value['ans'] = (object)$value['ans'];
        $image_base64 = $value['ans']->data;
        if($image_base64 && substr($image_base64, 0, 4)=='data'){
          $questions[$key]['ans']->data = base64_image_content($image_base64, 'Uploads/Customer');
        }
      }
    }

    $submit_type = $post['submit_type'] ?? "send";
    $data = [
      'signatures' => json_encode($signatures_update, JSON_UNESCAPED_UNICODE),
      'questions' => json_encode($questions, JSON_UNESCAPED_UNICODE),
      'content' => $post['content'],
    ];
    if($submit_type=="send"){ /*消費者簽約*/
      $data['flag'] = 1;
    }
    // $this->ajaxReturn($data);
    $result = D("crm_contract")->where("md5(CONCAT(sn,'_',id))='".$id."'")->data($data)->save();

    if($result){
      $this->success('更新成功');
    }else{
      $this->error('無資料須修改');
    }
  }
  public function get_base64_data(){
    $url = $_POST['url'];
    try {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $imageData = curl_exec($ch);
      curl_close($ch);
      
      // 將圖片數據轉換成 base64 字符串
      $base64Image = base64_encode($imageData);
      $src = 'data:image/jpeg;base64, '.$base64Image;
    } catch (\Exception $e) {
        $this->error('');
    }
    $this->success($src);
  }
}
?>