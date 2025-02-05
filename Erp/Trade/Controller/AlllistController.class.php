<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use Photonic\CustoHelper;
  use Photonic\MoneyHelper;
  use Photonic\ProductHelper;
  use Photonic\ContractHelper;
  use Photonic\GoogleStorage;

  class AlllistController extends GlobalController 
  {
    function _initialize($get_or_pay=0){
      parent::_initialize();
      parent::index_set('crm_cum_pri','id=1');

      $powercat_id = 57;
      $powercat = D('powercat')->find($powercat_id);
      $this->powercat_current = $powercat;
      $this->assign('page_title_active', $powercat_id);       /*右上子選單active*/
      
      $this->get_or_pay = $get_or_pay;                        /*收付款判斷 0.收款 1.付款*/
      $this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
      $this->assign('get_or_pay', $this->get_or_pay);
      $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
      $this->assign('CONTROLLER_NAME_Conlist', $this->get_or_pay==0 ? 'Conlist' : 'Conlistpay');

      $get_pay_words = ContractHelper::get_pay_words($this->get_or_pay);
      foreach ($get_pay_words as $key => $value) {
        $this->$key = $value;
        $this->assign($key, $value);
      }
    }
    
    //合約管理
    public function index(){
      // dump($_GET);
      $params = $_GET;
      $params['flag'] = $params['flag'] ?? '-1';
      $params['flag2'] = $params['flag2'] ?? '1';
      $this->assign("flag_flag2", $params['flag'].'_'.$params['flag2']);

      // 取得合約篩選sql語法
      $result = ContractHelper::get_contract_where_sql($params, $this->get_or_pay);
      // dump($result);exit;
      /*等級選單*/
      $this->assign('levels', $result['levels']);
      /*通用產業選單*/
      $this->assign('industr', $result['industr_all']);
      $this->assign('industr2_search', $result['industr2_search']);
      
      $this->assign("cdate", $params['cdate']);
      $this->assign("contract_text", $params['contract_text']);

      $count = D('crm_contract')->join("left join crm_crm on crm_crm.id=crm_contract.cid")->where($result['where_query'])->count();
      $Page = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
      $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
      $Page->setConfig('prev',"上一頁");
      $Page->setConfig('next',"下一頁");
      $Page->setConfig('first',"第一頁");
      $Page->setConfig('last',"最後一頁 %END% ");
      $show = $Page->show();// 分页显示输出
      $this->assign("show", $show);
      
      $order="cdate desc";
      foreach($params as $key =>$vo){
        if($key=="order"){//排序
          $order = $vo." desc, ". $order;
        }
      }

      //分頁總計資料
      $crm_contract=D('crm_contract')->field("*,crm_contract.id as pid, crm_crm.id as crm_id")
                                     ->join("left join crm_crm on crm_crm.id=crm_contract.cid")
                                     ->where($result['where_query'])
                                     ->order($order)
                                     ->limit($Page->firstRow.','.$Page->listRows)->select();
      [
        $crm_contract, $tallmoney, $tallmoney_pretax, $tallmoney_aftertax, $tmoney
      ] = self::get_contract_index_data($crm_contract, true);
      // dump($crm_contract);exit;
      $this->assign("crm_contract", $crm_contract);
      $this->assign("tallmoney", $tallmoney);
			$this->assign("tallmoney_pretax", $tallmoney_pretax);
			$this->assign("tallmoney_aftertax", $tallmoney_aftertax);
			$this->assign("tmoney", $tmoney);

      //搜尋總計資料
      $crm_contract_search=D('crm_contract')->field("*,crm_contract.id as pid, crm_crm.id as crm_id")
                                     ->join("left join crm_crm on crm_crm.id=crm_contract.cid")
                                     ->where($result['where_query'])
                                     ->select();
      [
        $crm_contract_search, $tallmoney_all, $tallmoney_pretax_all, $tallmoney_aftertax_all, $tmoney_all
      ] = self::get_contract_index_data($crm_contract_search, false);
      $this->assign("tallmoney_all", $tallmoney_all);
			$this->assign("tallmoney_pretax_all", $tallmoney_pretax_all);
			$this->assign("tallmoney_aftertax_all", $tallmoney_aftertax_all);
			$this->assign("tmoney_all", $tmoney_all);

      //其他顯示用資料
      $crm_cum_type=D('crm_cum_type')->where("status=1")->order('sort asc, id asc')->select();
      $this->assign("crm_cum_type", $crm_cum_type);

      $crm_cum_level=D('crm_cum_level')->select();
      $this->assign("crm_cum_level", $crm_cum_level);

      $crm_cum_cat=ContractHelper::get_crm_cum_cat($this->get_or_pay);
      $this->assign("crm_cum_cat", $crm_cum_cat);

      $crm_cum_menu=D('crm_cum_menu')->select();
      $this->assign("crm_cum_menu", $crm_cum_menu);

      $crm_cum_flag=D('crm_cum_flag')->where('name != "款收罄" and status=1')->order('id')->select();
      $this->assign("crm_cum_flag", $crm_cum_flag);
      
      $crm_cum_flag2=D('crm_cum_flag2')->where('status=1')->order('id')->select();
      $this->assign("crm_cum_flag2", $crm_cum_flag2);

      parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版
      parent::index_set('eip_user',"is_job=1 and id !=". self::$top_adminid, '', false);

      $this->assign("page", ($params['p'] ?? 1));

      $this->display('Alllist/index');
    }
    private function get_contract_index_data($crm_contract, $need_crm_data=true){
      $count_allmoney=0;
			$count_allmoney_pretax=0;
			$count_allmoney_aftertax=0;
			$count_money=0;
      foreach ($crm_contract as $key => $value) {
        if($need_crm_data){
          $crm_contract[$key]['show_name'] = CustoHelper::get_crm_show_name($value['crm_id']);
        }
        $pre_aftertax_money = ContractHelper::get_pre_aftertax_money($value['invoice'], $value['allmoney']);
        $crm_contract[$key]['allmoney_pretax'] = $pre_aftertax_money['money_pretax'];
        $crm_contract[$key]['allmoney_aftertax'] = $pre_aftertax_money['money_aftertax'];

        //分頁總計
        $count_allmoney += $value['allmoney'];
				$count_allmoney_pretax += $crm_contract[$key]['allmoney_pretax'];
				$count_allmoney_aftertax += $crm_contract[$key]['allmoney_aftertax'];
				$count_money += $value['money'];
      }
      // dump($crm_contract);exit;
      return [$crm_contract, $count_allmoney, $count_allmoney_pretax, $count_allmoney_aftertax, $count_money];
    }

    //客户管理
    function view(){
      $this->assign('page_title_link', u(CONTROLLER_NAME.'/index'));

      $pay=array("其他","現金","匯款","支票");
      $id=$_GET['id'];
      $where="crm_contract.id='".$id."'";
      $crm_contract=D('crm_contract')
              ->field("*, crm_contract.id as pid, crm_crm.id as crm_id")
              ->where($where)
              ->join("left join crm_crm on crm_crm.id=crm_contract.cid")
              ->order($order)->limit($Page->firstRow.','.$Page->listRows)->find();
      if(!$crm_contract){ $this->error('無此合約'); }
      $crm_contract['show_name'] = CustoHelper::get_crm_show_name($crm_contract['crm_id']);
      $crm_contract['paymenttype']=$pay[$crm_contract['paymenttype']];
      if($crm_contract['count_type']==='1'){
        $crm_contract['count_type'] = '外單';
      }else if($crm_contract['count_type']==='0'){
        $crm_contract['count_type'] = '內單';
      }
      
      switch($crm_contract['cate']){
        case 1:
          $data=D("crm_contract_seo")->where("pid={$id}")->select()[0];
          $seolist=D("crm_seo_key")->where("contract_id={$id}")->select();
          break;

        case 2:
          $data=D("crm_contract_seo")->where("pid={$id}")->select()[0];
          break;

        case 3:
          $data=D("crm_contract_host")->where("pid={$id}")->select()[0];
          break;

        default:
          break;
      }
      //dump($crm_contract);
      //dump($data);
      //dump($seolist);
      $crm_cum_type=D('crm_cum_type')->where("status=1")->order('sort asc, id asc')->select();
      $this->assign("crm_cum_type", $crm_cum_type);

      $crm_cum_level=D('crm_cum_level')->select();
      $this->assign("crm_cum_level",$crm_cum_level);

      $crm_cum_menu=D('crm_cum_menu')->select();
      $this->assign("crm_cum_menu",$crm_cum_menu);

      $crm_cum_flag=D('crm_cum_flag')->select();
      $this->assign("crm_cum_flag",$crm_cum_flag);

      parent::index_set('eip_user',"is_job=1 and id !=". self::$top_adminid);
      parent::index_set('eip_apart');
      $this->assign("page",$_GET['p']);
      $this->assign("pay",$pay);
      $this->assign("seolist",$seolist);
      $this->assign("mdate",$mdate);
      $this->assign("one",$crm_contract);
      $this->assign("data",$data);
      $this->assign("show",$show);

      $type= D('crm_cum_cat')->where('id = "'.$crm_contract['cate'].'"')->select()[0]['name'];
      $this->assign("type",$type);

      parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版

      /*讀取合約檔案*/
      $upload_files = $this->get_file($id);
      $this->assign('upload_files', $upload_files);

      /*讀取對應的付款合約*/
      $contracts_pay_sum = 0;
      if($crm_contract['get_or_pay']==0){
        $contracts_pay = ContractHelper::get_contracts([
          'pay_to' => $crm_contract['pid']
        ], 1, false);
        foreach ($contracts_pay as $value) {
          $contracts_pay_sum += $value['allmoney'];
        }
      }else{
        $contracts_pay = [];
      }
      $this->assign('contracts_pay', $contracts_pay);
      $this->assign('contracts_pay_sum', $contracts_pay_sum);

      /*讀取對應的附約合約*/
      $contracts_belongs_sum = 0;
      $contracts_belongs = ContractHelper::get_contracts([
        'belongs_to' => $crm_contract['pid']
      ], $this->get_or_pay, false);
      foreach ($contracts_belongs as $value) {
        $contracts_belongs_sum += $value['allmoney'];
      }
      $this->assign('contracts_belongs', $contracts_belongs);
      $this->assign('contracts_belongs_sum', $contracts_belongs_sum);

      /*讀取對應的收款合約*/
      $contracts_pay_to = ContractHelper::get_contracts([
        'crm_contract_id' => $crm_contract['pay_to']
      ], 0, false);
      $contracts_pay_to = $contracts_pay_to ? $contracts_pay_to[0] : null;
      $this->assign('contracts_pay_to', $contracts_pay_to);

      /*讀取對應的主合約*/
      $contracts_belongs_to = ContractHelper::get_contracts([
        'crm_contract_id' => $crm_contract['belongs_to']
      ], $this->get_or_pay, false);
      $contracts_belongs_to = $contracts_belongs_to ? $contracts_belongs_to[0] : null;
      $this->assign('contracts_belongs_to', $contracts_belongs_to);

      $this->display('Alllist/view');
    }

    function get_crm_contract_user_selected(){
      $post_data = $_POST;
      $post_data['field'] = 'eip_user.id, eip_user.no, eip_user.name, eip_user.ename';
      $caseid = $post_data['caseid'] ?? 0;

      $data = [];
      /*取得此合約中，符合篩選條件的已選擇員工名單*/
      $data['users'] = ContractHelper::get_crm_contract_user($caseid, false, $post_data);
      $this->ajaxReturn($data);
    }
    function get_crm_contract_user_not_selected(){
      $post_data = $_POST;
      $post_data['field'] = 'eip_user.id, eip_user.no, eip_user.name, eip_user.ename';
      $caseid = $post_data['caseid'] ?? 0;

      $data = [];
      /*取得此合約中，符合篩選條件的未選擇員工名單*/
      $data['users'] = ContractHelper::get_crm_contract_user($caseid, true, $post_data);
      $this->ajaxReturn($data);
    }
    function contract_select_user(){
      $caseid = I('post.caseid');
      $user_ids = I('post.user_ids');
      $this->check_alllist_access(CONTROLLER_NAME, 'edi', [$caseid], 0, 'crm_contract');

      $result = ContractHelper::contract_select_user($caseid, $user_ids);
      if($result){
        $this->success('操作成功');
      }else{
        $this->error('資料有誤或重複');
      }
    }
    function contract_delete_user(){
      $caseid = I('post.caseid');
      $user_ids = I('post.user_ids');
      $this->check_alllist_access(CONTROLLER_NAME, 'edi', [$caseid], 0, 'crm_contract');

      $result = ContractHelper::contract_delete_user($caseid, $user_ids);
      if($result){
        $this->success('操作成功');
      }else{
        $this->error('操作失敗');
      }
    }

    public function get_file($case_id){/*讀取google storage檔案*/
      $GoogleStorage = new GoogleStorage();
      $file_path = $this->google_file_path($case_id);
      $files = $GoogleStorage->show_files($file_path);
      return $files;
    }
    public function upload_file(){/*上傳google storage檔案*/
      /*權限判斷*/
      $case_id = $_GET['pid'] ?? ''; 
      $this->check_alllist_access(CONTROLLER_NAME, 'new', [$case_id], 0, 'crm_contract');

      $GoogleStorage = new GoogleStorage();
      $file_path = $this->google_file_path($case_id);
      $GoogleStorage->upload('google_file', $file_path);
      $this->success('操作成功');
    }
    public function download_file(){/*下載google storage檔案*/
      $file_path = $_GET['file_path'] ?? '';
      /*權限判斷*/
      $case_id = $_GET['pid'] ?? ''; 
      $this->check_alllist_access(CONTROLLER_NAME, 'red', [$case_id], 0, 'crm_contract');

      $GoogleStorage = new GoogleStorage();
      $GoogleStorage->download($file_path);
    }
    public function delete_file(){/*刪除google storage檔案*/
        $file_path = $_GET['file_path'] ?? '';
        /*權限判斷*/
        $case_id = $_GET['pid'] ?? ''; 
        $this->check_alllist_access(CONTROLLER_NAME, 'del', [$case_id], 0, 'crm_contract');

        $GoogleStorage = new GoogleStorage();
        $GoogleStorage->delete($file_path);
    }
    public function google_file_path($case_id){ /*回傳google storage檔案路徑*/
      $table = 'crm_contract';
      return $table .'/'.$case_id;
    }

    //新增頁面
    public function add() {
      $this->assign('page_title_link', u(CONTROLLER_NAME.'/index'));

      $crm_contract=D('crm_contract')->where("id='".($_GET['pid']??0)."'")->find();
      $this->assign("crm_contract", $crm_contract);

      $crm_crm=D('crm_crm')->where("id='".($_GET['id']??0)."'")->find();
      $crm_crm['show_name'] = CustoHelper::get_crm_show_name($crm_crm['id']);
      $this->assign("crm_crm",$crm_crm);

      parent::index_set('eip_user', "is_job=1 and id !=". self::$top_adminid);

      $crm_cum_cat=ContractHelper::get_crm_cum_cat($this->get_or_pay);
      $this->assign("crm_cum_cat",$crm_cum_cat);
      if(count($crm_cum_cat)==0){
        $redirect_url = $this->get_or_pay==0 ? U('Crmcumcat/index') : U('Crmcumcatpay/index');
        $this->error('請先設定合約類別', $redirect_url);
      }

      parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版

      $this->assign('search_status', ['status'=>1, 'get_or_pay'=>$this->get_or_pay]);

      $this->display('Alllist/add');
    }
    function aj_add() {
      $contracttime_option = D('system_parameter')->where('id=2')->find()['data'];
      $contracttime_option = json_decode($contracttime_option, true);
      $this->assign('contracttime_option', $contracttime_option);

      $contract_id = $_GET['pid'] ?? 0;
      /*預設參數(新增)*/
      $crm_contract = null;
      $get_or_pay = $this->get_or_pay;
      $belongs_to = $_GET['belongs_to'] ?? 0;
      $pay_to = $_GET['pay_to'] ?? 0;
      $cate = $_GET['cate'] ?? 0;
      if($contract_id){ /*編輯合約*/
        $crm_contract=D("crm_contract")->where("`id`=".$contract_id)->find();
        $get_or_pay = $crm_contract['get_or_pay'];
        $belongs_to = $crm_contract['belongs_to'];
        $pay_to = $crm_contract['pay_to'];
        $cate = $crm_contract['cate'];
        switch($crm_contract['cate']){
          case 1:
            $crm_contract_seo=D("crm_contract_seo")->where("`pid`=".$contract_id)->select()[0];
            $this->assign("content",$crm_contract_seo);

          case 2:
            $crm_contract_seo=D("crm_contract_seo")->where("`pid`=".$contract_id)->select()[0];
            $this->assign("content",$crm_contract_seo);
            break;

          case 3:
            $crm_contract_host=D("crm_contract_host")->where("`pid`=".$contract_id)->select()[0];
            $this->assign("content",$crm_contract_host);
            break;

          default:
            break;
        }
      }
      $this->assign("crm_contract",$crm_contract);
      $this->assign("get_or_pay",$get_or_pay);
      $this->assign("belongs_to",$belongs_to);
      $this->assign("pay_to",$pay_to);
      $this->assign("cate",$cate);

      $crm_cum_cat = M("crm_cum_cat")->where("id='".$cate."'")->find();
      $this->assign("crm_cum_cat",$crm_cum_cat);

      $this->display('Alllist/aj_add');
    }
    // 取得合約執行項目
    public function get_crm_contract_unit_ajax($pid=0){
      return $this->ajaxReturn(ContractHelper::get_crm_contract_unit($pid, $_POST));
    }
    // 取得工種
    public function get_user_skill($pid=0){
      $user_skill = ContractHelper::get_user_skills($pid);
      $this->ajaxReturn($user_skill);
    }
  
    //Api:新增&修改合約
    public function do_addcontract(){
      // dump($_POST);exit;
      $contract_id = $_POST['pid'] ?? 0;
      $units = $_POST['units'] ?? []; unset($_POST['units']);
      $units_del = $_POST['units_del'] ?? []; unset($_POST['units_del']);
      $units3 = $_POST['units3'] ?? []; unset($_POST['units3']);

      $_POST['contype'] = $_POST['contype'] ?? '';
      
      $_POST['content'] = save_img_in_content($_POST['content']); //將內容內的base64圖片上傳到主機
      
      $_POST['exptime']=strtotime($_POST['exptime']);
      $_POST['date']=strtotime($_POST['date']);
      if(!$contract_id){ //新增
        $_POST['cdate']=time();
      }
      $_POST['cate1']=$_POST['type1']?$_POST['type1']:0;//主机(1)
      $_POST['cate2']=$_POST['type2']?$_POST['type2']:0;//域名(1)youngfac_eip
      $_POST['cate3']=$_POST['type3']?$_POST['type3']:0;//维护(3),非主机域名维护合约为0
      if(!isset($_POST['h_status']))$_POST['h_status']='0';
      if(!isset($_POST['d_status']))$_POST['d_status']='0';
      if(!isset($_POST['w_status']))$_POST['w_status']='0';
      
      if(!$_POST['sn']){ /*未定義合約編號，由系統生成*/
        if($_POST['belongs_to']==0){ /*設定的為主約*/
          $sn_new = ContractHelper::get_new_sn_number($_POST['cate']);
        }else{ /*設定的為副約*/
          /*撈取主約*/
          $main_contract = D("crm_contract")->where("id='".$_POST['belongs_to']."'")->find();
          if($main_contract){
            $main_contract_sn = $main_contract['sn'];
            $sub_contract = D("crm_contract")->where("belongs_to='".$_POST['belongs_to']."'")->order('id desc')->find();
            if($sub_contract){
              $num = explode('-', $sub_contract['sn']);
              $num = count($num)==2 ? (INT)$num[1]+1 : 1;
            }else{
              $num = 1;
            }
            $sn_new = $main_contract_sn.'-'.str_pad($num, 3,'0', STR_PAD_LEFT);
          }else{
            $_POST['belongs_to'] = 0;
            $sn_new = ContractHelper::get_new_sn_number($_POST['cate']);
          }
        }
        $_POST['sn'] = $sn_new;
      }
      // dump($_POST);die();
      if($contract_id){ // 編輯合約
        $this->check_alllist_access(CONTROLLER_NAME, 'edi', [$contract_id], 0, 'crm_contract');

        $repeat = D("crm_contract")->where('id!='.$contract_id.' AND sn ="'.$_POST['sn'].'"')->find();
        if($repeat){
          $this->error($this->system_parameter['合約'].'號重複');
        }
        $contract_result = D("crm_contract")->data($_POST)->where("id={$contract_id}")->save();
        switch($_POST['cate']){
          case 1:
            if(D("crm_contract_seo")->where("pid={$contract_id}")->count()==0){
              D("crm_contract_seo")->data($_POST)->add();
              }else{
              D("crm_contract_seo")->where("pid={$contract_id}")->data($_POST)->save();
              
            }
            break;
          
          case 2:
            if(D("crm_contract_seo")->where("pid={$contract_id}")->count()==0){
              D("crm_contract_seo")->data($_POST)->add();
              }else{
              D("crm_contract_seo")->where("pid={$contract_id}")->data($_POST)->save();
              
            }
            break;
          
          case 3:
            if(D("crm_contract_host")->where("pid={$contract_id}")->count()==0){
              D("crm_contract_host")->data($_POST)->add();
            }else{
              D("crm_contract_host")->where("pid={$contract_id}")->data($_POST)->save();
            }
            break;
          
          default:							
            break;
        }

        // 全部合約都要處理執行項目
        $this->save_crm_contract_unit($contract_id, $units, $units_del);
        $this->save_crm_contract_user_skill($contract_id, $units3);

        parent::error_log('更新合約:'.$contract_id.', 資料：'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
        $this->success("更新成功!!",u(CONTROLLER_NAME.'/view').'?id='.$contract_id);
      }
      else{ // 新增合約
        $this->check_alllist_access(CONTROLLER_NAME, 'new');
        // dump($_POST);exit;

        $crm_cum_cat = D('crm_cum_cat')->where('id="'.$_POST['cate'].'"')->find();
        if(!$crm_cum_cat){ $this->error('無此合約類型'); }
        $_POST['get_or_pay'] = $crm_cum_cat['get_or_pay'];

        $repeat = D("crm_contract")->where('sn ="'.$_POST['sn'].'"')->find();
        if($repeat){
          $this->error($this->system_parameter['合約'].'號重複');
        }
        $_POST['pid']=D("crm_contract")->data($_POST)->add();
        if($_POST['pid']){
          switch($_POST['cate']){
            case 1:
              D("crm_contract_seo")->data($_POST)->add();
              break;

            case 2:
              D("crm_contract_seo")->data($_POST)->add();
              break;

            case 3:
              D("crm_contract_host")->data($_POST)->add();
              break;
            
            //case 4:
            default:
              break;
          }

          // 全部合約都要處理執行項目
          $this->save_crm_contract_unit($_POST['pid'], $units, $units_del);
          $this->save_crm_contract_user_skill($_POST['pid'], $units3);

          // 套用簽名資料
          $sign_data = D("crm_cum_cat")->field('imgs, signatures, questions')->where("id={$_POST['cate']}")->find();
          // dump($sign_data);
          D("crm_contract")->data($sign_data)->where("id={$_POST['pid']}")->save();

          parent::error_log('新增合約, 資料：'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
          $this->success("新增成功!!",u(CONTROLLER_NAME.'/index'));
          
        }else{
          $this->error("新增失敗!!");
        }	
      }
    }
    /*修改合約套用的商品(僅透過do_addcontract呼叫)*/
    private function save_crm_contract_unit($contract_id, $units=[], $units_del=[]){
      foreach ($units as $key => $unit) {
        $unit['pid'] = $contract_id;
        $unit_id = isset($unit['id']) ? $unit['id'] : 0;
        $unit_id = $unit_id ? $unit_id : 0;
        unset($unit['id']);

        if($unit_id){ /*編輯*/
          D('crm_contract_unit')->data($unit)->where('id="'.$unit_id.'"')->save();
        }else{ /*新增*/
          D('crm_contract_unit')->data($unit)->add();
        }
      }

      foreach ($units_del as $key => $unit_id) {
        D('crm_contract_unit')->where('id="'.$unit_id.'"')->delete();
      }
    }
    /*修改合約套用的工種請款金額*/
    private function	save_crm_contract_user_skill($contract_id, $units=[]){
      foreach ($units as $unit) {
        $unit_id = $unit['crm_contract_user_skill_id'] ?? 0;
        unset($unit['crm_contract_user_skill_id']);
        $unit['pid'] = $contract_id;
        // dump($unit);exit;
        if($unit_id){ /*編輯*/
          D('crm_contract_user_skill')->data($unit)->where('id="'.$unit_id.'"')->save();
        }else{ /*新增*/
          D("crm_contract_user_skill")->data($unit)->add();
        }
      }
    }

    //Api:批次處理
    public function patchupdate(){
      $flag = $_POST['flag'];
      $flag2 = $_POST['flag2'];
      $flag3 = $_POST['flag3'];
      $ids = $_POST['flags'];

      $data = [];
      if($flag != ''){ $data['flag'] = $flag; }
      if($flag2 != ''){ $data['flag2'] = $flag2; }
      if($flag3 != ''){ $data['flag3'] = $flag3; }

      if($flag2!='10'){//不是刪除
        if($flag2=='3'){//移到垃圾桶
          $this->check_alllist_access(CONTROLLER_NAME, 'hid', $ids, 0, 'crm_contract');
        }else{
          $this->check_alllist_access(CONTROLLER_NAME, 'edi', $ids, 0, 'crm_contract');
        }

        foreach($ids as $vo){
          if($flag == '1'){ ContractHelper::set_sign_date($vo); } # 設定簽約日期
          // dump($data);exit;
          D('crm_contract')->where($this->get_or_pay_where.' AND id='.$vo)->data($data)->save();
        }
        parent::error_log('批次修改合約, 資料：'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
      }
      else{//刪除
        $this->check_alllist_access(CONTROLLER_NAME, 'del', $ids, 0, 'crm_contract');
        parent::error_log('批次刪除合約, 資料：'.json_encode($_POST, JSON_UNESCAPED_UNICODE));

        foreach($ids as $vo){
          D('crm_contract')->where($this->get_or_pay_where.' AND id='.$vo)->delete();
        }
      }
      $this->success('更新成功');	
    }
    
    //Api:改資料ajax
    public function aj_chcontent(){
      if(!isset($_POST['dbname']) || !isset($_POST['row'])){
        $this->error('修改失敗');
      }
      $this->check_alllist_access(CONTROLLER_NAME, 'edi', [$_POST['id']], 0, $_POST['dbname']);
      
      $data[$_POST['row']]=$_POST['data'];
      if($_POST['row']=="flag" && $_POST['data']=="1" ){ # 設定簽約日期
        ContractHelper::set_sign_date($_POST['id']);
      }
      D($_POST['dbname'])->data($data)->where($this->get_or_pay_where." AND id='{$_POST['id']}'")->save();
      
      parent::error_log("修改".$_POST['dbname']."欄位".$_POST['row']."資料列:{$_POST['id']}的資料{$_POST['data']}");
      $this->success('修改成功');
    }

    //新增seo字組頁面
    function addseokey(){
      $this->check_alllist_access(CONTROLLER_NAME, 'edi');

      $id=$_GET['id'];
      $crm_contract=D("crm_contract")->where("id=$id")->find();
      $s_seo_key=D("crm_seo_key")->where("caseid=$id")->select();
      //dump($s_seo_key);
      $j_seo_key=D("crm_seo_key")->where("caseid<>$id and customers_id={$crm_contract['cid']}")->select();
      //分類
      $gcs=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
      $color=["#1f497d","#c0504d", "#9bbb59", "#8064a2","#4bacc6","#f79646","#f79646"];
      $starts=array(1=>'1~10',11=>'11~30',12=>'1~20',2=>'1~30',3=>'1~3',4=>'1~5',5=>'4~10',6=>'6~10',7=>'4~5');
      $num=1;
      $j=0;
      foreach($gcs as $key=>$vo){
        for($i=1;$i<=3;$i++){
          $gcsele[$num++]=$vo.$i;
        }
      }
      if(count($j_seo_key)!=0){
        foreach($j_seo_key as $k=>$v){
          $j_seo_key[$k]['sn']=D("crm_contract")->where("id=".$v['contract_id'])->field("sn")->find()['sn'];	
        }
      }

      foreach($s_seo_key as $k=>$v){
        if($j_color[$v[someno]]=='' && $v[someno]!=null){
          $s_color[$v[someno]]=$color[$j++];
        }
      }
      $this->assign("starts",$starts);
      $this->assign("gcs",$gcsele);
      $this->assign("crm_contract",$crm_contract);
      $this->assign("s_seo_key",$s_seo_key);
      $this->assign("j_seo_key",$j_seo_key);
      $this->assign("s_color",$s_color);
      $this->display();		
    }
    //Api:新增字組
    function do_addseokey(){
      if($_POST['caseid']==''){
        $this->error("新增失敗");
      }
      $this->check_alllist_access(CONTROLLER_NAME, 'edi', [$_POST['caseid']], 0, 'crm_seo_key');

      $box=$_POST['box'];
      $data['customers_id']=$_POST['customers_id'];
      $data['contract_id']=$_POST['caseid'];
      $data['caseid']=$_POST['caseid'];
      foreach($box['url'] as $key=>$vo){
        if($vo!=""){
          $data['url1']=$vo;
          $data['key_name']=$box['key_name'][$key];
          $data['engine']=$box['engine'][$key];
          $data['starts']=$box['starts'][$key];
          $data['gcsele']=$box['gcs'][$key];
          $data['price']=$box['price'][$key];
          if($box['type'][$key]==1){//2017/04/10補充新增相對自組
            $data['someno']=time();
          }else{
            unset($data['someno']);
          }
          D("crm_seo_key")->data($data)->add();
        }
      }
      $this->success("新增成功");
    }
    //Api:刪除字組(單個)
    function aj_delseo(){
      if($_POST['id']==''){
        $this->error("刪除失敗");
      }
      $this->check_alllist_access(CONTROLLER_NAME, 'del', [$_POST['id']], 0, 'crm_seo_key');

      D("crm_seo_key")->where("id=".$_POST['id'])->delete();
      parent::error_log("刪除seo字組 crm_seo_key 資料列:".$_POST['id']);
      $this->success("刪除成功");
    }
    //Api:刪除字組(批次)
    function do_deleseo(){
      if(!$_POST['case']){
        $this->error("請選擇刪除對象");
      }
      $this->check_alllist_access(CONTROLLER_NAME, 'del', $_POST['case'], 0, 'crm_seo_key');

      foreach ($_POST['case'] as $key => $value) {
        D("crm_seo_key")->where("id=".$value)->delete();
      }
      $this->success('刪除成功');
    }
    //Api:移動seo字組到另一個合約
    function do_moveseo(){
      if($_POST['id']==''){
        $this->error("請提供id");
      }
      $this->check_alllist_access(CONTROLLER_NAME, 'edi', $_POST['case'], 0, 'crm_seo_key');

      $data['contract_id']=$_POST['id'];
      $data['caseid']=$_POST['id'];
      foreach ($_POST['case'] as $key => $value) {
        D("crm_seo_key")->where("id=".$value)->data($data)->save();
      }
      $this->success("加入成功");
    }	
    //Api:設定對比字組
    function do_someno(){
      if($_POST['id']==''){
        $this->error("請提供id");
      }
      //dump($_POST);exit;
      $this->check_alllist_access(CONTROLLER_NAME, 'edi', $_POST['kids'], 0, 'crm_seo_key');

      $data['someno']=time();
      foreach ($_POST['kids'] as $key => $value){
        D("crm_seo_key")->where("id=".$value)->data($data)->save();
      }
      $this->success("對比成功");
    }

    public function check_alllist_access($acc_type, $acc_method, $ids=[], $teamid=0, $target_table='crm_contract'){
      parent::check_has_access($acc_type, $acc_method); /*檢查是否有設定此權限*/

      // dump($ids);exit;
      if($ids!=[]){ /*須依根據處理對象檢查權限*/
        foreach ($ids as $id) {
          $crm_contract = D('crm_contract')->find($id);
          if($this->get_or_pay!=$crm_contract['get_or_pay']){ $this->error('請至正確頁面操作'); }
        }
      }
    }
  }
?>