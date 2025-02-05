<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use Photonic\Common;

  class KmController extends GlobalController 
  {
    private $daoModel, $fileCode;

    function _initialize(){
      parent::_initialize();
      if(!isset($_GET['type'])){ $this->error('網址有誤'); }
      $km_type = D('km_types')->where('description="'.$_GET['type'].'"')->find();
      parent::check_has_access($km_type['codenamed'], 'red');
      
      $powercat_id = $km_type['parent_id'];
      $km_powercat = D('powercat')->where('id='.$powercat_id)->find();
      $this->assign("page_model_link", $km_powercat['link']);
      $this->assign("page_model", $km_powercat['title']);

      $this->fileCode = $km_type['description'];
      $this->assign("fileCode", $this->fileCode); // 文章編碼

      $this->daoModel = strtolower($km_type['codenamed']);
      $this->assign("daoModel", $this->daoModel); // 權限前綴
      $this->assign("right_new", $this->daoModel."_new");
      $this->assign("right_edi", $this->daoModel."_edi");
      $this->assign("right_hid", $this->daoModel."_hid");
      $this->assign("right_del", $this->daoModel."_del");
      $this->assign("right_all", $this->daoModel."_all");

      $this->assign("myparent", $powercat_id); // 右上選單子階層
      $this->assign('page_title', $km_type['title']);
      $this->assign('page_title_link_self', U(CONTROLLER_NAME.'/index', ['type'=>$km_type['description']]));
      $this->assign('page_title_active', 'km_'.$km_type['id']); /*右上子選單active*/

      $acc= parent::get_my_access();
      if($_SESSION['eid'] != self::$top_adminid){
        $access = " and (
                  f.creater = '".$_SESSION['eid']."' OR 
                  f.access_type = 'all' OR (
                    f.access_type = 'on' AND 
                    f.apart like '%\"".$_SESSION['apartId']."\"%') OR (
                      (
                        f.access_type = 'on' OR 
                        f.access_type = 'own'
                      ) and 
                      f.access like '%\"".$_SESSION['eid']."\"%'
                    )
                  )";
      }
      else{
        $access = " and true";
      }

      // 處理第一階層
      $sort = Common::get_child_file($this->fileCode, 0);
      foreach ($sort as $k_t => $v_t) {
        if($v_t['file_layer']==0){ /*是文章*/
          $sort[$k_t]['sub_file'] = [];
        }
        else{ /*是階層*/
          /*第二階層*/
          $sort[$k_t]['sub_file'] = Common::get_child_file($this->fileCode, $v_t['id']);
          foreach ($sort[$k_t]['sub_file'] as $k_s => $v_s) {
            if($v_s['file_layer']==0){ /*是文章*/
              $sort[$k_t]['sub_file'][$k_s]['sub_file'] = [];
            }else{ /*是階層*/
              /*第三階層*/
              $sort[$k_t]['sub_file'][$k_s]['sub_file'] = Common::get_child_file($this->fileCode, $v_s['id']);
            }
          }
        }
      }
      // dump($sort);exit;
      $this->assign("acc",$acc);
      $this->assign("sort",$sort);

      $this->assign("ACTION_NAME", ACTION_NAME);
    }

    function index(){
      $this->redirect('Km/search', ['type'=>$_GET['type']]);
    }
    function search(){
      if($_SESSION['eid'] != self::$top_adminid){
        $access = " AND (creater = '".$_SESSION['eid']."' or access_type = 'all' or (access_type = 'on' and apart  like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%'))";
      }
      else{
        $access = " AND true";
      }
      if($_GET['title'] != ''){
        $_GET['title'] = trim($_GET['title']);
        $where .= " AND ( title like '%".$_GET['title']."%' or note like '%".$_GET['title']."%')";
      }else{
        $where .= " AND true";
      }
      if($this->fileCode=='FI'){ /*是我的文件(含共用文件)*/
        $where .= " AND creater='".$_SESSION['adminId']."'"; /*看自己的*/
      }
      $count = M("file")->where("
                          showtime != 'stop' and number = '".$this->fileCode."' and 
                          status = '1' and start_time <= ".time()." and end_time >=".time()." ".$where.$access
                        )->count();
      $Page = new \Think\Page($count, 20);
      $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
      $Page->setConfig('prev',"上一頁");
      $Page->setConfig('next',"下一頁");
      $Page->setConfig('first',"第一頁");
      $Page->setConfig('last',"最後一頁 %END% ");
      $show = $Page->show();
      $file = M("file")->field("file.*,eip_user.name")
                      ->where("
                        showtime != 'stop' and number = '".$this->fileCode."' and 
                        file.status = '1' and start_time <= ".time()." and end_time >=".time()." ".$where.$access
                      )
                      ->join("left join eip_user on file.creater = eip_user.id")
                      ->limit($Page->firstRow.','.$Page->listRows)
                      ->order("type desc,id desc")->select();
      foreach ($file as $key => $value) {
        $file[$key]['location'] = $this->get_file_location($value);
      }
      $this->assign("show", $show);
      $this->assign("file", $file);
      $this->display('Km/search');
    }
    function others(){
      if($_SESSION['eid'] != self::$top_adminid){
        // $where = " and creater = '".$_SESSION['eid']."'";
        $where = " AND (creater = '".$_SESSION['eid']."' or access_type = 'all' or (access_type = 'on' and apart  like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%'))";
      }
      else{
        $where = " and true";
      }
      if($_GET['title'] != '')
        $where .= " and title like '%".$_GET['title']."%'";
      else
        $where .= " and true";
      $count = M("file")
      ->where("(showtime = 'stop' or start_time >= ".time()." or end_time <=".time().") and number = '".$this->fileCode."' and status = '1'".$where)
      ->count();
      $Page = new \Think\Page($count,20);
      $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
      $Page->setConfig('prev',"上一頁");
      $Page->setConfig('next',"下一頁");
      $Page->setConfig('first',"第一頁");
      $Page->setConfig('last',"最後一頁 %END% ");
      $show = $Page->show();
      $file = M("file")->field("file.*,eip_user.name")
      ->where("(showtime = 'stop' or start_time >= ".time()." or end_time <=".time().") and number = '".$this->fileCode."' and file.status = '1'".$where)
      ->join("left join eip_user on file.creater = eip_user.id")
      ->limit($Page->firstRow.','.$Page->listRows)->order("type desc,id desc")->select();
      $this->assign("show",$show);
      $this->assign("file",$file);
      $this->display('Km/others');
    }
    function trash(){
      parent::check_has_access($this->daoModel, 'hid');

      if($_GET['title'] != ''){
        $where = " and title like '%".$_GET['title']."%'";
      }
      else{
        $where = " and true";
      }
      $count = M("file")
      ->where("status = '0' and number = '".$this->fileCode."'".$where)
      ->count();
      $Page = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
      $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
      $Page->setConfig('prev',"上一頁");
      $Page->setConfig('next',"下一頁");
      $Page->setConfig('first',"第一頁");
      $Page->setConfig('last',"最後一頁 %END% ");
      $show = $Page->show();
      $file = M("file")->field("file.*,eip_user.name")
      ->where("file.status = '0' and number = '".$this->fileCode."'".$where)
      ->join("left join eip_user on file.creater = eip_user.id")
      ->limit($Page->firstRow.','.$Page->listRows)->order("type desc,id desc")->select();
      $this->assign("show",$show);
      $this->assign("file",$file);
      $this->display('Km/trash');
    }
    function trash_read(){
      parent::check_has_access($this->daoModel, 'hid');

      $file = M("file")->where("id = ".$_GET['id'])->select();
      $file[0]['file'] = json_decode($file[0]['file'],true);
      $message = M("file_message")->where("fid = ".$_GET['id'])
      ->join("left join eip_user on file_message.user_id = eip_user.id")
      ->order("time DESC")->select();
      $read = json_decode($file[0]['read_person'],true);
      $file[0]['apart'] = json_decode($file[0]['apart'],true);
      $file[0]['access'] = json_decode($file[0]['access'],true);
      if($file[0]['access_type'] == 'all'){
        $read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->count();
        $read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->select();
        $read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->getField('id', true);
      }
      else if($file[0]['access_type'] == 'on'){
        $elseid = " and (false";
        foreach($file[0]['apart'] as $k_apart => $v_apart){
          if($v_apart != self::$top_adminid){
            $elseid .= " or apartmentid = '".$v_apart."'";
          }
        }
        foreach($file[0]['access'] as $key => $vo){
          if($v_apart != self::$top_adminid){
            $elseid .= " or id = '".$vo."'";
          }
        }
        $elseid .= ")";
        $read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->count();
        $read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->select();
        $read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->getField('id', true);
      }
      
      $read_real= [];
        foreach($read as $k => $v){
          if(in_array($k,$read_person['all_id'])){
            // array_push($read_real, $k);
            $read_real[$k] = $v;
          }
        }	
      $read = $read_real;
      
      $read_num['read'] = count($read);
      $read_num['unread'] = $read_num['all'] - $read_num['read'];
      $read_num['rate'] = round($read_num['read'] / $read_num['all'] * 100,2);
      $read_where = "and (false";
      foreach($read as $k => $v){
        $read_where .= " or id = '".$v."'";
      }
      $read_where .= ")";
      $unread_where = "and (false";
      foreach($read_person['all'] as $key => $vo){
        foreach($read as $k => $v){
          $read_where .= " or id = '".$v."'";
          if($v == $vo['id']){
            $count[$key] = 1;
          }
        }
        if($count[$key] != 1)
          $unread_where .= " or id = '".$vo['id']."'";
      }
      $unread_where .= ")";
      $read_person['read'] = M("eip_user")->field("name")->where("is_job = '1' and status = '1'".$read_where)->select();
      $read_person['unread'] = M("eip_user")->field("name")->where("is_job = '1' and status = '1'".$unread_where)->select();

      $count = M("file_message")->where("fid = ".$_GET['id'])->count();
      $this->assign("file",$file[0]);
      $this->assign("message",$message);
      $this->assign("count",$count);
      $this->assign("read_num",$read_num);
      $this->assign("read_person",$read_person);
      $this->display('Km/trash_read');
    }
    function read(){
      if($_SESSION['eid'] != self::$top_adminid){
        $access = " and (creater = '".$_SESSION['eid']."' or access_type = 'all' or (access_type = 'on' and apart  like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%'))";
      }
      else{
        $access = " and true";
      }
      $file = M("file")->field('file.*, eip_user.name user_name')
                      ->join('left join eip_user on eip_user.id=file.creater')
                      ->where("file.id = ".$_GET['id'])
                      ->select();
      $file[0]['file'] = json_decode($file[0]['file'],true);
      $message = M("file_message")->where("fid = ".$_GET['id'])
                                  ->join("left join eip_user on file_message.user_id = eip_user.id")
                                  ->order("time DESC")->select();
      $read = json_decode($file[0]['read_person'],true);
      $file[0]['apart'] = json_decode($file[0]['apart'],true);
      $file[0]['access'] = json_decode($file[0]['access'],true);
      if($file[0]['access_type'] == 'all'){
        $read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->count();
        $read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->select();
        $read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->getField('id', true);
      }
      else if($file[0]['access_type'] == 'on'){
        $user_apart = M("eip_user")->field("apartmentid")->where("id = ".$_SESSION['eid'])->select();
        $elseid = " and (false";
        foreach($file[0]['apart'] as $k_apart => $v_apart){
          $elseid .= " or apartmentid = '".$v_apart."'";
          if($v_apart == $user_apart[0]['apartmentid']){
            $creater_apart = 1;
          }
        }
        foreach($file[0]['access'] as $key => $vo){
          $elseid .= " or id = '".$vo."'";
          if($vo == $_SESSION['eid']){
            $creater = 1;
          }
        }
        $elseid .= ")";
        $read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->count();
        $read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->select();
        $read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->getField('id', true);
      }
      
      /*紀錄讀取人員*/
      if($file[0]['start_time']<=time()){ /*小於發佈時間不更新*/
        $read[$_SESSION['eid']] = $_SESSION['eid'];
        $data['read_person'] = json_encode($read);
        M("file")->where("id = ".$_GET['id'])->data($data)->save();
      }
      
      $read_real= [];
      foreach($read as $k => $v){
        if(in_array($k,$read_person['all_id'])){
          // array_push($read_real, $k);
          $read_real[$k] = $v;
        }
      }	
      $read = $read_real;

      $read_num['read'] = count($read);
      $read_num['unread'] = $read_num['all'] - $read_num['read'];
      $read_num['rate'] = round($read_num['read'] / $read_num['all'] * 100,2);
      $read_where = "and (false";
      foreach($read as $k => $v){
        $read_where .= " or id = '".$v."'";
      }
      $read_where .= ")";
      $unread_where = "and (false";
      foreach($read_person['all'] as $key => $vo){
        foreach($read as $k => $v){
          $read_where .= " or id = '".$v."'";
          if($v == $vo['id']){
            $count[$key] = 1;
          }
        }
        if($count[$key] != 1)
          $unread_where .= " or id = '".$vo['id']."'";
      }
      $unread_where .= ")";
      $read_person['read'] = M("eip_user")->field("name")->where("is_job = '1' and status = '1'".$read_where)->select();
      $read_person['unread'] = M("eip_user")->field("name")->where("is_job = '1' and status = '1'".$unread_where)->select();
      
      $pageup = M('file')->field("id,type")
                        ->where("id < '".$_GET['id']."' and showtime != 'stop' and number = '".$this->fileCode."' and status = '1' and start_time <= ".time()." and end_time >=".time()."".$access)
                        ->order("id desc")->limit("0,1")->select();
      $pagedown = M('file')->field("id,type")
                          ->where("id > '".$_GET['id']."' and showtime != 'stop' and number = '".$this->fileCode."' and status = '1' and start_time <= ".time()." and end_time >=".time()."".$access)
                          ->order("id asc")->limit("0,1")->select();
      $count = M("file_message")->where("fid = ".$_GET['id'])->count();

      $this->assign("leftview",$leftview);
      $this->assign("file",$file[0]);
      $this->assign("message",$message);
      $this->assign("count",$count);
      $this->assign("read_num",$read_num);
      $this->assign("read_person",$read_person);
      $this->assign("pageup",$pageup[0]);
      $this->assign("pagedown",$pagedown[0]);

      $this->display('Km/read');
    }

    /*編輯/新增文章頁面*/
    function edit(){
      $_SESSION['note'] = "";
      if($_GET['id']=='' || $_GET['id']=='0'){
        $parent_id = isset($_GET['parent_id']) ? $_GET['parent_id'] : 0;

        $number = $this->fileCode;
        $date = date("Ymd",time());
        $num = M("file")->where("date = '".strtotime($date)."' and number = '".$this->fileCode."'")->count();
        $file = M("file")->field("num")->where("date = '".strtotime($date)."' and number = '".$this->fileCode."'")->select();
        $num = $file[$num-1]['num'];
        $eip_user = M("eip_user")->where("is_job = 1")->select();
        $apart = M("eip_apart")->where("status = 1")->select();
        foreach($apart as $key => $vo){
          $user[$vo['id']] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."' and apartmentid = '".$vo['id']."'")->select();
        }
        $this->assign("num",$num+1);
        $file[0]['apart'] = "[]";
        $file[0]['access'] = "[]";
        $file[0]['end_time'] = "9999999999";
        $this->assign("showtime",'1');
        $this->assign("access_type",'1');
        $this->assign("file",$file[0]);
        $this->assign("edit_code", '');
      }
      else{
        $file = M("file")->field("file.*,eip_user.name")->where("file.id = ".$_GET['id'])
                 ->join("left join eip_user on file.creater = eip_user.id")->select();
        $parent_id = $file ? $file[0]['parent_id'] : 0;
        $file[0]['file'] = json_decode($file[0]['file'],true);
        $file[0]['update_person'] = json_decode($file[0]['update_person'],true);
        $file[0]['update_time'] = json_decode($file[0]['update_time'],true);
        $count = count($file[0]['update_person']);
        $number = $file[0]['number'];
        $date = date("Ymd",$file[0]['date']);
        $num = $file[0]['num'];
        $eip_user = M("eip_user")->where("is_job = 1")->select();
        $apart = M("eip_apart")->where("status = 1")->select();
        foreach($apart as $key => $vo){
          $user[$vo['id']] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."' and apartmentid = '".$vo['id']."'")->select();
        }
        $read = json_decode($file[0]['read_person'],true);
        $file[0]['apart'] = json_decode($file[0]['apart'],true);
        $file[0]['access'] = json_decode($file[0]['access'],true);
        if($file[0]['access_type'] == 'all'){
          $read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->count();
          $read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->getField('id', true);
        }
        else if($file[0]['access_type'] == 'on'){
          $elseid = " and (false";
          foreach($file[0]['apart'] as $k_apart => $v_apart){
            $elseid .= " or apartmentid = '".$v_apart."'";
          }
          foreach($file[0]['access'] as $key => $vo){
            $elseid .= " or id = '".$vo."'";
          }
          $elseid .= ")";
          $read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->count();
          $read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->getField('id', true);
        }
        
        $read_real= [];
        foreach($read as $k => $v){
          if(in_array($k,$read_person['all_id'])){
            // array_push($read_real, $k);
            $read_real[$k] = $v;
          }
        }	
        $read = $read_real;
        
        $read_num['read'] = count($read);
        $read_num['unread'] = $read_num['all'] - $read_num['read'];
        $read_num['rate'] = round($read_num['read'] / $read_num['all'] * 100,2);
        $file[0]['apart'] = json_encode($file[0]['apart'],true);
        $file[0]['access'] = json_encode($file[0]['access'],true);
        $this->assign("file",$file[0]);
        $this->assign("edit_code",$file[0]['edit_code'] ?? '');
        $this->assign("num",$num);
        $this->assign("read_num",$read_num);
        if($file[0]['showtime'] == 'now')
          $this->assign("showtime",'1');
        else if($file[0]['showtime'] == 'chos_time')
          $this->assign("showtime",'2');
        else
          $this->assign("showtime",'3');
        if($file[0]['access_type'] == 'own')
          $this->assign("access_type",'1');
        if($file[0]['access_type'] == 'all')
          $this->assign("access_type",'2');
        if($file[0]['access_type'] == 'on')
          $this->assign("access_type",'3');
      }
      // 前次勾選指定人員
      $pre_share = M("file")->field('id,access_type,access,apart')
                  ->where("creater=".$_SESSION['adminId'].' AND number="'.$this->fileCode.'" AND status!=0')
                  ->order('id desc')->limit(1)->find();
      // dump($pre_share);exit;
      if($pre_share){
        $pre_share['apart'] =  json_decode($pre_share['apart']);
        $pre_share['access'] =  json_decode($pre_share['access']);
      }
      $this->assign("pre_share", json_encode($pre_share));
      //dump($pre_share);exit;
      $this->assign("file_click",$_SESSION['file_click']);
      $this->assign("number",$number);
      $this->assign("date",$date);
      $this->assign("apart",$apart);
      $this->assign("user",$user);
      $this->assign("count",$count);

      $parent_id = $parent_id ? $parent_id : 0;
      $this->assign("parent_id", $parent_id);

      $this->display('Km/edit');
    }
    /*儲存文章資料(新增/編輯)*/
    function addfile($redirect=true){
      $_SESSION['note'] = $_POST['note'];
      
      if($_POST['title'] == '')
        $this->error("標題不可為空");
      if($_POST['access_type'] == 'on' && ($_POST['name'] == '' && $_POST['apart'] == ''))
        $this->error("閱讀權限不可為空");
      if($_POST['showtime'] == 'chos_time' && ($_POST['start_time'] == '' || ($_POST['end_time'] == '' && $_POST['time_length'] == '2')))
        $this->error("指定日期不可為空");
      
      if( !isset($_POST['parent_id']) ){
        $this->error("請指定文章階層");
      }
      $_POST['parent_id'] = $_POST['parent_id'] ? $_POST['parent_id'] : 0;
      
      $_POST['date'] = strtotime($_POST['date']);
      $_POST['note'] = save_img_in_content($_POST['note']); //將內容內的base64圖片上傳到主機

      if($_POST['access_type'] == 'on'){
        $_POST['apart'] = json_encode($_POST['apart']);
        $_POST['access'] = json_encode($_POST['name']);
      }
      else if($_POST['access_type'] == 'own'){
        $_POST['access'] = json_encode([$_SESSION['eid']]);
      }
      else{
        $all = '';
        $_POST['access'] = json_encode($all);
      }
      if($_POST['showtime'] == 'chos_time'){
        $_POST['start_time'] = strtotime($_POST['start_time']);
        if($_POST['time_length'] == 2)
          $_POST['end_time'] = strtotime($_POST['end_time']);
        else
          $_POST['end_time'] = 9999999999;
      }
      else if($_POST['showtime'] == 'now'){
        $_POST['showtime'] = 'chos_time';
        $_POST['start_time'] = time();
        $_POST['end_time'] = 9999999999;
      }
      else{
        $_POST['start_time'] = 9999999999;
        $_POST['end_time'] = 9999999999;
      }
      $_POST['type'] = strtotime(date("Y-m",time()));
      $_POST['creater'] = $_SESSION['eid'];
      //$_POST['read_person'] = $_POST['access'];
      $_POST['read_person'] = null;

      if( !isset($_POST['order_id']) ){ $_POST['order_id'] = 0; }
      $_POST['order_id'] = $_POST['order_id'] ? $_POST['order_id'] : 0;
      
      if($_POST['id']=='' || $_POST['id']=='0'){ /*新增*/
        parent::check_has_access($this->daoModel, 'new');

        foreach($_FILES['file']['name'] as $key => $vo){
          $_POST['file_name'][$key] = $vo;
          if($vo){
            $disname='Uploads/fig/';
            $file=parent::uploadfile($disname);
            $link='<a href="'.$file.'" download="'.$_POST['file_name'][$key].'"><img src="/Public/qhand/images/save.png" />'.$_POST['file_name'][$key].'</a>';
            $_POST['file'][$key] = $link;
          }
        }
        $_POST['file'] = json_encode($_POST['file'],true);

        // 改排序
        parent::change_order(
          $order_table = 'file', 
          $order_column ='order_id', 
          $new_order = $_POST['order_id'], 
          $related_rows = 'number="'.$_POST['number'].'" AND parent_id="'.$_POST['parent_id'].'"'
        );

        $id = M("file")->data($_POST)->add();
        if($id){
          parent::error_log("新增文章:{$id}, 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
          $this->success("更新成功",u('Km/read',["type"=>$this->fileCode]).'?id='.$id);
        }
        else{
          $this->error("新增失敗");
        }
      }else{ /*編輯*/
        parent::check_has_access($this->daoModel, 'edi');

        $id = $_POST['id'];
        $files = M("file")->where("id = ".$id)->select()[0];

        /*判斷是否可以編輯*/
        $edit_code = $_POST['edit_code'];
        if($edit_code!=$files['edit_code']){
          $this->error("有其他使用者已更新此文章，請重新整理頁面後再進行修改");
        }
        $_POST['edit_code'] = Common::geraHash(32);

        $_POST['file'] = json_decode($files['file']); 
        // 取得最後一個檔案index+1
        if(count($_POST['file'])>0){
          end($_POST['file']);
          $files_num = (Int)key($_POST['file'])+1;
        }else{
          $files_num = 0;
        }

        // 刪除舊檔案
        foreach($_POST['delete_file'] as $key => $vo){
          if($vo == '1')
            unset($_POST['file']->$key);
        }
        foreach($_FILES['file']['name'] as $key => $vo){
          $_POST['file_name'][$key + $files_num] = $vo;
          if($vo){
            $disname='Uploads/fig/';
            $file=parent::uploadfile($disname);
            $link='<a href="'.$file.'" download="'.$_POST['file_name'][$key + $files_num].'"><img src="/Public/qhand/images/save.png" />'.$_POST['file_name'][$key + $files_num].'</a>';
            $object_index = $key + $files_num;
            $_POST['file']->$object_index = $link;
          }
        }

        $_POST['file'] = json_encode($_POST['file'],true);
        $update_person = M("eip_user")->field("name")->where("id = ".$_SESSION['eid'])->select()[0];
        $update = M("file")->field("update_person,update_time")->where("id = ".$id)->select()[0];
        //$count = M("file")->where("id = ".$id)->count();
        $update['update_person'] = json_decode($update['update_person'],true);
        $update['update_time'] = json_decode($update['update_time'],true);
        $count = count($update['update_person']);
        $update['update_person'][$count + 1] = $update_person['name'];
        $update['update_time'][$count + 1] = time();
        $_POST['update_person'] = json_encode($update['update_person']);
        $_POST['update_time'] = json_encode($update['update_time']);
        unset($_POST['id']);
        unset($_POST['creater']);
        if($files['note'] == $_POST['note']){
          if($files['showtime'] == $_POST['showtime']){
            if($files['file'] == $_POST['file']){
              $_POST['read_person'] = $files['read_person'];
            }
          }
        }
        //dump($_POST);exit;
    
        // 改排序
        parent::change_order(
          $order_table = 'file', 
          $order_column ='order_id', 
          $new_order = $_POST['order_id'], 
          $related_rows = 'number="'.$_POST['number'].'" AND parent_id="'.$_POST['parent_id'].'"'
        );
        $_POST['read_person']='{}';
        if(M("file")->where("id = ".$id)->data($_POST)->save()){
          parent::error_log("更新文章:{$id}, 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
          $this->success("更新成功",u('Km/read',["type"=>$this->fileCode]).'?id='.$id);
        }else{
          $this->error("更新失敗");
        }
      }
    }


    function aj_note(){
      if($_SESSION['note'])
        echo $_SESSION['note'];
    }

    function save_message(){
      // parent::check_has_access($this->daoModel, 'edi');

      $data = $_POST;
      $data['user_id'] = $_SESSION['eid'];
      $data['time'] = time();
      if(M("file_message")->data($data)->add()){
        $user = M("eip_user")->field("name,img")->where("id = ".$_SESSION['eid'])->select();
        $this->ajaxReturn([
          'status' => 1,
          'info' => "留言成功",
          'name' => $user[0]['name'],
          'message' => $_POST['message'],
          'time' => date("Y-m-d H:i",time()),
        ]);
      }else{
        $this->error("操作失敗");
      }
    }
    function file_del(){
      parent::check_has_access($this->daoModel, 'hid');
      $data['status'] = '0';
      if(M("file")->where("id='".$_POST['id']."'")->data($data)->save()){
        $this->success("刪除成功");
      }
      else{
        $this->error("刪除失敗");
      }
    }
    function file_action(){
      if($_POST['action'] == '請選擇'){
        $this->error("請選擇動作");
      }
      if($_POST['action'] == 'recovery'){
        parent::check_has_access($this->daoModel, 'hid');
        try{
          $data['status'] = '1';
          foreach($_POST['fid'] as $key => $vo){
            M("file")->where("id = ".$vo)->data($data)->save();
          }
          $this->success("還原成功");
        }
        catch(Exception $e){
          $this->error("還原失敗");
        }
      }
      if($_POST['action'] == 'delete'){
        parent::check_has_access($this->daoModel, 'del');
        try{
          foreach($_POST['fid'] as $key => $vo){
            M("file")->where("id = ".$vo)->delete();
          }
          $this->success("刪除完成");
        }
        catch(Exception $e){
          $this->error("還原失敗");
        }
      }
    }

    function order(){
      // dump($_POST);exit;

      $changed_ids = [];
      foreach($_POST['file_order'] as $id => $oreder_num){
        array_push($changed_ids, $id);
      }
      $changed_ids_where = $changed_ids ? ' AND file not in ('.implode(',', $changed_ids).')' : '';
      foreach($_POST['file_order'] as $id => $oreder_num){
        /*修改全體排序*/
        $file=M("file")->where("id = ".$id)->find();
        parent::change_order(
          $order_table = 'file', 
          $order_column ='order_id', 
          $new_order = $oreder_num, 
          $related_rows = 'number="'.$file['number'].'" AND parent_id="'.$file['parent_id'].'" '.$changed_ids_where
        );

        $data_sort['order_id'] = $oreder_num;
        M("file")->where("id='".$id."'")->data($data_sort)->save();
      }
      $this->success("排序完成");
    }

    function get_file_location($file, $location=[]){
      $parent = D("file")->where('id="'.$file['parent_id'].'"')->find();
      if($parent){
        array_push($location, $parent['title']);
        if($parent['parent_id']){
          if(count($location)<3){
            $location = $this->get_file_location($parent, $location);
            return $location;
          }
        }
      }

      $location = array_reverse($location);
      return implode('>', $location);
    }
  }
?>