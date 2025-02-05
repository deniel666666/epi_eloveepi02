<?php
namespace Trade\Controller;
use Trade\Controller\GlobalController;

use Photonic\Common;
use Photonic\CustoHelper;
use Photonic\ContractHelper;
use Photonic\ProductHelper;

class CustoController extends GlobalController 
{
  static public $custo_index_count=100;	/*客戶列表一頁數量*/
  static public $crm_panel_page_count=20; /*CRM左側操作面板一頁數量*/
  static public $chats_page_count=20;     /*CRM右側訪談內容一頁數量*/

  /*此controller允許自定義操作的資料庫*/
  static public $crm_associate_db=[
    'crm_crm', 'crm_chats', 'crm_memo', 'crm_contact', 'crm_website', 'crm_contract',
    'custo_level1','custo_level2','custo_level3',
  ];
  static public $crm_detail_associate_db=['crm_chats', 'crm_memo', 'crm_contact', 'crm_website'];

  function _initialize(){
    parent::_initialize();
    if(!$_SESSION['custo_lab'])
      $_SESSION['custo_lab'] = '1';
    
    if($_POST['key'] == "bossphone" && strlen($_POST['key'])>1){
      if(false ==($rst = strpos($_POST['val'],'-'))){
        $_POST['val']=substr($_POST['val'],0,2).'_'.substr($_POST['val'],2,9);
        $_POST['val']=str_replace('(','_',$_POST['val']);
        $_POST['val']=str_replace(')','_',$_POST['val']);
      }else{
        $_POST['val']=str_replace('-','_',$_POST['val']);
        $_POST['val']=str_replace('(','_',$_POST['val']);
        $_POST['val']=str_replace(')','_',$_POST['val']);
      }
    }
  }

