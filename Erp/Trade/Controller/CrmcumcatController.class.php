<?php
namespace Trade\Controller;
use Trade\Controller\GlobalController;

use Photonic\ContractHelper;
use Photonic\File;
use Photonic\GoogleStorage;
use Photonic\Common;

class CrmcumcatController extends GlobalController{
  function _initialize($get_or_pay=0){
    parent::_initialize();
    parent::check_has_access(CONTROLLER_NAME, 'red');

    $powercat_id = 117;
    $powercat = D('powercat')->find($powercat_id);
    $this->powercat_current = $powercat;
    $this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

    $this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
    $this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
    $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
    $this->assign('get_or_pay', $this->get_or_pay);
  }

  /*合約類別編輯 畫面*/
  public function index(){
    $crm_cum_cat = M("crm_cum_cat")->where($this->get_or_pay_where." AND status=1")->order("id")->select();
    //dump($crm_crm_cat);
    $this->assign("crm_cum_cat",$crm_cum_cat);
    $this->display('Crmcumcat/index');
  }
  /*合約類別垃圾桶 畫面*/
  public function trash(){
    parent::check_has_access(CONTROLLER_NAME, 'hid');

    $this->assign('page_title', $this->powercat_current['title'].'>垃圾桶');
    $this->assign('page_title_link_self', u('Crmcumcat/trash'));

    $crm_cum_cat = M("crm_cum_cat")->where($this->get_or_pay_where)->where($this->get_or_pay_where." AND status=0")->order("id")->select();
    $this->assign("crm_cum_cat",$crm_cum_cat);
    $this->display();
  }

  /*取得合約類別預設內容(含合約內容、簽名、問題)*/
  public function aj_cate_content(){
    if(!$_POST['cat_id']){$this->error('未提供'.self::$system_parameter['合約'].'類型id');}

    $crm_cum_cat = D("crm_cum_cat")->where($this->get_or_pay_where." AND id=".$_POST['cat_id'])->find();

    $File = new File();
    $crm_cum_cat['imgs'] = $crm_cum_cat['imgs'] ? json_decode($crm_cum_cat['imgs']) : [];
    $crm_cum_cat['imgs_show'] = $File->try_get_files($crm_cum_cat['imgs']);
    $crm_cum_cat['signatures'] = $crm_cum_cat['signatures'] ? json_decode($crm_cum_cat['signatures']) : [];
    $crm_cum_cat['questions'] = $crm_cum_cat['questions'] ? json_decode($crm_cum_cat['questions']) : [];

    $crm_cum_cat['types_need_limit'] = ContractHelper::$types_need_limit;
    $crm_cum_cat['types_need_option'] = ContractHelper::$types_need_option;

    $this->ajaxReturn($crm_cum_cat);
  }
  /*編輯合約類別預設內容(含合約內容、簽名、問題)*/
  public function update_cate(){
    parent::check_has_access(CONTROLLER_NAME, 'edi');

    if(!$_POST['cat_id']){$this->error('未提供'.self::$system_parameter['合約'].'類型id');}
    $cat_id = $_POST['cat_id'];
    unset($_POST['cat_id']);

    if(!$_POST['name']){ $this->error('請填寫名稱'); }

    if(isset($_POST['content'])){
      $_POST['content'] = save_img_in_content($_POST['content']); //將內容內的base64圖片上傳到主機
    }else{
      $_POST['content'] = "";
    }

    /*處理圖片*/
    $GoogleStorage = new GoogleStorage();
    if(isset($_POST['imgs'])){
      foreach($_POST['imgs'] as $key=>$img){
        if($img_src_start = strpos($img, 'data:') !== false){
          // $_POST['imgs'][$key] = base64_image_content($img, 'Uploads/Alllist'); // 存在本主機
          $file_name = Common::geraHash(16);
          $file_path = 'Crmcumcat/'.$cat_id;
          $_POST['imgs'][$key] = $GoogleStorage->upload_base64($img, $file_name, $file_path); // 存在google雲
        }
      }
      $_POST['imgs'] = json_encode($_POST['imgs'], JSON_UNESCAPED_UNICODE);
    }else{
      $_POST['imgs'] = json_encode([], JSON_UNESCAPED_UNICODE);
    }

    /*處理簽名*/
    if(isset($_POST['signatures'])){
      $_POST['signatures'] = json_encode($_POST['signatures'], JSON_UNESCAPED_UNICODE);
    }else{
      $_POST['signatures'] = json_encode([], JSON_UNESCAPED_UNICODE);
    }

    /*處理問題*/
    if(isset($_POST['questions'])){
      foreach($_POST['questions'] as $key=>$question){
        $_POST['questions'][$key]['ans'] = ContractHelper::set_examinee_default_ans($question['type']);
      }
      $_POST['questions'] = json_encode($_POST['questions'], JSON_UNESCAPED_UNICODE);
    }else{
      $_POST['questions'] = json_encode([], JSON_UNESCAPED_UNICODE);
    }

    parent::error_log('修改資料:crm_cum_cat, ID:'.$cat_id);
    D("crm_cum_cat")->where($this->get_or_pay_where." AND id=".$cat_id)->save($_POST);
    $this->success('更新成功');
  }

  /*合約類別新增*/
  public function add_cat(){
    parent::check_has_access(CONTROLLER_NAME, 'new');

    if(!preg_match("/^([A-Z]+)$/",$_POST['sn_num'])){
      $this->error("sn編號只能是英文");
    }
    if(!$_POST['name'])
      $this->error("種類不得為空");
    if(!$_POST['sn_num'])
      $this->error("sn編號不得為空");
    if(M('crm_cum_cat')->where($this->get_or_pay_where." AND name = '".$_POST['name']."'")->limit(1)->select())
      $this->error("種類不得重複");
    if(M('crm_cum_cat')->where($this->get_or_pay_where." AND sn_num = '".$_POST['sn_num']."' and status=1")->limit(1)->select())
      $this->error("sn編號不得重複");
    $crm_cum_cat = M('crm_cum_cat')->where($this->get_or_pay_where)->order('sort asc')->select();
    $data = [
      'get_or_pay' => $this->get_or_pay,
      'sn_num' => $_POST['sn_num'],
      'name' => $_POST['name'],
      'cid' => '1',
      'type' => '1',
      'status' => '1',
      'sort' => count($crm_cum_cat)+1,
    ];
    if(M('crm_cum_cat')->data($data)->add()){
      parent::error_log('新增資料:crm_cum_cat, 資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
      $this->success("新增成功");
    }else{
      $this->error("新增失敗");
    }
  }
  /*合約類別移到垃圾桶*/
  public function aj_sn_ck(){
    parent::check_has_access(CONTROLLER_NAME, 'hid');

    $str=explode(',',$_POST['str']);
    $ajax='';
    $ajax_ck=0;
    foreach($str as $key => $vo){
      M("crm_cum_cat")->where($this->get_or_pay_where." AND sn_num = '".$vo."'")->data(['status'=>0])->save();
    }

    parent::error_log('刪除資料:crm_cum_cat, 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
    $this->success('刪除成功');
  }
  /*合約類別還原、刪除*/
  public function sn_action(){
    parent::check_has_access(CONTROLLER_NAME, 'hid');

    parent::error_log('修改資料:crm_cum_cat, 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
    $data['status'] = 1;
    try{
      foreach($_POST['sn_num'] as $key => $vo){
        M("crm_cum_cat")->where($this->get_or_pay_where." AND sn_num = '".$vo."'")->data($data)->save();
      }
      $this->success("還原完畢");
    }
    catch(Exception $e){
      $this->error("還原失敗");
    }
  }
}
?>