  // 客户列表
  public function index(){
    $powercat_id = 16;
    $page_title_link_self = U('Custo/index');
    $status_supplier = $_GET['status_supplier'] ?? '';
    if($status_supplier==2){ /*供應商*/
      $powercat_id = 158;
      $page_title_link_self = U('Custo/index').'?status_supplier='.$status_supplier;
    }
    $powercat_title =  D('powercat')->find($powercat_id)['title'];
    $this->assign('page_title', $powercat_title);
    $this->assign('page_title_link_self', $page_title_link_self);
    $this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

    if($_GET['teamid']){
      $this->assign('page_title_active', $_GET['teamid'].'_teamid');
      $eip_team = D('eip_team')->where('id="'.$_GET['teamid'].'"')->find();
      if($eip_team){
        $this->assign('page_title', $powercat_title.' > '.$eip_team['name']);
        $this->assign('page_title_link_self', $_SERVER['REQUEST_URI']);
      }
    }
    $return_data = $this->search_customer($_GET, 1);
    $return_data = $this->merge_industr_with_search_customer($_GET, $return_data);

    $this->display('Custo/index');
  }
  /*ajax取得客戶搜尋資料*/
  public function ajax_search_customer($ajaxReturn=true){
    foreach($_POST as $k=>$v){
      $_POST[$k] = trim($_POST[$k]);
    }
    $return_data = $this->search_customer($_POST, self::$custo_index_count);
    $return_data = $this->merge_industr_with_search_customer($_GET, $return_data);
    $return_data['country'] = $return_data['country'] ? $return_data['country'] : "";
    $return_data['district'] = $return_data['district'] ? $return_data['district'] : "";

    // 計算開放、垃圾桶客戶數
      $part_where = $return_data['part_where'];
      $count_list = [0,0];
      $count=D()->query("SELECT COUNT(*) as count_num FROM `crm_crm` WHERE   typeid = '4' and {$part_where}")[0]['count_num'];
      $count_list[0] = $count ? $count : 0;
      $count=D()->query("SELECT COUNT(*) as count_num FROM `crm_crm` WHERE   typeid = '5' and {$part_where}")[0]['count_num'];
      $count_list[1] = $count ? $count : 0;
      $return_data['count_list'] = $count_list;

    if($return_data['team_name']){
      $this->assign('page_title', $return_data['team_name']);
    }

    $return_data['typeid'] = $_POST['typeid'];
    
    /*處理各客戶資料*/
    foreach($return_data['crmlist'] as $key => $vo){
      /*等級*/
      $return_data['crmlist'][$key]['level']=$return_data['levels'][$vo['levelid']-1]['name'];
      
      /*類型*/
      $return_data['crmlist'][$key]['type']=$vo['type_name'];

      /*取得當前大項的次項選項*/
      $industr2[$key+1]=M("crm_industr")->where("industr = '".$vo['industr']."'")->select();
    }
    $return_data['industr2'] = $industr2;
    
    /*員工*/
    $return_data['eip_user_all'] = parent::index_set('eip_user',"is_job=1 and id !=".self::$top_adminid." and (no like '正%' or no like '臨%')", '', true);
    if($return_data['team_id'] == ''){
      $return_data['eip_user'] = $return_data['eip_user_all'];
    }else{
      $return_data['eip_user'] = parent::index_set('eip_user',"is_job=1 and id !=".self::$top_adminid." and id in( ".$return_data['team_id'].")", '', true);
    }

    $return_data['scv'] = $_POST['searchvalue'];

    if($ajaxReturn){
      $this->ajaxReturn($return_data);
    }else{
      return $return_data;
    }
  }
  // 現況追蹤
  public function crmtrace(){
    $powercat_id = 16;
    $page_title_link_self = U('Custo/crmtrace');
    $status_supplier = $_GET['status_supplier'] ?? '';
    if($status_supplier==2){ /*供應商*/
      $powercat_id = 158;
      $page_title_link_self = U('Custo/crmtrace').'?status_supplier='.$status_supplier;
    }
    $this->assign('page_title', '現況追蹤');
    $this->assign('page_title_link_self', $page_title_link_self);
    $this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

    foreach($_GET as $k=>$v){
      $_GET[$k] = trim($_GET[$k]);

      if(isset($_GET['teamid'])){
        if($_GET['teamid']){
          $this->assign('page_title_active', $_GET['teamid'].'_teamid');
          $eip_team = D('eip_team')->where('id="'.$_GET['teamid'].'"')->find();
          if($eip_team){
            $this->assign('page_title', '現況追蹤 > '.$eip_team['name']);
            $this->assign('page_title_link_self', $_SERVER['REQUEST_URI']);
          }
        }
      }
      else if($k=='orders'){
        $order_list = explode(":", $v);
        $dateline_order = $order_list[1]=='desc' ? 'asc' : 'desc';
        $this->assign('dateline_order', $dateline_order);
      }
    }
    $return_data = $this->search_customer($_GET, 1);
    $return_data = $this->merge_industr_with_search_customer($_GET, $return_data);
    
    parent::index_set('crm_chatqulity');

    $this->display('Custo/crmtrace');
  }
  /*現況追蹤 ajax資料*/
  public function ajax_search_crmtrace(){
    $return_data = $this->ajax_search_customer($ajaxReturn=false);
    $crmlist = $return_data['crmlist'];
    
    /*撈取訪談資料*/
    foreach ($crmlist as $key => $value) {
      $crm_chats = D("crm_chats cc")->field('cc.*, cq.name as qulid_name')
              ->join("left join crm_chatqulity cq on cq.id= cc.qulid")
              ->where("cc.cumid =".$value['id'])->order("cc.id desc")->limit(1)->select()[0];
      $crmlist[$key]['last_chats'] = $crm_chats;
      $crmlist[$key]['last_chats']['dateline_format'] = date('Y-m-d H:i', $crm_chats['dateline']);
      $crmlist[$key]['last_chats']['appmdate_format'] = date('Y-m-d H:i', $crm_chats['appmdate']);
      
      /*以訪談紀錄取得聯繫對象*/
      $crmlist[$key]['last_chats']['contacter'] = CustoHelper::get_chat_contacter($crm_chats);
    }
    $return_data['crmlist'] = $crmlist;

    $this->ajaxReturn($return_data);
  }
  //批次處理修改
  public function patchupdate(){
    // dump($_POST);exit;
    $post_sele = $_POST['sele'];
    unset($_POST['sele']);
    unset($_POST['update']);

    if(isset($_POST['slele']))
      $post_sele = $_POST['slele'];
      unset($_POST['slele']);
    if(empty($post_sele)){
      $this->error('請選擇客戶');exit;
    }
    
    /*檢查權限*/
    $this->check_crm_access('custo', 'edi', $post_sele, $_POST['teamid']);
        
    if($_POST['typeid']!="" && $_POST['typeid']!="-1") $data['typeid']=$_POST['typeid'];
    if($_POST['levelid']!="" && $_POST['levelid']!="-1") $data['levelid']=$_POST['levelid'];
    if($_POST['industr']!='0' && $_POST['industr']!="") $data['industr']=$_POST['industr'];
    if($_POST['industr2']!='0' && $_POST['industr2']!="") $data['industr2']=$_POST['industr2'];
    if($_POST['wid']!="" && $_POST['wid']!="-1") $data['wid']=$_POST['wid'];
    if($_POST['did']!="" && $_POST['did']!="-1") $data['did']=$_POST['did'];
    if($_POST['sid']!="" && $_POST['sid']!="-1") $data['sid']=$_POST['sid'];
    if($_POST['hid1']!="" && $_POST['hid1']!="-1") $data['hid1']=$_POST['hid1'];
    if($_POST['hid2']!="" && $_POST['hid2']!="-1") $data['hid2']=$_POST['hid2'];
    if($_POST['hid3']!="" && $_POST['hid3']!="-1") $data['hid3']=$_POST['hid3'];

    if($_POST['need_project']!="") $data['need_project']=(int)$_POST['need_project'];
    if($_POST['need_visit']!="") $data['need_visit']=(int)$_POST['need_visit'];
    if($_POST['need_proposal']!="") $data['need_proposal']=(int)$_POST['need_proposal'];
    
    foreach ($_POST as $key => $value) {
      if($value=="-1" || $value==""){ continue; }
      if($value=="no"){ $data[$key]="0"; }
    }
    //$post_sele = $post_sele[0];
    
    //-----------------
    // 超過潛在上限判斷
    //----------------
    $canAddPot = true;
    $haveNoneWid = false;  
    $potErrMsg = '';
    $haveWid = (isset($_POST['wid']));
    // dump($haveWid);
    // exit();
    //dump($post_sele);
    if ($_POST['typeid'] == 2){
      if (true){
        if ($_POST['wid'] != 'no'){
          if( $_POST['wid'] ==0){
            foreach($post_sele as $crmId){
              $checkWid = D('crm_crm')->where('id='.$crmId)->find()['wid'];
              // dump($checkWid);
              $potErrMsg = CustoHelper::potNum_anysis($checkWid, $post_sele);//檢查潛在上限
            }//foreach
          }else{
            $potErrMsg = CustoHelper::potNum_anysis($_POST['wid'], $post_sele);//檢查潛在上限
          }					
        }
      }else{
        $candidate = array();
        foreach($post_sele as $crmId){
          $crm = D('crm_crm')->where('id='.$crmId)->find();
          // dump($crm);
          if ($crm['wid']!=0){
            if (array_search($candidate,$crm['wid'])){
              $candidate[$crm['wid']] = 1;
            }else{
              $candidate[$crm['wid']] += 1;
            }	
          }else{
            $haveNoneWid = true;
          }
        }
        // dump($haveNoneWid);
        if (!$haveNoneWid){
          foreach($candidate as $cKey => $cValue){
            $potErrMsg = CustoHelper::potNum_anysis($cKey, $post_sele);//檢查潛在上限
            if ($potErrMsg != ''){ break; }
          }
        }
      }
    }

    //--- 超過潛在上限判斷 ---//
    if ($potErrMsg=='' &&(!$haveNoneWid)){
      foreach($post_sele as $vo){
        $data['newclient_date'] = CustoHelper::get_newclient_date($vo, $_POST); /*設定起追日期*/
        if(empty($data)){ $this->error('請設定修改資料'); }

        $crm_crm = D('crm_crm')->where('id='.$vo)->find();
        if(!$crm_crm){ continue; }
        $salesid = $_POST['wid']!=-1 ? $_POST['wid'] : $crm_crm['wid'];
        $typeid = $_POST['typeid']!=-1 ? $_POST['typeid'] : $crm_crm['typeid'];
        CustoHelper::add_salesrecord(
          $salesid, 					// 業務id
          $opeid	 = session('eid'),	// 操作人員
          $cid 	 = $vo, 			// 客戶id
          $typeid  					// 修改的客戶類型
        );

        if($_POST['typeid']=='7'){
          $this->check_crm_access('custo', 'del', [ $vo ], $_POST['teamid']);
          D('crm_crm')->where('id='.$vo)->delete();//真刪資料
        }else{
          if($_POST['typeid']=='6'){
            $this->check_crm_access('custo', 'hid', [ $vo ], $_POST['teamid']);
          }
          D('crm_crm')->where('id='.$vo)->data($data)->save();
        }

        parent::error_log("修改crm_crm 客戶:".$vo.", 資料:".json_encode($data, JSON_UNESCAPED_UNICODE));
      }
      $this->success('更新成功');
    }
    else{
      if ($haveNoneWid){
        $this->error("有客戶未指定業務");
      }else{
        $this->error($potErrMsg);	
      }
    }
  }
  // 匯出index_excel
  public function index_excel(){
    if(self::$control_export_crm!=1){ $this->error('無發操作'); }
    $cid = session('cid');
    $levels = D()->query("SELECT `id`,`name` FROM crm_cum_level WHERE `cid`=$cid AND `status`=1");
  
    $title = [ 
      "序號",
      self::$system_parameter["簡稱"],
      self::$system_parameter["公司名稱"],
      self::$system_parameter["統編"],
      self::$system_parameter["類別"],
      self::$system_parameter["等級"],
      self::$system_parameter["公司電話"],
      self::$system_parameter["公司手機"],
      self::$system_parameter["公司MAIL"],

      self::$system_parameter["負責人"].self::$system_parameter["名稱"],
      self::$system_parameter["負責人"].self::$system_parameter["職稱"],
      self::$system_parameter["負責人"].self::$system_parameter["負責人電話"],
      self::$system_parameter["負責人"].self::$system_parameter["負責人手機"],
      self::$system_parameter["負責人"].self::$system_parameter["負責人MAIL"],

      self::$system_parameter["聯絡人"].self::$system_parameter["名稱"],
      self::$system_parameter["聯絡人"].self::$system_parameter["職稱"],
      self::$system_parameter["聯絡人"].self::$system_parameter["聯絡人電話"],
      self::$system_parameter["聯絡人"].self::$system_parameter["聯絡人手機"],
      self::$system_parameter["聯絡人"].self::$system_parameter["聯絡人MAIL"],

      self::$system_parameter["產業別"],
      self::$system_parameter["產業次項"],
      self::$system_parameter["地址"],
      self::$system_parameter["資本額"],
      self::$system_parameter["公司核准日"],
    ];

    if(isset($_GET['ids'])){ // 依勾選產生
      $where_in = join(',', json_decode($_GET['ids']));
      $crmlist = D()->query("
          SELECT crm_crm.*,
                crm_cum_type.name as type_name,
                crm_cum_type.cid,
                crm_cum_type.type,
                crm_cum_type.status,
                crm_cum_type.sort 
          FROM `crm_crm` 
          LEFT JOIN crm_cum_type 
          ON crm_crm.typeid=crm_cum_type.id
          WHERE crm_crm.id IN ( 0,{$where_in} ) 
          ORDER BY {$order_query} crm_cum_type.sort,crm_crm.levelid,CAST(CONVERT(crm_crm.nick using big5) AS BINARY)
          ");

    }else{ // 依搜尋結果產生
      foreach($_GET as $k=>$v){
        $_GET[$k] = trim($_GET[$k]);
      }
      $crmlist = $this->search_customer($_GET, 10000)['crmlist'];
    }

    $crm_data=[];
    foreach ($crmlist as $key => $value) {
      $contact = D("crm_contact")->where("cumid = ".$value['id'])->order("radio asc, id desc")->limit(1)->select()[0];
      $data = [
        'id' 			=> $key+1,
        'nick' 			=> $value['nick'],
        'name' 			=> $value['name'],
        'no' 			=> '="'.$value['no'].'"',
        'type'			=> $value['type_name'],
        'levelid' 		=> $levels[$value['levelid']-1]['name'],
        'comphone' 		=> $value['comphone'],
        'commobile' 	=> '="'.$value['commobile'].'"',
        'commail' 		=> '="'.$value['commail'].'"',
        
        'bossname' 		=> $value['bossname'],
        'bossposition'	=> $value['bossposition'],
        'bossphone' 	=> '="'.$value['bossphone'].'"',
        'bossmobile' 	=> '="'.$value['bossmobile'].'"',
        'bossmail' 		=> $value['bossmail'],
        
        'c_name' 		=> $contact['cname'],
        'c_position' 	=> $contact['position'],
        'c_phone' 		=> '="'.$contact['phone'].'"',
        'c_mobile' 		=> '="'.$contact['mobile'].'"',
        'c_mail' 		=> $contact['mail'],
        
        'industr' 		=> $value['industr'],
        'industr2' 		=> $value['industr2'],
        'addr' 			=> (string)$value['zip'].(string)$value['addr'],
        'zbe' 			=> $value['zbe'] ? $value['zbe'].'萬' : '',
        'hzrq' 			=> $value['hzrq'],
      ];
      array_push($crm_data, $data);
    }
    // dump($crm_data);exit;
    parent::DataDbOut($crm_data,$title,$list_start="A2",$file_title="客戶資料");
  }
  // 匯出index_excel_import(匯入格式)
  public function index_excel_import(){
    if(self::$control_export_crm!=1){ $this->error('無發操作'); }
    $cid = session('cid');
    $levels = D()->query("select `id`,`name` from crm_cum_level where `cid`=$cid and `status`=1");
  
    $title = [
      self::$system_parameter["公司名稱"],
      self::$system_parameter["統編"],
      '郵遞區號',
      self::$system_parameter["地址"],
      self::$system_parameter["公司電話"],
      self::$system_parameter["公司手機"],
      self::$system_parameter["公司MAIL"],
      self::$system_parameter["官方網站"],
      self::$system_parameter["傳真"],
      in_array(115, self::$use_function_top) ? self::$system_parameter["產業別"]."(限4字)" : "",
      
      self::$system_parameter["負責人"] ? self::$system_parameter["負責人"].self::$system_parameter["名稱"] : "",
      self::$system_parameter["負責人"] ? self::$system_parameter["負責人"].self::$system_parameter["負責人電話"] : "",
      self::$system_parameter["負責人"] ? self::$system_parameter["負責人"].self::$system_parameter["負責人手機"] : "",
      self::$system_parameter["負責人"] ? self::$system_parameter["負責人"].self::$system_parameter["負責人MAIL"] : "",
      
      self::$system_parameter["聯絡人"] ? self::$system_parameter["聯絡人"].self::$system_parameter["名稱"] : "",
      self::$system_parameter["聯絡人"] ? self::$system_parameter["聯絡人"].self::$system_parameter["聯絡人電話"] : "",
      self::$system_parameter["聯絡人"] ? self::$system_parameter["聯絡人"].self::$system_parameter["聯絡人手機"] : "",
      self::$system_parameter["聯絡人"] ? self::$system_parameter["聯絡人"].self::$system_parameter["聯絡人MAIL"] : "",

      self::$system_parameter["資本額"]."(萬元)",
      self::$system_parameter["公司核准日"]."(YYYY-mm-dd)",
      self::$system_parameter["公司備註"],
      self::$system_parameter["員工人數"] ? self::$system_parameter["員工人數"] : "",
      self::$system_parameter["來源"],
      self::$system_parameter["訪談紀錄"] ? self::$system_parameter["訪談紀錄"]."(限一則)" : "",
    ];

    if(isset($_GET['ids'])){ // 依勾選產生
      $where_in = join(',', json_decode($_GET['ids']));
      $crmlist = D()->query("select crm_crm.*,crm_cum_type.name as type_name,crm_cum_type.cid,crm_cum_type.type,crm_cum_type.status,crm_cum_type.sort from `crm_crm` 
          LEFT JOIN crm_cum_type 
          ON crm_crm.typeid=crm_cum_type.id
          where  crm_crm.id in ( 0,{$where_in} ) 
          order by {$order_query} crm_cum_type.sort,crm_crm.levelid,CAST(CONVERT(crm_crm.nick using big5) AS BINARY)
          ");
    }else{ // 依搜尋結果產生
      foreach($_GET as $k=>$v){
        $_GET[$k] = trim($_GET[$k]);
      }
      $crmlist = $this->search_customer($_GET, 10000)['crmlist'];
    }

    $crm_data=[];
    foreach ($crmlist as $key => $value) {
      $contact = D("crm_contact")->where("cumid = ".$value['id'])->order("radio asc, id desc")->limit(1)->select()[0];
      $sourse = D("crm_cum_sourse")->where("id ='".$value['sourceid']."'")->find();

      $data = [
        'name' => $value['name'],
        'no' => '="'.$value['no'].'"',
        'zip' => (string)$value['zip'],
        'addr' => (string)$value['addr'],
        'comphone' => '="'.$value['comphone'].'"',
        'commobile' => '="'.$value['commobile'].'"',
        'commail' => $value['commail'],
        'url1' => $value['url1'],
        'comfax' => '="'.$value['comfax'].'"',
        'industr' => $value['industr'],
        
        'bossname' => 	self::$system_parameter["負責人"] ? $value['bossname'] : "",
        'bossphone' => 	self::$system_parameter["負責人"] ? '="'.$value['bossphone'].'"' : "",
        'bossmobile' => self::$system_parameter["負責人"] ? '="'.$value['bossmobile'].'"' : "",
        'bossmail' => 	self::$system_parameter["負責人"] ? $value['bossmail'] : "",
        
        'c_name' => 	self::$system_parameter["聯絡人"] ? $contact['cname'] : "",
        'c_phone' => 	self::$system_parameter["聯絡人"] ? '="'.$contact['phone'].'"' : "",
        'c_mobile' => 	self::$system_parameter["聯絡人"] ? '="'.$contact['mobile'].'"' : "",
        'c_mail' => 	self::$system_parameter["聯絡人"] ? $contact['mail'] : "",
        
        'zbe' => $value['zbe'] ? $value['zbe'] : '',
        'hzrq' => $value['hzrq'],
        'mom' => $value['mom'],
        'member_num' => "",
        'sourceid' => $sourse ? $sourse['name'] : "",
        'chats' => "",
      ];
      array_push($crm_data, $data);
    }
    // dump($crm_data);
    parent::DataDbOut($crm_data,$title,$list_start="A3",$file_title="客戶資料(匯入格式)");
  }
  // 產生信封
  public function print_envelope(){
    if(isset($_GET['ids'])){ // 依勾選產生
      $where_in = join(',', json_decode($_GET['ids']));
      $crmlist = D()->query("
          SELECT crm_crm.*,
               crm_cum_type.name as type_name,crm_cum_type.cid,crm_cum_type.type,crm_cum_type.status,crm_cum_type.sort 
          FROM `crm_crm` 
          LEFT JOIN crm_cum_type 
          ON crm_crm.typeid=crm_cum_type.id
          WHERE  crm_crm.id in ( 0,{$where_in} ) 
          ORDER BY {$order_query} crm_cum_type.sort,crm_crm.levelid,CAST(CONVERT(crm_crm.nick using big5) AS BINARY)
          ");

    }else{ // 依搜尋結果產生
      foreach($_GET as $k=>$v){
        $_GET[$k] = trim($_GET[$k]);
      }
      $crmlist = $this->search_customer($_GET, 1000)['crmlist'];
    }
    //dump($crm_crm);exit;
    
    $crm_crm = [];
    foreach($crmlist as $k=>$v){
      $address= CustoHelper::get_crm_show_addr($v, $type='mail');

      /*加入 郵遞區號*/
      $zip = $v['zip'];
      // if(!(bool)$zip){ /*如果資料庫未紀錄*/
      // 	// $url = "http://".$_SERVER['HTTP_HOST']."/index.php/FatApi/zpi32?addr=".$address; /*以自寫程式抓取*/
      // 	$url = "https://zip5.5432.tw/zip/".$address; /*串外部api抓取*/
      // 	$zpi_id = 'id="zipcode"';
      // 	$zpi_end = '</span>';
      // 	$start_catch = strlen($zpi_id) + 1;
      // 	$string = file_get_contents($url);
      // 	// dump($string);
      // 	if ($pos !== false) {
      // 		$pos = strpos($string, $zpi_id);
      // 		$pos_e = strpos($string, $zpi_end, $pos);
      // 		$string = substr($string, $pos + $start_catch , $pos_e - $pos - $start_catch);
      // 		$zip = $string;
      // 		// dump($zip);
      // 		// exit;
      // 	}
      // }
      $address = $zip.$address; 

      $crm_data = [
        'id'		=> $v['id'],
        'zip'		=> $zip,
        'address'	=> $address,
        'name'		=> $v['name'],
        'cname'		=> $v['bossname'],
        'cposition'	=> $v['bossposition'] ? $v['bossposition'] : "先生/小姐",
      ];
      $contacter = CustoHelper::get_contacter($v['id']);
      // dump($contacter);exit;
      if($contacter){
        $contacter = $contacter[0];
        $crm_data['cname'] = $contacter['cname'];
        $crm_data['cposition'] = $contacter['position'] ? $contacter['position'] : "先生/小姐";
      }
      // dump($crm_data);exit;
      array_push($crm_crm, $crm_data);
    }
    
    $size = isset($_GET['size']) ? $_GET['size'] : 'h_b';

    if($size == 'v_s'){ /*如果尺寸是直小*/
      foreach ($crm_crm as $key => $value) { /*拆解字串成陣列*/
        $crm_crm[$key]['address_rotate'] = CustoHelper::add_rotate(mb_str_split($value['address'], 1));
        $crm_crm[$key]['name'] = CustoHelper::add_rotate(mb_str_split($value['name'], 1));
        $crm_crm[$key]['cname'] = CustoHelper::add_rotate(mb_str_split($value['cname'], 1));
        $crm_crm[$key]['cposition'] = CustoHelper::add_rotate(mb_str_split($value['cposition'], 1));
      }
    }
    // dump($crm_crm);exit;
    $this->assign('crmlist', $crm_crm);

    $this->display('Custo/print_envelope/'.$size);
  }

  // 從客戶列表點擊進CRM
  public function view_re(){
    $this->redirect('Custo/view', ['id' => $_GET['id'],'teamid'=>$_GET['teamid']]);
  }
  public function view($id=false){
    if(!in_array(75, self::$use_function_top)){
      $this->error('無此頁面');
    }
    parent::check_has_access('Crm', 'red');

    $powercat_title =  D('powercat')->find(75)['title'];
    $this->assign('page_title', $powercat_title);
    $this->assign('page_title_link_self', U('Custo/view'));
    $this->assign('page_title_active', 75);  /*右上子選單active*/

    $this->assign("current_qh", date('Y/m'));
    $this->assign('current_datetime_local_value', date('Y-m-d').'T00:00');

    if($id){
      $crmId = $id;
    }else{
      $crmId = $_GET['id'];
      $crmId = $crmId ? $crmId : self::$our_company_id;
    }
    $this->assign('crmId', $crmId);

    $teamid = $_GET['teamid'];
    $teamid = $teamid ? $teamid : 0;
    $this->assign('teamid', $teamid);
    $this->assign('myId', session('eid'));

    $crm_cum_type = D('crm_cum_type')->index('id')->select();
    $this->assign('crm_cum_type_new', $crm_cum_type[1]['name']);
    $this->assign('crm_cum_type_pot', $crm_cum_type[2]['name']);
    $this->assign('crm_cum_type_cur', $crm_cum_type[3]['name']);

    $this->display('Custo/view');
  }
  /*編輯CRM詳細內容頁面*/
  public function addcrm(){
    if(in_array(75, self::$use_function_top)){
      $powercat_title =  D('powercat')->find(75)['title'];
      $this->assign('page_title', $powercat_title);
      $this->assign('page_title_link_self', U('Custo/view'));
      $this->assign('page_title_active', 75);  /*右上子選單active*/
    }else{
      $powercat_title =  D('powercat')->find(16)['title'];
      $this->assign('page_title', $powercat_title);
      $this->assign('page_title_link_self', U('Custo/index'));
      $this->assign('page_title_active', 16);  /*右上子選單active*/
    }

    $id = isset($_GET['id']) ? $_GET['id'] : "0";
    $teamid = isset($_GET['teamid']) ? $_GET['teamid'] : 0;
    if($id!=0 && $id!=-1){
      /*檢查crm操作權限檢查*/
      $this->check_crm_access('custo', 'edi', [ $id ], $teamid);
    }
    else{
      /*檢查crm操作權限檢查*/
      $this->check_crm_access('custo', 'new', [], $teamid);
    }
    $this->display('Custo/addcrm');
  }

  //CRM詳細內容頁 ajax 修改置頂聯絡人
  public function save_crm_contact_top(){
    $id = $_POST['id'] ? $_POST['id'] : "";
    $cumid = $_POST['cumid'] ? $_POST['cumid'] : "";
    if(!$id || !$cumid){
      $this->error('請提供完整資料');
    }

    /*檢查權限*/
    $crms = [ $cumid ];
    $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : 0;
    $this->check_crm_access('crm', 'edi', $crms, $teamid);

    $crm_contact = D('crm_contact')->where("id='".$id."'")->find();
    if(!$crm_contact){
      $this->error('無此聯絡人');
    }
    $data['radio'] = 0;
    D('crm_contact')->data($data)->where("cumid='".$crm_contact['cumid']."'")->save();
    
    $data['radio'] = 1;
    D('crm_contact')->data($data)->where("id='".$id."'")->save();

    $this->success('修改成功');
  }
  //CRM詳細內容頁 ajax 修改指定資料表指定欄位資料(訪談紀錄、綜合事項...)
  public function ajax_save_one_value(){
    $dbname = isset($_POST['dbname']) ? $_POST['dbname'] : "";
    $id = isset($_POST['id']) ? $_POST['id'] : "";
    $column = isset($_POST['column']) ? $_POST['column'] : "";
    $value = isset($_POST['value']) ? $_POST['value'] : "";
    if(!$dbname || !$id || !$column){
      $this->error('請提供完整資料');
    }
    if(!in_array($dbname, self::$crm_associate_db)){
      $this->error('無法操作此資料庫');
    }
    $update_data = [$column => $value];

    $target = M($dbname)->where("id = '".$id."'")->find();
    if(!$target){ $this->error('無此編輯對象'); }

    /*找出操作的客戶id (預設抓cumid欄位)*/
    if( $dbname=='crm_crm' ){ /*操作的是客戶*/
      $target['cumid'] = $target['id'];
    }
    else if( $dbname=='crm_contract' ){ /*操作的是合約*/
      $target['cumid'] = $target['cid'];
    }
    else if( $dbname=='custo_level1' || $dbname=='custo_level2' || $dbname=='custo_level3' ){
      $target['cumid'] = 0;
    }
    
    if($dbname =='crm_chats' && ( ($column=='doevt' && $value=='1') || $column=='do_response') ){ /*修改的是 訪談紀錄 且 是處理小事或處理回覆*/
      if( $target['doid']!=session('eid') && $target['doid']!=0){ /*不做權限判斷，只檢查是否為處理人*/
        $this->error('您無法處理此小事');
      }
    }else{
      /*檢查crm操作權限檢查*/
      $crms = $target['cumid'] ? [ $target['cumid'] ] : [];
      $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : 0;
      $this->check_crm_access('crm', 'edi', $crms, $teamid);
    }

    if($dbname =='crm_chats'){ /*修改的是 訪談紀錄*/
      if($column=='do_response'){ /*修改的是 處理回覆*/
        if($target['doevt']==1){ $this->error('小事已處理，無法修改處理紀錄'); };
        if($value){ /*有紀錄內容*/
          $update_data['doevt'] = '1';		/*設定為已處理*/
          $update_data['do_time'] = time();	/*紀錄處理時間*/
          CustoHelper::send_smallthing_done($target, $target['eid'], $target['cumid']); /*寄送小事件完成給建立者*/
        }else{
          $this->error('請輸入處理回覆');
        }
      }
      else if($column=='do_review_time'){ /*修改的是 已讀處理回覆*/
        if($target['doevt']!=1){
          $this->error('尚未處理完畢');
        }
        else if($target['eid']!=session('adminId')){
          $this->error('您不須已讀此處理回覆');
        }
        else if($target['do_review_time']!=null){
          $this->error('無需更新已讀狀態');
        }
        $update_data['do_review_time'] = time();
      }
      else if( !($column=='doevt' && $value=='1') ){ /*修改的是 不是處理小事*/
        $dateline_format = date('Y-m-d', $target['dateline']);
        $now_format = date('Y-m-d');
        if( $dateline_format < $now_format ){
          $this->error('已超出修改期限');
        }

        if($column=='appmdate'){ /*修改預約時間*/
          if($value){
            $value = strtotime(str_replace('T', ' ', $value));
            if($value<time()){
              $this->error('無法預約過去的時間');
            }
            $update_data[$column] = $value; /*修改value成轉換成時間戳的*/
          }
        }

        if($column=='doid'){ /*修改的是處理者*/
          if($value==0){ /*修改成沒有處理者*/
            $update_data['smevt'] = '0'; /*取消勾選小事*/
          }else{ /*修改成其他處理者*/
            $update_data['smevt'] = '1'; /*勾選小事*/
            $update_data['doevt'] = '0'; /*改成未處理*/
            CustoHelper::send_smallthing_remind($target, $value, $target['cumid']); /*寄送小事件提醒給處理者*/
          }
        }
      }
    }

    if($dbname =='crm_crm' && $column=='fields_data'){ /*修改的是 客戶資料的特性資料*/
      // dump($value);
          $saved_fields_data = $target['fields_data'] ? (Array)json_decode($target['fields_data']) : [];
      $value = CustoHelper::instance()->check_ans_of_fields('crm_property', $value, $saved_fields_data);
      $value = json_encode($value, JSON_UNESCAPED_UNICODE);
      $update_data[$column] = $value; /*重新設定修改的值*/
    }

    if($dbname =='crm_crm' && $column=='typeid'){ /*修改客戶類別*/
      CustoHelper::add_salesrecord(
        $salesid = $target['wid'] ? $target['wid'] : 0, // 業務id
        $opeid	 = session('eid'),	// 操作人員
        $cid 	 = $id, 			// 客戶id
        $typeid  = $value			// 修改的客戶類型
      );
      $this->check_typeid($value);
      if($value==1){ /*修改改新進客戶*/
        $checkWid = $target['wid'];
        $update_data['newclient_date'] = CustoHelper::get_newclient_date($id, $update_data);/*設定起追日期*/
        if($potErrMsg){ $this->error($potErrMsg); }
      }
      else if($value==2){ /*修改為潛在客戶*/
        $checkWid = $target['wid'];
        $potErrMsg = CustoHelper::potNum_anysis($checkWid, [$id]);//檢查潛在上限
        if($potErrMsg){ $this->error($potErrMsg); }
      }
    }

    // dump($update_data);exit;
    D($dbname)->data($update_data)->where("id={$id}")->save();
    parent::error_log('修改資料表：'.$dbname.', ID:'.$id.', 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));

    $this->success('修改成功');
  }
  //CRM詳細內容頁 ajax 刪除(綜合事項...)
  public function viewdelete(){
    $dbname = isset($_POST['dbname']) ? $_POST['dbname'] : "";
    $id = isset($_POST['id']) ? $_POST['id'] : "";
    if(!$dbname || !$id){
      $this->error('請提供完整資料');
    }
    if(!in_array($dbname, self::$crm_detail_associate_db)){
      $this->error('無法操作此資料庫');
    }

    $target = M($dbname)->where("id = '".$id."'")->find();
    if(!$target){ $this->error('無此刪除對象'); }
    if( $dbname=='crm_crm' ){
      $target['cumid'] = $target['id'];
    }

    /*檢查crm操作權限檢查*/
    $crms = [ $target['cumid'] ];
    $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : 0;
    $this->check_crm_access('crm', 'del', $crms, $teamid);

    if(M($dbname)->where("id = '".$id."'")->delete()){
      $this->success('刪除成功');
    }
  }
  //CRM詳細內容頁 ajax 新增(訪談紀錄、綜合事項...)
  public function viewadd(){
    /*檢查crm操作權限檢查*/
    // $crms = [ $_POST['cumid'] ];
    // $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : 0;
    // $this->check_crm_access('crm', 'new', $crms, $teamid);

    $dbname = isset($_POST['dbname']) ? $_POST['dbname'] : "";
    if(!in_array($dbname, self::$crm_detail_associate_db)){
      $this->error('無法操作此資料庫');
    }

    $_POST['dateline']=time();
    $_POST['eid'] = session('eid');

    if($dbname=='crm_chats'){ /*新增 訪談記錄 的資料*/
      $crm_crm=D("crm_crm")->field('typeid')->where("id={$_POST['cumid']}")->find();
      $_POST['current_typeid'] = $crm_crm ? $crm_crm['typeid'] : 0;

      $appmdate_format = "";
      if($_POST['appmdate']!=""){
        $appmdate_format = str_replace("T", " ", $_POST['appmdate']);
        $_POST['appmdate']=strtotime($appmdate_format);

        if($_POST['appmdate']<time()){
          $this->error('無法預約過去的時間');
        }
      }

      /*有處設定處理者*/
      if($_POST['doid'] && $_POST['doid']!="" && $_POST['doid']!="0"){
        CustoHelper::send_smallthing_remind($_POST, $_POST['doid'], $_POST['cumid']); /*寄送小事件提醒給處理者*/
      }

      /*記錄當下建立訪談者腳色之顏色*/
      $cooperate_members=D("crm_crm")->field('did,wid,sid,hid1,hid2,hid3')->where("id={$_POST['cumid']}")->find();
      foreach ($cooperate_members as $k => $v) {
        if(session('eid') == $v ){
          $_POST['color_class'] = 'chat_record_'.$k;
          break;
        }
      }
      
      if($_POST['chattype2']=='0' && $appmdate_format!=""){ /*預約方式為面談 且 有設定預約時間*/
        $contacter = CustoHelper::get_chat_contacter($_POST);
        $eid_user = D("eip_user")->where('id="'.$_POST['eid'].'"')->find();
        $our_company = D("crm_crm")->where('id="'.self::$our_company_id.'"')->find();
        $our_company_phone = $our_company["comphone"] ? $our_company["comphone"] : $our_company["bossphone"];
        $body ="
          <p>敬愛的 ".$contacter['name']." 您好：</p>
          <p>".$this->eip_company_custom['name']." 的 ".$eid_user["name"]." 已於 ".$appmdate_format." 和您預約面談</p>
          <p>詳情請再連絡 ".$eid_user["name"]." 專員，電話：".$our_company_phone."，分機：".$eid_user["extension"]."</p>
          <p>並期待我們的會面</p>";
        // dump($body);exit;
        send_email($body, $contacter['mail'], "預約拜訪通知");
      }
    }

    if(D($dbname)->data($_POST)->add()){ // 如果新增至資料庫成功
      $this->success('新增成功');
    }


    $this->error('新增失敗');
  }

  //檢查重複
  public function aj_repeat(){
    $crm_id = $_POST['crm_id'] ?? 0;
    if(isset($_POST['val']) && ($_POST['val']!="")){
      $crm_crm_repeat = CustoHelper::check_crm_repeat($_POST['key'], $_POST['val'], $crm_id);
      if($crm_crm_repeat==0){
        $this->success("可以使用");
      }else{
        $this->error("有重複資料");
      }
    }else{
      $this->error("請填寫資料");
    }
  }
  // 新增、編輯客戶詳細資料
  public function do_addcrm(){
    // dump($_POST);exit;
    $_POST['createtime'] = time();
    $_POST['dateline'] = time();
    $_POST['nick'] = $_POST['nick'] ? $_POST['nick'] : mb_substr($_POST['name'], 0 ,5);
    $_POST['sn_num'] = !empty($_POST['sn_num']) ? $_POST['sn_num'] : '';

    $contact_data = $_POST['contact'];
    unset($_POST['contact']);
    // dump($contact_data);exit;

    $wid = $_POST['wid'];
    
    $crm_id=$_POST['id'];
    unset($_POST['id']);

    if( empty($_POST['name']) ){
      $this->error("請填寫".self::$system_parameter['公司名稱']);
    }
    
    // 重複資料檢查
    $repeat_name = CustoHelper::check_crm_repeat('name', $_POST['name'], $crm_id);
    $repeat_no = CustoHelper::check_crm_repeat('no', $_POST['no'], $crm_id);
    if($repeat_name!=0 || $repeat_no!=0){
      $this->error("有重複資料");
    }

    //潛在限制
    $checkPotMsg = CustoHelper::potNum_anysis($wid, [$crm_id]);
    //dump($checkPotMsg);
    $canAddPot = $checkPotMsg=="" ? true : false;
    //dump($canAddPot);exit;
    if (!$canAddPot){
      $this->error("超過潛在上限!!");
    }

    if(isset($_POST['typeid'])){
      $this->check_typeid($_POST['typeid']);
    }
    if($crm_id=="" || $crm_id=="-1"){ /*新增*/
      $this->check_crm_access('custo', 'new');

      $_POST['createid']=session('eid');
      //--------------------------------------------------------------------------------					
      $addr = $_POST['city'] ? $_POST['city'] : "";
      $_POST['addr'] .=$_POST['town']!="0" ? $_POST['town'] : "";
      $_POST['addr'] = $addr.$_POST['addr'];

      if($_POST['addr']!=''){
        $_POST['addr'] = str_replace("台","臺",$_POST['addr']);
        $_POST['addr'] = str_replace("F","樓",$_POST['addr']);
        $_POST['addr'] = str_replace("壹","1",$_POST['addr']);
        $_POST['addr'] = str_replace("貳","2",$_POST['addr']);
        $_POST['addr'] = str_replace("參","3",$_POST['addr']);
        $_POST['addr'] = str_replace("肆","4",$_POST['addr']);
        $_POST['addr'] = str_replace("伍","5",$_POST['addr']);
        $_POST['addr'] = str_replace("陸","6",$_POST['addr']);
        $_POST['addr'] = str_replace("柒","7",$_POST['addr']);
        $_POST['addr'] = str_replace("捌","8",$_POST['addr']);
        $_POST['addr'] = str_replace("玖","9",$_POST['addr']);
        $_POST['addr'] = str_replace("零","0",$_POST['addr']);
      }

      $crm_crm_addData = $_POST;
      $crm_crm_addData['newclient_date'] = CustoHelper::get_newclient_date(0, $crm_crm_addData); /*設定起追日期*/
      // dump($crm_crm_addData);exit;

      // 客戶數檢查
      if(CustoHelper::check_crm_num(1)){
        $this->error(self::$system_parameter['客戶'].'數量超出上限');
      }

      // 新增客戶
      $crm_id=D('crm_crm')->data($crm_crm_addData)->add();
      $_POST['cumid']=$crm_id;
      $data = $_POST;
    
      if ($_POST['typeid'] > 0 || ($_POST['wid'] > 0)){	
        // 添加
        CustoHelper::add_salesrecord(
          $salesid = $_POST['wid'],	// 業務id
          $opeid	 = session('eid'),	// 操作人員
          $cid 	 = $crm_id,			// 客戶id
          $typeid  = $_POST['typeid'],// 修改的客戶類型
          $new 	 = true 			// 新增
        );
      }	

      if($crm_id){
        if($contact_data){
          $need_deal_contact = false;
          foreach ($contact_data as $ck => $cv) {
            if($cv && $cv!=""){
              $need_deal_contact = true;
              break;
            }
          }

          if($need_deal_contact){
            $contact_data['cumid'] = $crm_id;
            $contact_data['dateline'] = time();
            D("crm_contact")->data($contact_data)->add();
          }
        }

        $result = [
          'code' => 1,
          'msg' => "新增成功!!"
        ];
        parent::error_log("新增 crm_crm 客戶,資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
      }else{
        $result = [
          'code' => 0,
          'msg' => "新增失敗!!"
        ];
        
      }
      //--------------------------------------------------------------------------------------
    }
    else{
      $this->check_crm_access('custo', 'edi', [$crm_id]);

      $crm = D('crm_crm')->where("id='".$crm_id."' ")->find();
      if(!$crm){ $this->error('操作的客戶不存在'); }

      $crm_crm_UpdData = $_POST;
      // dump($crm_crm_UpdData);exit;
      
      /*添加客戶轉換紀錄*/
        if (($_POST['typeid'] > 0) ||($_POST['wid'] > 0)){
          $wid = D('crm_crm')->field('wid')->where("id='".$crm_id."'")->find()['wid'];
          $typeid = D('crm_crm')->field('typeid')->where("id='".$crm_id."'")->find()['typeid'];
          if(empty($_POST['typeid']) || $_POST['typeid']==0){
            $_POST['typeid'] = $typeid;
          }

          // 添加
          CustoHelper::add_salesrecord(
            $salesid = $_POST['wid'],	// 業務id
            $opeid	 = session('eid'),	// 操作人員
            $cid 	 = $crm_id, 		// 客戶id
            $typeid  = $_POST['typeid']	// 修改的客戶類型
          );
        }

      $crm_crm_UpdData['newclient_date'] = CustoHelper::get_newclient_date($crm_id, $crm_crm_UpdData); /*設定起追日期*/
      
      // 儲存客戶資料
      $crm=D('crm_crm')->data($crm_crm_UpdData)->where("id={$crm_id}")->save();
      
      // 編輯聯絡人
      $conResult = 0;
      $contacters = CustoHelper::get_contacter($crm_id); /*取得置頂聯絡人*/
      $conid = count($contacters)>0 ? $contacters[0]['id'] : '';
      if($contact_data){ /*有傳入聯絡人資料*/
        $need_deal_contact = false;
        foreach ($contact_data as $ck => $cv) {
          if($cv && $cv!=""){
            $need_deal_contact = true;
            break;
          }
        }

        if($need_deal_contact){
          $contact_data['dateline'] = time();
          if($conid){
            $conResult = D('crm_contact')->data($contact_data)->where("id={$conid}")->save();
          }else{
            $contact_data['cumid']=$id;
            $conResult = M("crm_contact")->data($contact_data)->add();
          }
        }
      }

      if($crm||$conResult){
        // dump($crm);dump($conResult);exit;
        $result = [
          'code' => 1,
          'msg' => "更新成功!!"
        ];
        parent::error_log("編輯 crm_crm 客戶".$crm_id.",資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
      }else{
        $result = [
          'code' => 0,
          'msg' => "更新失敗!!"
        ];
      }
    }

    $result['url'] = in_array(75, self::$use_function_top) ? u('Custo/view').'?id='.$crm_id : u('Custo/index');
    if($result['code']){
      $this->success($result['msg'], $result['url']);
    }else{
      $this->error($result['msg']);
    }
  }
  public function check_typeid($typeid){
    if(!in_array($typeid, [1,2,3,4,5,6])){
      $this->error('修改內容有誤');
    }
  }

  // CRM詳細內容頁 以ajax取得客戶分類資料
  public function ajax_init_crm_right_data(){
    $return_data['crm_cum_type'] = D('crm_cum_type')->where("status=1")->order('sort asc, id asc')->select();
    $return_data['crm_cum_cat'] = ContractHelper::get_crm_cum_cat(0);
    $return_data['crm_cum_cat_pay'] = ContractHelper::get_crm_cum_cat(1);
    // 客戶等級
    $return_data['levels'] = D()->query("SELECT `id`,`name` FROM crm_cum_level WHERE `status`=1 ORDER BY `id` ASC");
    // 客戶來源
    $return_data['crm_cum_sourse'] = D()->query("select `id`,`name` from crm_cum_sourse where `status`=1");
    // 產業大項
    $return_data['crm_industr'] = D("crm_crm")->where("`industr`!= ''")->field("industr")->group("industr")->select();
    $this->ajaxReturn($return_data);
  }
  // CRM詳細內容頁 ajax取得單一客戶資料
  public function ajax_crm_one_data(){
    $return_data = CustoHelper::get_crm_rightdata($_POST, self::$chats_page_count);
    $return_data['crmChatqulity'] = D('crm_chatqulity')->where('`status` = 1')->select(); //訪談品質

    $createtime = $return_data['newbier']['createtime'];
    $return_data['newbier']['createtime_format'] = $createtime ? date('Y-m-d', $createtime) : "";
    
    $return_data['eip_user'] = D('eip_user')->field('id, name')->where('status=1 AND id !='.self::$top_adminid.' AND is_job=1')->select();
    if(isset($_POST['id'])){
      if($_POST['id']=='-1'){ /*新增客戶時要撈取資料，預設業務為操作者*/
        $return_data['newbier']['wid'] = session('eid');
      }
    }
    $this->ajaxReturn($return_data);
  }
  // CRM詳細內容頁 ajax取得單一客戶資料訪談紀錄
  public function ajax_get_chats(){
    $crmId = $_POST['crmId'];
    $page = $_POST['page'];
    $search_text = $_POST['search_text'];
    $return_data = CustoHelper::get_chats($crmId, $page, $search_text, self::$chats_page_count);
    $this->ajaxReturn($return_data);
  }
  // CRM詳細內容頁 操作面板篩選客戶資料(新進、潛在、成交、搜尋)
  public function ajax_get_crm_list(){
    $_SESSION['val'] = $_POST["val"] ;
    $_SESSION['key'] = $_POST["key"] ;	
    if(isset($_POST['key']) && ($_POST['val']!="")){
      $_POST[$_POST['key']] = $_POST['val'];
    }

    $_POST['p'] = $_POST['p'] ? $_POST['p'] : 1;
    $_POST['teamid'] = $_POST['teamid'] ? $_POST['teamid'] : 0;
    $return_data = $this->search_customer($_POST, self::$crm_panel_page_count);
    
    // 各等級統計
    $level_and_count_data = CustoHelper::get_level_and_count($_POST);
    $return_data['levels'] = $level_and_count_data['levels'];
    $return_data['levels_count'] = $level_and_count_data['levels_count'];

    /*計算新進客戶倒數天數*/
    foreach ($return_data['crmlist'] as $key => $value) {
      if($value['typeid']==1 && $value['newclient_date']){
        $user = D('eip_user')->where('id="'.$value['wid'].'"')->find();
        $user_data = D('eip_user_data')->where('eid="'.$value['wid'].'"')->find();
        if($user){
          if($user['is_anysis']==1){
            $datetime1 = date_create( date('Y-m-d', time()) );
            $new_limit = $user_data['new_date'] ? $user_data['new_date'] : '0';
            $datetime2 = date_create( date('Y-m-d', strtotime($value['newclient_date'].' +'.$new_limit.'days')) );
            $interval = date_diff($datetime1, $datetime2); 
            $return_data['crmlist'][$key]['datecount'] = $interval->format('%a'); /*%R%a*/
            continue;
          }
        }
      }
      $return_data['crmlist'][$key]['datecount'] = ''; 
    }

    $crm_level_description = [];
    if( in_array($_POST['typeid'], [1,2,3]) ){
      $crm_level_description = D('custo_level'.$_POST['typeid'])->where('id="'.self::$top_adminid.'"')->find();
    }
    $return_data['crm_level_description'] = $crm_level_description;

    $this->ajaxReturn($return_data);
  }
  // CRM詳細內容頁 操作面板篩選客戶資料(預約、對話)
  public function ajax_get_conversation(){
    $return_data = CustoHelper::get_conversation($_POST , self::$crm_panel_page_count);
    $this->ajaxReturn($return_data);
  }
  // CRM詳細內容頁 操作面板篩選客戶資料(小事)
  public function ajax_get_smallthings(){
    $return_data = CustoHelper::get_smallthings($_POST, self::$crm_panel_page_count);
    $this->ajaxReturn($return_data);
  }

  // 串接再行銷：寄送對象
  public function ajax_get_crm_people(){
    $return_data = $this->search_customer($_POST);
    $crmlist = $return_data['crmlist'];

    $people_list = [];
    foreach($crmlist as $key=>$value){
      $company_name = CustoHelper::get_crm_show_name($value['id']);

      /*加入公司*/
        array_push($people_list, [
          'id'    => $value['id'],
          'name'  => $company_name,
          'phone' => $value['comphone'],
          'mobile'=> $value['commobile'],
          'mail'  => $value['commail'],
        ]);

      /*加入負責人*/
        if(self::$system_parameter['負責人']!="" && $value['bossname']){ /*如果負責人沒被隱藏 且 有輸入名稱*/
          array_push($people_list, [
            'id'    => $value['id'].'_boss',
            'name'  => $company_name.':'.$value['bossname'],
            'phone' => $value['bossphone'],
            'mobile'=> $value['bossmobile'],
            'mail'  => $value['bossmail'],
          ]);
        }

      /*加入聯絡人*/
        if(self::$system_parameter['聯絡人']!=""){ /*如果聯絡人沒有被隱藏*/
          $crm_contact = D('crm_contact')->where('cumid="'.$value['id'].'" AND cname!=""  AND cname is not null')
                            ->order('radio desc, id desc')->select();
          foreach ($crm_contact as $k_c => $v_c) {
            array_push($people_list, [
              'id'    => $v_c['id'].'_contact',
              'name'  => $company_name.':'.$v_c['cname'],
              'phone' => $v_c['phone'],
              'mobile'=> $v_c['mobile'],
              'mail'  => $v_c['mail'],
            ]);
          }
        }
    }

    $this->ajaxReturn($people_list);
  }
  // 串接再行銷：取得搜尋html
  public function get_search_html(){
    $return_data = $this->search_customer($_GET, 1);
    $return_data = $this->merge_industr_with_search_customer($_GET, $return_data);

    $this->display('search_setting');
  }

  // 取得客戶資料(搜尋客戶)
  public function search_customer($get_data, $page_count=NULL, $type_count=true){
    $acc = parent::get_my_access(); /*取得我的權限*/
    if(!$acc){ $this->error('此帳號無對應權限，請重新登入'); }
    // dump($acc);
    $return_data = CustoHelper::search_customer($acc, $get_data, $page_count, $type_count);

    return $return_data;
  }
  public function merge_industr_with_search_customer($get_data, $return_data){
    $return_data = CustoHelper::merge_industr_with_search_customer($get_data, $return_data);
    /*篩選所需的變數*/
      $this->assign('levels', $return_data['levels']);

      $this->assign('industr', $return_data['industr']);
      $this->assign('industr2_search', $return_data['industr2_search']);
  
      $this->assign('country',$return_data['country']);
      $this->assign('district',$return_data['district']);

      $this->assign('crm_cum_pri',$return_data['crm_cum_pri']);

      $this->assign('eip_user',$return_data['eip_user']);
  return $return_data;
  }

  public function add_crm_cum_cat_unit_crm(){
    $crm_id = $_POST['crm_id'];
    $crm_cum_cat_unit_ids = $_POST['crm_cum_cat_unit_ids'];
    $this->check_crm_access('custo', 'edi', [$crm_id]);

    $result = ProductHelper::add_crm_cum_cat_unit_crm($crm_id, $crm_cum_cat_unit_ids);
    if($result){
      $this->success('操作成功');
    }else{
      $this->error('資料重複或發生錯誤');
    }
  }
  public function delete_crm_cum_cat_unit_crm(){
    $crm_id = $_POST['crm_id'];
    $crm_cum_cat_unit_ids = $_POST['crm_cum_cat_unit_ids'];
    $this->check_crm_access('custo', 'edi', [$crm_id]);

    $result = ProductHelper::delete_crm_cum_cat_unit_crm($crm_id, $crm_cum_cat_unit_ids);
    if($result){
      $this->success('操作成功');
    }else{
      $this->error('無資料須刪除或發生錯誤');
    }
  }

  // 檢查CRM操作權限，參數：權限種類(crm、event...)、哪種類型(新增、編輯...)、要操作的客戶ids、組別id
  public function check_crm_access($acc_type,  $acc_method, $crms=[], $teamid=0){
    $user_id = session('eid');
    $top_team = D('eip_team')->where('(boss_id='.$user_id.' OR childeid like "%'.$user_id.'%") AND id='.self::$top_teamid)->find();
    if($top_team){ return; } /*此帳號被設定於最高權限組，無須檢查權限*/

    /*檢查自己的權限*/
    $user = D('eip_user')->where('id="'.$user_id.'" and is_job=1')->find();
    if(!$user){ $this->error('無此帳號，請重新登入'); }
    if($user['usergroupid']==1){ return; }/*此帳號是管理員權限，無須檢查權限*/

    $acc = parent::get_my_access(); /*取得我的權限*/
    if(!$acc){ $this->error('此帳號無對應權限，請重新登入'); }

    if($acc[$acc_type."_".$acc_method] == '0'){ $this->error('您沒有此操作權限'); }
    if($acc[$acc_type."_all"]=='1'){ return; } /*有看全部權限，略過逐個客戶檢查權限*/
    
    /*逐個客戶檢查權限*/
    foreach($crms as $crmsid){
      if($crmsid==self::$our_company_id || $crmsid==-1){ continue; } /*此客戶是我們公司 或 -1，無須檢查協同處理*/
      $crm = D('crm_crm')->where("id='".$crmsid."' ")->find();
      if(!$crm){ $this->error('操作的客戶不存在'); }
      if($crm['typeid']=='5'){ continue; } /*客戶屬於開放客戶，無需檢查協同處理*/

      /*組織客戶的協同人員*/
      $crm_cooperate = [];
      $crm_cum_pri = D('crm_cum_pri')->where("status=1")->select();
      foreach($crm_cum_pri as $ccp_k=>$ccp_v){
        array_push($crm_cooperate, $crm[$ccp_v['ename']]);
      }

      if( in_array($user_id, $crm_cooperate) ){ /*你是此客戶的協同處理人員*/
        continue;
      }

      if($teamid!=0 && $teamid!=''){/*有以組別查看*/
        /*查找你是否在此組別*/
        $team = D('eip_team')->where('boss_id='.$user_id.' OR childeid like "%'.$user_id.'%" AND id='.$teamid)->find();
        if(!$team){ $this->error('無此組別'); }				
        if(in_array($team['boss_id'], $crm_cooperate) && $team['edit_leader_customers']==1){ /*此客戶屬於組長 且 可編輯組長客戶*/
          continue;
        }
        else if($team['edit_member_customes']==1){ /*可編輯組員客戶*/
          $childeids = explode('、', $team['childeid']);
          $in_crm_cooperate = false;
          foreach ($childeids as $childeid) {
            $childeid = str_replace('"', '', $childeid);
            if( in_array($childeid, $crm_cooperate) ){ /*此客戶屬於某組員*/
              $in_crm_cooperate = true;
              break;
            }
          }
          if($in_crm_cooperate){
            continue;
          }
        }
      }

      $this->error("您不屬於客戶的協同處理人員，無法操作");
    }
  }


  // 串接電話系統 撥出可掛斷電話
  public function phone_call(){
    $_POST['apiKey'] = UCRM_SERVER_APIKEY;
    $_POST['callerKey'] = session('userAccount');
    $_POST['callerKeyType'] = 'loginAccount';
    $_POST['destNumber'] = substr($_POST['destNumber'], 1);
    $para = json_encode($_POST, JSON_UNESCAPED_UNICODE);
    // dump($para);exit;
    $phone_call_url = UCRM_SERVER_HTTP_DOMAIN.'/ucrm/api/phone/dialOutbound?para='.$para;
    // dump($phone_call_url);
    $result = Common::http_request($phone_call_url);
    // dump($result);exit;
    $result = json_decode($result, true);
    if($result['returnCode']==0){
      if($result['callId']){
        $this->success($result['callId']);
      }else{
        $this->error('撥話失敗');
      }
    }else{
      $this->error($result['returnInfo']);
    }
  }
  // 串接電話系統 掛斷電話
  public function phone_call_off(){
    $_POST['apiKey'] = UCRM_SERVER_APIKEY;
    $para = json_encode($_POST, JSON_UNESCAPED_UNICODE);
    $phone_call_off_url = UCRM_SERVER_HTTP_DOMAIN.'/ucrm/api/phone/hangupCall?para='.$para;
    $result = Common::http_request($phone_call_off_url);
    // dump($result);exit;
    $result = json_decode($result, true);
    if($result['returnCode']==0){
      $this->success('已掛斷');
    }else{
      $this->error($result['returnInfo']);
    }
  }
  // 串接電話系統 來電顯示
  public function phone_view(){
    $namber = $_GET['q'];
    $namber = str_replace(' ', '', $namber);
    $namber = str_replace('#', '', $namber);
    $namber = str_replace('%', '', $namber);
    $namber = str_replace('-', '', $namber);
    $namber = str_replace('+', '', $namber);
    $namber = str_replace('(', '', $namber);
    $namber = str_replace(')', '', $namber);

    $search_data = [];
    $search_data = ['bossphone'=>$namber, 'typeid'=>-2];
    $crmlist = $this->search_customer($search_data, 1)['crmlist'];
    // dump($crmlist);
    if($crmlist){
      $this->redirect('Custo/view?id='.$crmlist[0]['id']);
    }

    $search_data = [];
    $search_data = ['bossmobile'=>$namber, 'typeid'=>-2];
    $crmlist = $this->search_customer($search_data, 1)['crmlist'];
    if($crmlist){
      $this->redirect('Custo/view?id='.$crmlist[0]['id']);
    }
    // dump($crmlist);
    // exit;
    $this->error('無此'.self::$system_parameter["客戶"].'資料');
  }
}
?>