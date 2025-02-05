<?php
namespace Trade\Controller;
use Trade\Controller\GlobalController;

use Photonic\Common;
use Photonic\CustoHelper;
use Photonic\ContractHelper;

class FigController extends GlobalController{

  public static $event_list_page_count=20;

  function _initialize(){
    parent::_initialize();
    $num=0;
    $year=date("Y",time());
    for($y = 0;$y <= 3;$y++){
      $years[$y] = $year - (3 - $y);
    }
    for($m = 1;$m <= 12;$m++){
      $months[$m] = str_pad($m,2,'0',STR_PAD_LEFT);
    }
    $this->assign("years",$years);
    $this->assign("months",$months);

    $this->assign('page_title', '事件簿');
    $this->assign('page_title_link_self', U('Fig/index'));
    $this->assign('page_title_active', 18);  /*右上子選單active*/
    
    $this->assign('event_list_page_count', self::$event_list_page_count);
  }

  //事件處理列表頁
  public function getlist(){
    // 重新發布
    $republish=D('eve_events as e')
      ->field('e.*, e.content as eve_content, cc.nick, cc.name as crm_title, erf.name as eve_role_flow')
      ->join('crm_crm as cc on cc.id = e.cum_id','left')
      ->join('eve_role_flow as erf on erf.id = e.result','left')
      ->where("eid=".session('eid')." AND step_flow='0' AND e.result NOT IN(4,6,8,9,10)")
      ->order('e.id asc')
      ->select();
    foreach($republish as $key=>$vo){
      $republish[$key]['show_name'] = CustoHelper::get_crm_show_name($vo['cum_id']);
      $republish[$key]['steps_content'] = '發佈事件';
    }
    $this->assign("republish",$republish);

    $makesum = 0;
    $mysum=0;
    $ovsum=0;
    // 找出所自己要處理，且執行狀態仍為0 ，且事件不是不給過、竣工、垃圾桶、歸檔的流程
    $dis_id= D('eve_steps as es')
        ->field('es.*, e.content as eve_content, cc.nick, cc.name as crm_title')
        ->join('eve_events as e on e.id=es.eve_id','left')
        ->join('crm_crm as cc on cc.id = e.cum_id','left')
        ->where("es.user_id=".session('eid')." and es.status != 1 and e.result NOT IN(4,6,8,9,10)")
        ->order('e.id asc, es.orders asc, es.id asc')
        ->select();
    if(count($dis_id)!=0){
      foreach($dis_id as $key=>$vo){
        $ex_steps=D('eve_events as e')->field('e.*')->where("e.id=".$vo['eve_id']." and e.result IN (0,1,2,3,11)")->select()[0];
        if($ex_steps == null){ continue; }
        $ex_steps['show_name'] = CustoHelper::get_crm_show_name($ex_steps['cum_id']);

        if(count($ex_steps)!=0){
          $current_step = D('eve_steps')->where('eve_id='.$vo['eve_id'].' AND orders='.$ex_steps['step_flow'])->find();
          if($current_step){
            if($current_step['step_id']==1){ /*核可者正在確認中的*/
              $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
              $approved[$makesum++]=$ex_steps;
              $approved[$makesum-1]['step_id']=$vo['step_id'];
              $approved[$makesum-1]['steps_content']=$vo['content'];
              $approved[$makesum-1]['eve_content']=$vo['eve_content'];
            }
            if($current_step['step_id']==0){ /*分配者正在分配中的*/
              $dis_steps['schedule']=(round($dis_steps['step_flow']/$dis_steps['step_num'],2)*100)."%";
              $distribution[$makesum++]=$ex_steps;
              $distribution[$makesum-1]['step_id']=$vo['step_id'];
              $distribution[$makesum-1]['steps_content']=$vo['content'];
              $distribution[$makesum-1]['eve_content']=$vo['eve_content'];
            }
          }

          if($vo['is_make']!= 1 && $vo['orders']==$ex_steps['step_flow'] && $ex_steps['step_flow']>0){ /*待處理的*/
            $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
            $mys[$mysum++]=$ex_steps;
            $mys[$mysum-1]['step_id']=$vo['step_id'];
            $mys[$mysum-1]['steps_content']=$vo['content'];
            $mys[$mysum-1]['eve_content']=$vo['eve_content'];
          }

          if($vo['orders']>$ex_steps['step_flow'] && $ex_steps['step_flow']>0){ /*已發布未到件的*/
            $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
            $ovs[$ovsum++]=$ex_steps;
            $ovs[$ovsum-1]['step_id']=$vo['step_id'];
            $ovs[$ovsum-1]['steps_content']=$vo['content'];
            $ovs[$ovsum-1]['eve_content']=$vo['eve_content'];
          }
        }
        //var_dump($ex_steps);						
      }

      $eve_role_steps=D("eve_role_steps")->select();
      $this->assign("eve_role_steps",$eve_role_steps);

      parent::index_set('eip_user','true','',false);
      parent::index_set('eve_role_level');

      // dump($mys);exit;
      $this->assign("mys",$mys);
      $this->assign("ovs",$ovs);
      $this->assign("make",$make);
      $this->assign("approved",$approved);
      $this->assign("distribution",$distribution);
      $this->assign("trash",$trash);
    }

    $this->assign("getlist_Btn_on","navBtn_on");
    $this->display();
  }
  // 行進事件列表頁
  public function runing(){
    $dis_id=D('eve_steps es')->field('es.*, e.content AS eve_content')
                ->join('eve_events as e on e.id=es.eve_id','left')
                ->where("es.user_id=".session('eid')." AND step_flow!='0'")
                ->order('e.id asc, es.orders asc, es.id asc')
                ->select();
    //dump($dis_id);


    $sucsum = 0;
    $trashsum = 0;
    $notimesum=0;
    $delaysum=0;
    $makesum = 0;
    $vsum=0;
  
    $nowTime=time();
    $wrong_job=[];
    $suc=[];
    $trash=[];
    $notime=[];
    $delay=[];
    $make=[];
    $vs=[];
    //dump($dis_id);
    //有沒有指派的事件
    if(count($dis_id)!=0){
      foreach($dis_id as $key=>$vo){
        /*找出此步驟對應的事件*/
        $ex_steps=D('eve_events e')->field('e.*')->where("e.id=".$vo['eve_id']." AND e.result in (2,3,5,6,8)")->select()[0];
        if($ex_steps == null){ continue; };

        $ex_steps['show_name'] = CustoHelper::get_crm_show_name($ex_steps['cum_id']);

        if(count($ex_steps)!=0){
          // 此步驟屬於完成項目
          if(($vo['status']==1 || $ex_steps['result']==6) && $vo['step_id']=='3'){
            $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
            $suc[$sucsum++]=$ex_steps;
            $suc[$sucsum-1]['step_id']=$vo['step_id'];
            $suc[$sucsum-1]['steps_content']=$vo['content'];
            $suc[$sucsum-1]['eve_content']=$vo['eve_content'];
            continue;
          }

          // 此步驟屬於暫停中的項目
          if($ex_steps['result']==8){
            $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
            $trash[$trashsum++]=$ex_steps;
            $trash[$trashsum-1]['step_id']=$vo['step_id'];
            $trash[$trashsum-1]['step_id']=$vo['step_id'];
            $trash[$trashsum-1]['steps_content']=$vo['content'];
            $trash[$trashsum-1]['eve_content']=$vo['eve_content'];
            continue;
          }

          // 此步驟屬於當前事件進度，且是輪到 執行者或驗收者 處理 ，且有點擊過處理
          if($ex_steps['step_flow']==$vo['orders'] && ($vo['step_id']=='3' || $vo['step_id']=='5') && $vo['is_make']=='1'){
            $is_start_time = $vo['start_time'];
            $is_end_time = $vo['end_time'];

            $wrongjob_word = "";
            if($vo['count'] > 0){
              $wrongjob=D("wrong_job")->where("eve_id=".$vo['eve_id'])->order("id desc")->select();
              $wrongjob_word = "(".$vo['count']."驗修改)";
              if($wrongjob[0]['dateline']){
                $is_end_time = strtotime($wrongjob[0]['dateline']);
              }else{
                $is_end_time = null;
              }
            }
            $ex_steps['title'] = $ex_steps['title'].$wrongjob_word;

            $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
            if($is_end_time <= $is_start_time){ // 未排定時間的項目
              $notime[$notimesum++]=$ex_steps;
              $notime[$notimesum-1]['step_id']=$vo['step_id'];
              $notime[$notimesum-1]['steps_content']=$vo['content'];
              $notime[$notimesum-1]['eve_content']=$vo['eve_content'];

            }else if($is_end_time < $nowTime&& $is_end_time != null){ // 延遲的項目
              $delay[$delaysum++]=$ex_steps;
              $delay[$delaysum-1]['step_id']=$vo['step_id'];
              $delay[$delaysum-1]['steps_content']=$vo['content'];
              $delay[$delaysum-1]['eve_content']=$vo['eve_content'];

            }else{  // 進行中的項目
              $make[$makesum++]=$ex_steps;
              $make[$makesum-1]['step_id']=$vo['step_id'];
              $make[$makesum-1]['steps_content']=$vo['content'];
              $make[$makesum-1]['eve_content']=$vo['eve_content'];
            }
            
            continue;
          }

          /*此步驟屬於 執行者 且 沒驗收過*/
          if($vo['step_id']==3 && $vo['status']==0){
            $current_step = D('eve_steps')->where("eve_id=".$vo['eve_id']." and `orders` =".$ex_steps['step_flow'])->find();
            if($current_step['step_id']==5 && $ex_steps['step_flow']>$vo['orders']){// 當前輪到驗收者，當前進度大於步驟進度，表示等待驗收
              $ex_steps['schedule']=(round($ex_steps['step_flow']/$ex_steps['step_num'],2)*100)."%";
              $vs[$vsum++]=$ex_steps;
              $vs[$vsum-1]['step_id']=$vo['step_id'];
              $vs[$vsum-1]['steps_content']=$vo['content'];
              $vs[$vsum-1]['eve_content']=$vo['eve_content'];
            }
          }
        }
      }
      
      $eve_role_steps=D("eve_role_steps")->select();

      $this->assign("vs",$vs);
      $this->assign("eve_role_steps",$eve_role_steps); 
      //dump($mys[0]);
      parent::index_set('eve_role_flow');
      parent::index_set('eve_role_level');
      parent::index_set('eip_user','true','',false);
      $this->assign("suc",$suc);
      $this->assign("wrong_job",$wrong_job);
      $this->assign("notime",$notime);
      $this->assign("delay",$delay);
      $this->assign("make",$make);
      $this->assign("approved",$approved);
      $this->assign("distribution",$distribution);
      $this->assign("trash",$trash);
    }
    
    $this->assign("runing_Btn_on","navBtn_on");
    $this->display();
  }

  /*事件列表頁面*/
  public function index(){
    $_GET['p'] = $_GET['p'] ?? 1;
    $returnData = $this->get_events();
    $this->assign("events", $returnData['eve_events']);
    $this->assign("show", $returnData['show']);
    //dump($eve_events);

    $eve_role_steps=D("eve_role_steps")->where("do=0")->select();
    $this->assign("eve_role_steps", $eve_role_steps);

    
    parent::index_set('eip_apart', $where="true", $word="name", $rname=false);
    parent::index_set('eve_role_flow');
    parent::index_set('eve_back_flow');
    parent::index_set('eve_role_level');
    parent::index_set('eip_user','is_job=1','',false);
    $this->assign("index_Btn_on","navBtn_on");

    $this->display();
  }
  /*歸檔頁面*/
  public function file(){
    $_GET['p'] = $_GET['p'] ?? 1;
    $_GET['view_type'] = 'file'; // 查詢歸檔
    $returnData = $this->get_events();
    $this->assign("events", $returnData['eve_events']);
    $this->assign("show", $returnData['show']);
    
    $eve_role_steps=D("eve_role_steps")->where("do=0")->select();
    $this->assign("eve_role_steps", $eve_role_steps);
    
    parent::index_set('eip_apart', $where="true", $word="name", $rname=false);
    parent::index_set('eve_role_flow');
    parent::index_set('eve_back_flow');
    parent::index_set('eve_role_level');
    parent::index_set('eip_user','is_job=1','',false);
    $this->assign("file_Btn_on","navBtn_on");
    
    $this->display();
  }
  /*垃圾桶頁面*/
  public function trash_can(){
    $_GET['p'] = $_GET['p'] ?? 1;
    $_GET['view_type'] = 'trash_can'; // 查詢歸檔
    $returnData = $this->get_events();
    $this->assign("events", $returnData['eve_events']);
    $this->assign("show", $returnData['show']);
    
    $eve_role_steps=D("eve_role_steps")->where("do=0")->select();
    $this->assign("eve_role_steps", $eve_role_steps);
    
    parent::index_set('eip_apart', $where="true", $word="name", $rname=false);
    parent::index_set('eve_role_flow');
    parent::index_set('eve_back_flow');
    parent::index_set('eve_role_level');
    parent::index_set('eip_user','is_job=1','',false);
    $this->assign("trash_can_Btn_on","navBtn_on");

    $this->display();
  }
  /*ajax取得事件資料*/
  public function ajax_get_events(){
    $_GET['show_all'] = true;
    $returnData = $this->get_events();
    // dump($returnData);
    $this->ajaxReturn($returnData);
  }
  /*依條件搜尋事件*/
  public function get_index_events(){
    // I('get.') == $_GET
    $get_data = I('get.');

    $acc= Common::get_my_access();

    /*事件列表*/
    $where_arr = [
      'result' => ['not in', [9, 10]],
      'eve_steps.user_id' => $get_data['user'],
    ];

    $eve_id_result = D('eve_steps')->field('eve_id')->where(['user_name' => session('userName')])->select();

    if (empty($eve_id_result) == false) {
      if (session('eid') != self::$top_adminid && $acc['fig_all'] == 0) {
        $eve_id = array_column($eve_id_result, 'eve_id');
        $where_arr['eve_events.id'] = ['in', $eve_id];
      }
    }

    $field_arr = [
      'eve_events.id AS id',
      'eve_events.cum_id AS cum_id',
      'eve_role_steps.name AS role_name',
      'eve_steps.content AS es_content'
    ];

    $eve_event = D('eve_events')->join('LEFT JOIN eve_steps ON eve_events.id = eve_steps.eve_id AND eve_events.step_flow = eve_steps.orders')
    ->join('LEFT JOIN eve_role_steps ON eve_role_steps.id = eve_steps.step_id')
    ->field($field_arr)
    ->where($where_arr)
    ->group('eve_events.id')
    ->order('create_time desc')
    ->select();

    $count = count($eve_event);

    // 实例化分页类 传入总记录数和每页显示的记录数(25)
    $Page = new \Think\Page($count, self::$event_list_page_count);
    $Page->setConfig('header', "%TOTAL_ROW% 個客戶");
    $Page->setConfig('prev', "上一頁");
    $Page->setConfig('next', "下一頁");
    $Page->setConfig('first', "第一頁");
    $Page->setConfig('last', "最後一頁 %END% ");
    $show = $Page->show();// 分页显示输出
    $total_page = $Page->totalPages;// 總頁數

    if (array_key_exists('p', $get_data) == true) {
      if (empty($get_data['p']) == true) {
        $p = 1;
      } else {
        $p = $get_data['p'];
      }

      $start_index = $p - 1 >= 0 ? ($p - 1) * $Page->listRows : 0;
      $eve_event = array_slice($eve_event, $start_index, $Page->listRows);
    }

    return array_map(function ($item) {
      $item['show_name'] = CustoHelper::get_crm_show_name($item['cum_id']);

      return $item;
    }, $eve_event);
  }
  /*依條件搜尋事件*/
  public function get_events(){
    $result_temp='';
    $daoModel = "fig_all";
    $acc= Common::get_my_access();
    // dump($acc);exit;
    
    $view_type = isset($_GET['view_type']) ? $_GET['view_type'] : 'index';
    if($view_type=='index'){ /*事件列表*/
      $where = "result NOT IN(9,10)";
    }elseif($view_type=='file'){ /*歸檔區*/
      $where = "result IN(9)";
    }elseif($view_type=='trash_can'){ /*垃圾桶*/
      $where = "result IN(10)";
    }else{
      $where = "true";
    }
    $where .= " and ( false ";

    if(!isset($_GET['show_all'])){
      $eve_id = D('eve_steps')->where('user_name = "'.session('userName').'"')->select();
      if(session('eid')!=self::$top_adminid && $acc['fig_all'] == 0){
        foreach($eve_id as $key_id => $vo_id){
          $where .= "or eve_events.id = '".$vo_id['eve_id']."' ";
        }
        $where .= ")";
      }
      else{
        $where .= "or true )";
      }
    }else{
      $where .= "or true )";
    }

    if(isset($_GET['result'])){
      if($_GET['result']==''){
      }
      else if($_GET['result']==2){ /*搜尋進行中*/
        $where .= ' AND result in (2, 3)';
      }
      else{
        $where .= ' AND result="'.$_GET['result'].'"';
      }
    }

    if(isset($_GET['value'])){
      $vo_name1 = str_replace ('台','臺',$_GET['value']);
      $vo_name2 = str_replace ('臺','台',$_GET['value']);
      $where .=" AND 
          ( evesno like '%{$_GET['value']}%' or (case_name like '%".$vo_name1."%' or crm_crm.name like '%".$vo_name1."%' or crm_crm.nick like '%".$vo_name1."%' or title like '%".$vo_name1."%') or 
            (crm_crm.addr like '%".$vo_name1."%' or crm_crm.addr like '%".$vo_name2."%') or 
            (case_name like '%".$vo_name2."%' or crm_crm.name like '%".$vo_name2."%' or crm_crm.nick like '%".$vo_name2."%' or title like '%".$vo_name2."%')
          )";
    }
    if(isset($_GET['year']) && isset($_GET['month'])){
      $where .= parent::datetime($_GET['year'],$_GET['month']);
    }
    if(isset($_GET['cum_id'])){
      $where .= ' AND eve_events.cum_id ="'.$_GET['cum_id'].'"';
    }
    if(isset($_GET['user']) && $_GET['user']!=''){
      $where .= ' AND eve_steps.user_id ="'.$_GET['user'].'"';
    }
    if(isset($_GET['step_id']) && $_GET['step_id']!=''){
      $where .= ' AND eve_steps.step_id ="'.$_GET['step_id'].'"';
    }

    $field = '
      eve_events.*,eip_user.name as publish_name,
      eve_steps.id as eve_steps_id, eve_steps.user_name,eve_steps.user_id,eve_steps.content es_content,eve_steps.step_id,eve_steps.end_time,eve_steps.start_time,
      eve_chats.eid chat_id,eve_chats.content chat_content,
      crm_crm.name,crm_crm.nick,crm_crm.addr,
      eve_role_steps.name role_name
    ';
    // dump($where);exit;
    $count=D('eve_events')->where($where)
      ->field($field)
      ->join('left join eip_user on eve_events.eid = eip_user.id')
      ->join('left join eve_steps on eve_events.id=eve_steps.eve_id and eve_events.step_flow=eve_steps.orders')
      ->join('left join eve_chats on eve_events.id =eve_chats.eve_id')
      ->join('left join crm_crm on crm_crm.id =eve_events.cum_id')
      ->join('left join eve_role_steps on eve_role_steps.id = eve_steps.step_id')
      ->group('eve_events.id')
      ->order("create_time desc")->select();
    $count = count($count);

    $Page = new \Think\Page($count, self::$event_list_page_count);// 实例化分页类 传入总记录数和每页显示的记录数(25)
    $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
    $Page->setConfig('prev',"上一頁");
    $Page->setConfig('next',"下一頁");
    $Page->setConfig('first',"第一頁");
    $Page->setConfig('last',"最後一頁 %END% ");
    $show = $Page->show();// 分页显示输出
    $total_page = $Page->totalPages;// 總頁數

    $eve_events=D('eve_events')->where($where)
      ->field($field)
      ->join('left join eip_user on eve_events.eid = eip_user.id')
      ->join('left join eve_steps on eve_events.id=eve_steps.eve_id and eve_events.step_flow=eve_steps.orders')
      ->join('left join eve_chats on eve_events.id =eve_chats.eve_id')
      ->join('left join crm_crm on crm_crm.id =eve_events.cum_id')
      ->join('left join eve_role_steps on eve_role_steps.id = eve_steps.step_id')
      ->group('eve_events.id')
      ->order("create_time desc");
    if($_GET['p']){
      $p = $_GET['p'] ? $_GET['p'] : 1;
      $start_index = $p-1>=0 ? ($p-1)*$Page->listRows : 0;
      $eve_events=$eve_events->limit($start_index.','.$Page->listRows);
    }
    $eve_events=$eve_events->select();

    // if($eve_events==null && $_GET['value']!=''){
    // 	$eve_events=D('eve_events')->where("result NOT IN(9,10) and evesno LIKE '%".strtoupper($_GET['value'])."%' or case_name LIKE '%".strtoupper($_GET['value'])."%' ")->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
    // }

    foreach($eve_events as $key => $vo){
      //echo $vo['cum_id'];
      $eve_events[$key]['show_name'] = CustoHelper::get_crm_show_name($vo['cum_id']);

      $eve_events[$key]['schedule']=(round($vo['step_flow']/$vo['step_num'],2)*100)."%";
      
      /*判斷進行狀況*/
      $eve_events[$key]['color'] = '';
      if($vo['result']==2){ /*進行中事件*/
        $wrong_job = D('wrong_job')->where('steps_id="'.$vo['eve_steps_id'].'"')->order('id desc')->find();
        if($wrong_job){ /*有往返單*/
          if($wrong_job['dateline']){
            if($wrong_job['dateline']<$wrong_job['datestart']){ /*未排定*/
              $eve_events[$key]['result']=11;
              $eve_events[$key]['color'] = 'pink';
            }else if(strtotime($wrong_job['dateline'])<time()){
              $eve_events[$key]['result']=3; /*延遲*/
              $eve_events[$key]['color'] = 'red';
            }
          }else{ /*未排定*/
            $eve_events[$key]['result']=11;
            $eve_events[$key]['color'] = 'pink';
          }
        }else{
          if($vo['end_time']!=''){
            if($vo['end_time']<$vo['start_time']){ /*未排定*/
              $eve_events[$key]['result']=11;
              $eve_events[$key]['color'] = 'pink';
            }else if($vo['end_time']<time()){
              $eve_events[$key]['result']=3; /*延遲*/
              $eve_events[$key]['color'] = 'red';
            }
          }else{ /*未排定*/
            $eve_events[$key]['result']=11;
            $eve_events[$key]['color'] = 'pink';
          }
        }
      }
    }
    
    $eve_role_flow = D('eve_role_flow')->select();
    $role_flow = [];
    foreach($eve_role_flow as $key => $value) {
      $role_flow[$value['id']] = $value['name'];
    }

    $eve_role_level = D('eve_role_level')->select();
    $role_level = [];
    foreach($eve_role_level as $key => $value) {
      $role_level[$value['id']] = $value['name'];
    }

    $returnData = [
      'eve_events' => array_values($eve_events),
      'show' => $show,
      'total_page' => $total_page,
      'role_flow' => $role_flow,
      'role_level' => $role_level,
    ];
    return $returnData;
  }

  /*發布事件列表頁*/
  public function addnew(){
    $acc=D('access')->where('id='.session('accessId'))->select()[0];
    if($acc['custo_all'] == 1){ /*可看全部客戶*/
      $in = " and true";
    }
    else{
      $in =" and ( did in(".session('childeid').") or wid in(".session('childeid').") or sid in(".session('childeid').") or hid1 in(".session('childeid').")
      or hid2 in(".session('childeid').") or hid3 in(".session('childeid').") )";
    }

    if($_POST['rowname']==''){ $_POST['rowname']='name'; }
    $this->assign('rowname_input', $_POST['rowname']);
    $this->assign('value_input', $_POST['value']);
    $this->assign('wid_input', $_POST['wid']);
    $this->assign('levellist_input', $_POST['levellist']);
      
    $where="crm_crm.wid like '".$_POST['wid']."' and ";
    $where.="crm_crm.levelid like '".$_POST['levellist']."' and ";
    
    if(false!==(strpos($_POST['value'],'台')) || false !==(strpos($_POST['value'],'臺'))){
      $vo_name1 = str_replace ('台','臺',$_POST['value']);
      $vo_name2 = str_replace ('臺','台',$_POST['value']);
      $where.=" ( 
            crm_crm.".$_POST['rowname']." like '%{$vo_name1}%' OR 
            crm_crm.".$_POST['rowname']." like '%{$vo_name2}%' 
            )";
      if($_POST['rowname']=='name'){
        $where.=" OR ( 
              crm_crm.nick like '%{$vo_name1}%' OR 
              crm_crm.nick like '%{$vo_name2}%'
              )";	
      }
    }else{
      $where.="crm_crm.".$_POST['rowname']." like '%".$_POST['value']."%'";
      if($_POST['rowname']=='name'){
        $where.= " OR crm_crm.nick like '%{$_POST['value']}%'";	
      }
    }
    // dump($where);exit;
            
    $count_record = 50;
    if(($_GET['p'] == 1 || !$_GET['p']) and (!$_POST['value'] || $_POST['value'] =='')){
      $count_record =49;
    }

    $count=D('crm_crm')->where($where.$in)->count();
          $Page = new \Think\Page($count,$count_record);// 实例化分页类 传入总记录数和每页显示的记录数(25)
          $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
          $Page->setConfig('prev',"上一頁");
          $Page->setConfig('next',"下一頁");
          $Page->setConfig('first',"第一頁");
          $Page->setConfig('last',"最後一頁 %END% ");
          $show = $Page->show();// 分页显示输出

    // 排除置頂id外篩選
    $crmlist=D('crm_crm')->where("crm_crm.id!='". self::$our_company_id ."' and ".$where.$in)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->order('crm_cum_type.sort,crm_crm.levelid,CAST(CONVERT(crm_crm.nick using big5) AS BINARY)')->field('crm_crm.*')->join("left join crm_cum_type on crm_crm.typeid=crm_cum_type.id")->select();
    //dump(D('crm_crm')->getLastSql());	
    //dump($crmlist);

    if(($_GET['p'] == 1 || !$_GET['p'])){
      $first = D('crm_crm')->where("crm_crm.id='".self::$our_company_id."'")->order("id desc")->limit(1)->order('crm_crm.id')->join("left join crm_cum_type on crm_crm.typeid=crm_cum_type.id")->field('crm_crm.*')->select()[0];
      //dump($first);
      if(!empty($first)){
        array_unshift($crmlist,$first);
      }
    }

    foreach($crmlist as $k => $v){
      $crmlist[$k]['show_name'] = CustoHelper::get_crm_show_name($v['id']);
      
      $crmlist[$k]['show_addr']= '';
      if($v['register_addr'] != '' &&  $v['register_addr'] != '1')
        $crmlist[$k]['show_addr']= $v['register_addr'];
      
      if($v['factory_addr'] != ''  &&  $v['factory_addr'] != '1')
        $crmlist[$k]['show_addr']= $v['factory_addr'];
      
      if($v['shipment_addr'] != ''  &&  $v['shipment_addr'] != '1')
        $crmlist[$k]['show_addr']= $v['shipment_addr'];

      if($v['accounting_addr'] != ''  &&  $v['accounting_addr'] != '1')
        $crmlist[$k]['show_addr']= $v['accounting_addr'];

      if($v['addr'] != '')
        $crmlist[$k]['show_addr']= $v['addr'];	
    }

    parent::index_set('eip_user','is_job=1 and id !='.self::$top_adminid);
    parent::index_set('crm_cum_type');
    parent::index_set('crm_cum_level');
    parent::index_set('crm_cum_pri','id=1');

    $this->assign("crmlist",$crmlist);
    $this->assign("show",$show);

    //等級
    $levellist = D()->query("select `id`,`name` from crm_cum_level");
    $this->assign('levellist', $levellist);

    $this->assign("addnew_Btn_on","navBtn_on");
    $this->display();
  }
  /*發布/重發事件內容頁*/
  public function addcontent(){
    if(isset($_GET['eve_id'])){  // 重發事件
      $eve_steps=D("eve_steps")->where("eve_id=".$_GET['eve_id'])->select();
      $eve_events=D("eve_events")->where("id=".$_GET['eve_id'])->find();
      $_GET['id'] = $eve_events['cum_id'];
      $eve_sno = $eve_events['evesno'];
      $eve_sno_num  = substr($eve_sno, 2);
      $eve_caseid = $eve_events['caseid'];
      $eve_title = $eve_events['title'];
      $eve_content = $eve_events['content'];
      $eve_events_id = $eve_events['id'];

    }else{  // 新發事件
      $eve_steps=[];
      $eve_events=D('eve_events')
              ->where("create_time between ".strtotime(date("Y/m/d"))." and ".(strtotime(date("Y/m/d"))+86400))
              ->order("id desc")->select();
      if(count($eve_events)!=0){
        $test=$eve_events[0]['evesno'];
        $test=substr($test,2); 
        $eve_sno_num = $test+1;
      }else{
        $eve_sno_num = date('Ymd').str_pad(0,3,'0',STR_PAD_LEFT) + 1;
      }
      $eve_sno = 'EN'.$eve_sno_num;
      $eve_caseid = 0;
      $eve_title = "";
      $eve_content = "";
      $eve_events_id = "";
    }

    $this->assign('eve_sno', $eve_sno);
    $this->assign('eve_sno_num', $eve_sno_num);
    $this->assign('eve_caseid', $eve_caseid);
    $this->assign('eve_title', $eve_title);
    $this->assign('eve_content', $eve_content);
    $this->assign('eve_events_id', $eve_events_id);
    //dump($eve_steps);//exit;

    $where.="id = '".$_GET['id']."' ";
    $crm_crm=D('crm_crm')->field('id')->where($where)->select();
    if($crm_crm[0]){
      $crm_crm[0]['show_name'] = CustoHelper::get_crm_show_name($crm_crm[0]['id']);
    }
    $this->assign('one',$crm_crm[0]);
    $this->assign('cum_id',json_encode($_GET['id']));

    $eve_role_level=D('eve_role_level')->select();
    $this->assign('eve_role_level',$eve_role_level);

    $eip_user=D('eip_user')->where("status=1 && is_job=1 && id!=".self::$top_adminid)->select();
    $this->assign('eip_user',$eip_user);

    $model_builder=D('eip_user e')
      ->field('e.id, e.name')
      ->join('eve_processes ep on ep.eid=e.id','right')
      ->where("e.status=1 && e.is_job=1 && e.id!=".self::$top_adminid)
      ->group('e.id')
      ->select();
    $this->assign('model_builder',$model_builder);

    $processes=D('eve_processes')->where("status=1")->select();
    $this->assign('processes',$processes);
    
    $crm_contract=D('crm_contract')->where("cid=".$_GET['id']." and flag!=3 and get_or_pay=0")->order('id desc')->select();
    $this->assign('crm_contract',$crm_contract);
    
    $this->display();
  }	
  //新增事件 或 草稿
  public function do_add(){
    // dump($_POST);exit;
    $eve_events_id = isset($_POST['eve_events_id']) ? $_POST['eve_events_id'] : "";
    unset($_POST['eve_events_id']);

    $_POST['step_flow']='0';
    $_POST['create_time']=time();
    $_POST['eid']=Session('eid');
    $_POST['file_name']=$_FILES['file']['name'] ?? null;
    $_POST['step_num']=0;

    $draft = isset($_POST['draft']) ? $_POST['draft'] : '0';
    // 判斷執行狀態
    if($draft=='1'){				// 儲存成草稿
      $_POST['result']='-1';			// 草稿
    }else{
      $first_role = $steps[0];
      if($first_role=='0'){		// 分配者
        $_POST['result']='1';		// 分配中
      }elseif($first_role=='1'){	// 核可者 
        $_POST['result']='0';		// 送件中
      }else{						// 其他腳色
        $_POST['result']='2';		// 進行中
      }
    }
    //echo $_POST['evesno'].'<br>';
    //echo $_POST['case_name'].'<br>';
  
    if($_POST['title'] == ''){
      $this->error('主旨不可空白');
    }

    $disname='Uploads/fig/';
    $file = $this->uploadfile($disname);
    $_POST['file_url']=$file;
    $_POST['content'] = save_img_in_content($_POST['content']);
    // dump($_POST);exit;

    if(isset($_POST['case_name'])){
      $_POST['case_name'] = trim($_POST['case_name']);
    }

    if($eve_events_id){ /*有提供eve_events_id*/
      /*重新發佈*/
      D("eve_events")->data($_POST)->where("id=".$eve_events_id)->save();
      $id = $eve_events_id;
    }else{
      /*新事件*/
      $has_evesno = D('eve_events')->where('evesno="'.$_POST['evesno'].'"')->find();
      if($has_evesno){ $this->error('此事件號已被使用'); }
      $id=D('eve_events')->data($_POST)->add();
    }

    if($id){
      $step_num = $this->set_event_steps($id); // 設定事件步驟
      $updata['step_num'] = $step_num; //流程數
      D("eve_events")->data($updata)->where("id=".$id)->save();
      
      if($draft=='1'){
        parent::error_log("儲存草稿事件, 事件:{$id}, ".self::$system_parameter['合約'].":{$_POST['case_name']}, 名稱:{$_POST['title']}");
        $this->success('儲存草稿成功',u('Fig/index'));
      }else{
        $this->push($id); /*推進事件進度*/
        $this->remind_email($id, 'nextjob'); // 寄送提醒信

        if($eve_events_id){ /*有提供eve_events_id 重新發佈*/
          parent::error_log("重發事件, 事件:{$id}, ".self::$system_parameter['合約'].":{$_POST['case_name']}, 名稱:{$_POST['title']}");
          $this->success('重發佈成功',u('Fig/index'));
        }else{/*新事件*/
          parent::error_log("新增事件, 事件:{$id}, ".self::$system_parameter['合約'].":{$_POST['case_name']}, 名稱:{$_POST['title']}");
          $this->success('新增成功',u('Fig/index'));
        }
      }
    }else{
      $this->error('新增失敗');
    }
  }
  //上傳檔案類 文件
  function uploadfile($finalPath,$rule="uniqid"){
    //import("ORG.Net.UploadFile");
    //import("@.ORG.UploadFile");
    $upload = new \Org\Net\UploadFile();
    $upload->savePath = $finalPath;
    $upload->thumb = false;
    $upload->saveRule = $rule;

    if(!$upload->upload()) {
        //echo ($upload->getErrorMsg());
      $main_image='';
    }else{
      $i = 0;
      $infoBuf = $upload->getUploadFileInfo();
      foreach($infoBuf as $key=>$vo){
        $main_image = '/'.$vo['savepath'].$vo['savename'];
        $back.="<a href='{$main_image}' download='{$vo['name']}'><img src='/Public/qhand/images/save.png' />{$vo['name']}</a><br>";

      }
    }
    return $back;
  }
  function set_event_steps($eve_id=0){
    $steps = $_POST['steps'];
    if(!$steps){ $this->error('未安排流程'); }
    $row_ids = $_POST['row_ids'];
    if(!$row_ids){ $this->error('未安排流程'); }
    $code = $_POST['code'];
    $work = $_POST['work'];
    $price = $_POST['price'];
    $count_type = $_POST['count_type'];
    $count_type = array_values($count_type); /*重新依據參數的順序編排index，避免因為拖拉更換順序導致內外單資料錯位*/
    $sdate = $_POST['sdate'];
    $edate = $_POST['edate'];
    $estimated_time = $_POST['estimated_time'];
    $exact_time = $_POST['exact_time'];
    
    $eip_user=D("eip_user")->where("id in(".implode(",", $code).")")->select();//找出相關的人員
    
    $num=0;
    $eve_events_ids = [];
    foreach($steps as $key=>$vo){
      if($vo!="-1"){
        $num += 1;

        /*查看此步驟原本是否存在*/
        $eve_steps_id = 0;
        $data = D("eve_steps")->where('eve_id="'.$eve_id.'" AND id="'.$row_ids[$key].'"')->find();
        if($data){ $eve_steps_id = $data['id']; }

        $data['eve_id']		= $eve_id;
        $data['step_id']	= $vo;
        $data['user_id']	= $code[$key];
        $data['content']	= $work[$key];
        $data['price']		= $price[$key];
        $data['count_type']	= $count_type[$key] ? $count_type[$key] : 0;
        $data['start_time']	= $sdate[$key] ? strtotime(str_replace("T", " ", $sdate[$key]) ) : "";
        $data['end_time']	= $edate[$key] ? strtotime(str_replace("T", " ", $edate[$key]) ) : "";
        $data['estimated_time']	= $estimated_time[$key] ? $estimated_time[$key] : 0;
        $data['exact_time']	= $exact_time[$key] ? $exact_time[$key] : 0;
        if($data['start_time'] == $data['end_time']){ /*時間一樣視為不排時間*/
          $data['start_time']	= "";
          $data['end_time']	= "";
        }
        foreach($eip_user as $ukey=>$ev){//對齊資料
          if($code[$key]==$ev['id']){
            $data['user_name']=$ev['name'];
            $data['apartmentid']=$ev['apartmentid'];
          }
        }
        $data['update_time'] = time();
        $data['orders'] = $num;

        if($eve_steps_id){
          D("eve_steps")->data($data)->save();
        }else{
          $eve_steps_id = D("eve_steps")->data($data)->add();
        }
        array_push($eve_events_ids, $eve_steps_id);

      }
    }
    if($eve_events_ids){
      // 刪除此事件 但 與本次設定無關的步驟
      D("eve_steps")->where('eve_id="'.$eve_id.'" AND id NOT IN ('.implode(",", $eve_events_ids).')')->delete();
    }

    return $num==0 ? $num : $num+1;
  }

  //事件詳細內容頁
  public function view(){
    $where="e.id='".$_GET['id']."'";
    $btn = $_GET['btn'];

    $eve_id=D('eve_steps')->where('eve_id='.$_GET['id'])->select();
    //dump($eve_id);
    $this->assign('eve_id', $eve_id);

    $eve_events=D('eve_events e')->field('e.*')->where($where)->limit(1)->select()[0];
    $eve_events['show_name'] = CustoHelper::get_crm_show_name($eve_events['cum_id']);
    if($eve_events['case_name']){
      $crm_contract = D('crm_contract')->where("sn='".trim($eve_events['case_name'])."'")->find();
      // dump($crm_contract);exit;
      if($crm_contract){
        $eve_events['caseid'] = $crm_contract['id'];        
        $pre_aftertax_money = ContractHelper::get_pre_aftertax_money($crm_contract['invoice'], $crm_contract['allmoney']);
        $eve_events['case_total'] = $pre_aftertax_money['money_pretax'];
      }else{
        $eve_events['caseid'] = 0;
        $eve_events['case_total'] = 0;
      }
      
    }else{
      $eve_events['caseid'] = 0;
      $eve_events['case_total'] = 0;
    }
    $eve_events['level']=D('eve_role_level')->where("id='".$eve_events['eve_level']."'")->select()[0]['name'];
    //show_bug($eve_events);
    // dump($eve_events);exit;

    $crm_contract=D('crm_contract')->where("cid=".$eve_events['cum_id']." AND flag2=1 AND get_or_pay=0")->order('id desc')->select();
    $this->assign('crm_contract',$crm_contract);

    /*******往返紀錄建置************/
    $wrongjob=D("wrong_job")->where("eve_id=".$_GET['id'])->order("id")->select();
    //dump($wrongjob);
    foreach($wrongjob as $key => $vo){
      $wrongjob[$key]['user_name']=D("eip_user")->where("id=".$wrongjob[$key]['user_id'])->getField("name");
      $wrongjob[$key]['eve_tname']=D("eve_back_flow")->where("id=".$wrongjob[$key]['eve_tid'])->getField("name");
    }
    //echo $wrongjob[0]['id'];
    //dump($wrongjob);
    $this->assign('wrongjob',$wrongjob);
    /*******************************/

    // 當前進度的流程
    $current_step = D('eve_steps')->where('`orders`="'.$eve_events['step_flow'].'" AND eve_id="'.$eve_events['id'].'"')->order('`orders` desc')->find();
    if($current_step['count']!=0){ /*驗收未過次數非0*/
      /*修正此步驟設定的執行時間*/
      $current_step['start_time'] = strtotime($wrongjob[count($wrongjob)-1]['datestart']);
      $current_step['end_time'] = strtotime($wrongjob[count($wrongjob)-1]['dateline']);
    }
    $this->assign('current_step',$current_step);
      
    /*有點集ma*/
    if( $btn == 1 && ($current_step["step_id"]=='3' && $current_step["user_id"]==session('eid') && $current_step["is_make"]==0) ){
      $updata['is_make']='1';
      D("eve_steps")->data($updata)->where("eve_id='".$_GET['id']."' and orders='".$eve_events['step_flow']."' and user_id='".session('eid')."'")->save();
      echo '<script>location.reload()</script>';exit;
    }

    $crm_crm=D('crm_crm')->where("id='".$eve_events['cum_id']."'")->find();
    $this->assign("crm_crm",$crm_crm);

    $note1 = M("eve_events_note")->where("eve_id ='".$eve_events['id']."'")->select();
    $this->assign('note1',$note1);

    $processes=D('eve_processes')->where("status=1")->select();
    $this->assign('processes',$processes);

    $eve_events["myflow"]=true;	//是否為我需要處理
    $idlist="";//篩選人選用
    //排列以及判斷目前工作進度
    $eve_steps=D('eve_steps')->where("eve_id=".$_GET['id'])->order('orders asc')->select();
    foreach($eve_steps as $key =>$vo){			
      $idlist.=$vo['user_id']."','";
      $eve_events['step_id'][$key]=$vo['step_id'];
      $eve_events['user_name'][$key]=$vo['user_name'];
      
      //接收工作
      if($eve_events['result']!=6){ /*未竣工*/
        if($vo['orders']==$eve_events["step_flow"]){
          if($vo['user_id']==session('eid')){
            $eve_events["myflow"]=true;
            $eve_events["nowflow"]=$vo["step_id"];
            $eve_events["count"]=$vo["count"];
            $steps_id = $vo['id'];
          }else{
            $eve_events["myflow"]=false;
            $steps_id = '';
          }
        }
      }else{
        $eve_events["myflow"]=true;
        $eve_events["nowflow"]=$vo["step_id"];	
        $steps_id = $vo['id'];
      }
      if($vo['start_time']!=''){
        $eve_steps[$key]['start_time']=$vo['start_time'];
        $eve_steps[$key]['end_time']=$vo['end_time'];
        //echo $eve_steps[$key]['start_time'];
      }
      $eve_steps[$key]['step_status'] = $this->get_step_status($vo);
    }
    //dump($eve_steps);
    $this->assign("eve_steps",$eve_steps);

    //計算進度比率
    $eve_events['process_rate']=(round($eve_events['step_flow']/$eve_events['step_num'],2)*100)."%";
    //dump($eve_events);
    $this->assign("newbier",$eve_events);
    
    parent::index_set('eve_role_steps');
    parent::index_set('eve_back_flow');
    parent::index_set('eve_role_flow');
    parent::index_set('eip_apart');

    /*事件中有在排程的人員(用於對話對象選擇)*/
    $eip_user_event = parent::index_set('eip_user', "id in('".$idlist."0')");
    $this->assign("eip_user_event",$eip_user_event);
    
    /*所有人員(用於顯示人員名稱)*/
    $eip_user_all = parent::index_set('eip_user', 'true');
    $this->assign("eip_user_all",$eip_user_all);

    parent::index_set('crm_cum_pri');

    $eve_chats=D("eve_chats")->where("eve_id=".$_GET['id'])->order('id desc')->select();
    $this->assign('eve_chats',$eve_chats);

    $can_edit_content = $this->check_can_edit_content($_GET['id']);
    // dump($can_edit_content);exit;
    $this->assign('can_edit_content',$can_edit_content);

    // 當前進度下的上一個執行者(驗收者要驗收的對象)
    $last_execute = D('eve_steps')->where('step_id=3 AND `orders` < '.$eve_events['step_flow'].' AND eve_id="'.$eve_events['id'].'"')
                    ->order('`orders` desc')->select()[0];
    $this->assign('last_execute',$last_execute);

    /*找出事件的分配者流程*/
    $distribute_orders = [];
    $distributes = D('eve_steps')->where('`step_id`="0" AND eve_id="'.$eve_events['id'].'"')->order('`orders` desc')->select();
    foreach ($distributes as $k => $v) {
      array_push($distribute_orders, $v['orders']);
    }
    $distribute_orders = implode(",", $distribute_orders);
    // dump($distribute_orders);
    $this->assign('distribute_orders',$distribute_orders);

    $this->display();
  }
  public function get_step_status($step){
    $where="e.id='".$step['eve_id']."'";
    $eve_events=D('eve_events e')->field('e.*')->where($where)->limit(1)->select()[0];
    
    $nowTime=time();
    $step_status = "";
    $wrong_job_time=D('wrong_job')->where('eve_id='.$step['eve_id'].' and user_id='.$step['user_id'])
                    ->order('create_time desc')->select()[0];
    if($wrong_job_time['dateline']!=null){
      $step['end_time']= time($wrong_job_time['dateline']);
    }

    if($eve_events["step_flow"]==$step['orders']){
      if($step['step_id'] == '0'){
        $step_status =  '分配中';
      }elseif($step['step_id'] == '1'){
        $step_status =  '審核中';
      }elseif($step['step_id'] == '3'){
        $step_status =  '進行中';
      }elseif($step['step_id'] == '5'){
        $step_status = '驗收中';
      }
      
      if($step['count'] != 0){
        $step_status = '首驗未過修改中';
      }
      if($step['count']>=2){
        $step_status = '第'.$step['count'].'次修改';
      }
    }

    if($step['end_time']!=null){//延遲判斷
      if($step['end_time']< $nowTime){
        $step_status =  '超過時間';
      }
    }
    if($eve_events['step_flow']<$step['orders']){
      $step_status = '尚未輪到你';
    }
    if($eve_events['step_flow'] > $step['orders'] && isset($step['orders'])){
      if($step['status'] == 0){
        if($step['count'] == 0){
          if($vo['step_id'] == '3'){
            $step_status = '完成待驗';
          }else{
            $step_status = '完成';
          }
        }else if($step['count'] == 1){
          $step_status = '首驗未過修改中';
          /*echo $eve_events['step_flow'] ;
          $echo =  $eve_even['orders'] ;*/
          if($step['orders']<$eve_events['step_flow']){
            $step_status =  '首驗修改完成待驗';
        }
      }else{
        $step_status =  '第'.$step['count'].'次修改完成待驗';
      }
      }else if($step['status'] == 1){
        $step_status =  '驗收完畢';
      }else{
        if($step['count'] == 0){
          $step_status =  '完成待驗';
        }else{
          $step_status =  '未過修改中';
        }
      }
    }
    // echo $step_status;
    // dump($step);

    return $step_status;
  }

  // 依事件id判斷當前使用者是否可修改內容事件內容
  public function check_can_edit_content($eve_id){
    $can_edit_content = 0;
    $distribute_uesr = $this->check_is_distribute_uesr($eve_id);
    $eve_steps=D('eve_steps')->where('eve_id="'.$eve_id.'"')->select();

    $eve_events=D('eve_events e')->where('id="'.$eve_id.'"')->find();
    if( $distribute_uesr == '1' && 						/*判斷分配權限*/
      !in_array($eve_events['result'], ['6', '9', '10'])	/*判斷是見狀態*/
    ){
      $can_edit_content = 1;
    }

    return $can_edit_content;
  }

  // 依事件id判斷當前使用者是否有分配權限
  public function check_is_distribute_uesr($eve_id){
    $has_distribute = '0';//檢查此單是否有分配者
    $distribute_uesr = '0';//檢查此人是本單分配者

    $eve_steps=D('eve_steps')->where('eve_id="'.$eve_id.'"')->select();
    foreach($eve_steps as $key =>$vo){
      if($vo['step_id']=='0'){ /*此工作有分配者*/
        $has_distribute = '1';
        if($vo['user_id']==session('adminId')){ //此人是本單分配者
          $distribute_uesr = '1';
          break;
        }
      }
    }
    if($has_distribute == '0'){ /*如果此單沒有分配者*/
      $eve_events=D('eve_events e')->where('id="'.$eve_id.'"')->find();
      if($eve_events['eid']==session('adminId')){ /*此人是本單發佈者*/
        $distribute_uesr = '1';
      }
    }

    return $distribute_uesr;
  }

  /*Api:修改事件內容*/
  public function aj_up_content(){
    if(!isset($_POST['id']) || !isset($_POST['data'])){
      $this->error('操作失敗');
    }
    
    $eve_id = $_POST['id'];
    $this->check_fig_access(CONTROLLER_NAME, 'red', [$eve_id], '0', 'eve_events'); /*檢查使否為此事件的分配者*/

    $message = '修改事件';
    $data = $_POST['data'];
    foreach ($data as $key => $value) {
      if(!in_array($key, ['caseid', 'case_name', 'title', 'content', 'step_flow'])){
        $this->error('無法修改此欄位');
      }
      else if($key=='caseid'){
        $message = '修改事件合約';
      }
      else if($key=='title'){
        $message = '修改事件標題';
      }
      else if($key=='content'){ /*修改內容*/
        $message = '修改事件內容';
        $data[$key] = save_img_in_content($value);
      }
      else if($key=='step_flow'){ /*修改流程*/
        $eve_events = D('eve_events')->data($data)->where("id='".$eve_id."'")->find();
        if($eve_events['step_flow']==0){
          $this->error('請先發佈事件');
        }else if($eve_events['result']==6){
          $this->error('此事件已竣工，無法修改');
        }else if($value<1 || $value >= $eve_events['step_num']){
          $this->error('階段僅可設為1~'.($eve_events['step_num']-1));
        }
        $message = '修改事件流程至階段'.$value;
      }
    }
    if($data){
      D('eve_events')->data($data)->where("id='".$eve_id."'")->save();
    }

    $note['eve_id'] = $eve_id;
    $note['orders'] = 0;
    $note['user_id'] = session('adminId');
    $note['user_name'] = session('userName');
    $note['step_id'] = 0;
    $note['create_time'] = time();
    $note['value'] = "修改";
    $note['message'] = $message;
    M("eve_events_note")->data($note)->add();

    parent::error_log("修改事件內容, 事件:{$eve_id}, 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
    $this->success('修改成功');
  }

  // 人員進度表頁
  public function work_calendar(){
    $user 	= isset($_GET['user'])	? $_GET['user'] : "";
    $search = isset($_GET['search'])? $_GET['search'] : "";
    $title 	= isset($_GET['title']) ? $_GET['title'] : "";
    $apart 	= isset($_GET['apart']) ? $_GET['apart'] : "";
    $this->assign("user",$user);
    $this->assign("search",$search);
    $this->assign("title",$title);
    $this->assign("apart",$apart);

    parent::index_set('eip_apart', $where="true", $word="name", $rname=false);
    parent::index_set('eve_role_flow');
    parent::index_set('eve_role_steps');
    parent::index_set('eip_user',"is_job=1");
    parent::index_set('eve_back_flow');

    $this->assign("calendar_Btn_on","navBtn_on");

    $this->display();
  }
  // AJAX取得工作步驟(甘特圖用)
  public function aj_get_working_steps(){
    $all = $_GET['all'] ? $_GET['all'] : 'false'; /*判斷顯示內容，預設顯示當前流程之後*/

    $show_examine_step = $_GET['show_examine_step'] ?? 0;
    if($show_examine_step=='1'){
      $where = [
        '(es.step_id=3 OR es.step_id=5)',	/*抓取執行者&驗收者的流程*/
      ]; 
    }else{
      $where = [
        '(es.step_id=3)',	/*只抓取執行者的流程*/
      ]; 
    }
    if($all=='false'){ /*不看全部流程(甘特圖)*/
      array_push($where, 'e.result in(1,2,3,11)'); /*分配中, 進行中, 延遲, 未排定時間*/
      array_push($where, 'es.orders >= e.step_flow'); /*只抓在事件進度之後的流程*/
    }

    $show_time_type = $_GET['show_time_type'] ?? '1';
    if($show_time_type!='-1'){
      if($show_time_type=='0'){ /*未排定時間*/
        array_push($where, '(
          ((es.start_time="" OR es.end_time="") AND w.id is null) OR 
          ((w.datestart="" OR w.dateline="") AND w.id is not null)
        )');
      }else if($show_time_type=='1'){ /*已排定時間*/
        array_push($where, '(
          (es.start_time!="" AND es.end_time!="" AND w.id is null) OR
          (w.datestart!="" AND w.dateline!="" AND w.id is not null)
        )');
      }
    }

    if($_GET['eve_id']!=''){
      array_push($where, 'es.eve_id ='.$_GET['eve_id']);
    }
    if($_GET['apartmentid']!=''){
      $apart=D('eip_user')->where("status=1 and is_job=1 and apartmentid=".$_GET['apartmentid'])->select();
      //dump($apart);
      if($apart!=null){
        $ids = [];
        foreach($apart as $key =>$vo){
          array_push($ids, $vo['id']);
        }
        if($ids){
          array_push($where, "es.user_id in(". implode(',', $ids) .")");
        }
      }
    }
    if($_GET['user_id']!=''){
      array_push($where, 'es.user_id ='.$_GET['user_id']);
    }
    if($_GET['search']!=''){
      $search = $_GET['search'];
      array_push($where, "( eu.name like '%".$search."%' OR 
                  e.title like '%".$search."%' OR
                  e.evesno like '%".$search."%' OR
                  c.name like '%".$search."%' OR
                  c.nick like '%".$search."%'
                )");
    }
    if($_GET['fig_time_s']!=''){
      $fig_time_s = $_GET['fig_time_s'];
      array_push($where, " e.create_time >=".strtotime($_GET['fig_time_s']));
    }
    if($_GET['fig_time_e']!=''){
      $fig_time_s = $_GET['fig_time_s'];
      array_push($where, " e.create_time <=".strtotime($_GET['fig_time_e']));
    }
    // dump($where);exit;
    $jonin_last_wrong_job = "
      LEFT JOIN (
        SELECT * FROM `wrong_job` JOIN (SELECT max(id) AS max_id FROM `wrong_job` GROUP BY steps_id) m ON m.max_id = wrong_job.id
      ) w on w.steps_id=es.id
    ";
    $eve_steps = D('eve_steps es')->field('es.*, 
                                           e.result, e.title, e.eve_level, e.cum_id,
                                           eu.name as u_name')
                    ->join("left join eve_events e on e.id=es.eve_id")
                    ->join("left join eip_user eu on eu.id=es.user_id")
                    ->join("left join crm_crm c on c.id=e.cum_id")
                    ->join($jonin_last_wrong_job)
                    ->where($where)
                    ->order('e.id asc, es.orders asc')
                    ->group("es.id")
                    ->select();
    // dump($eve_steps);exit;
    $events = D('eve_events e')->field('e.*')
            ->join("left join eve_steps es on e.id=es.eve_id")
            ->join("left join eip_user eu on eu.id=es.user_id")
            ->join("left join crm_crm c on c.id=e.cum_id")
            ->join($jonin_last_wrong_job)
            ->where($where)->order('e.id asc, es.orders asc')
            ->group("e.id")
            ->select();

    /*整理step資料*/
    $steps_data = [];
    foreach ($eve_steps as $k => $v) {
      $wrong_job = D('wrong_job')->where('eve_id="'.$v['eve_id'].'"')->where('steps_id="'.$v['id'].'"')->order('id asc')->select();
      // dump($wrong_job);
      
      if($all=='false'){ /*只顯示當前流程之後的*/
        if($wrong_job){
          $w_v = end($wrong_job);
          array_push($steps_data, $this->set_work_flow_step_data($v, $w_v));
        }else{
          array_push($steps_data, $this->set_work_flow_step_data($v));
        }
      }
      else{ /*顯示全部*/
        array_push($steps_data, $this->set_work_flow_step_data($v));
        foreach ($wrong_job as $w_k => $w_v) {
          array_push($steps_data, $this->set_work_flow_step_data($v, $w_v));
        }
      }
    }

    /*計算整個甘特圖開始到結束的時間*/
      $sdate="9999-99-99 99:99"; /*開始時間*/
      $edate="0000-00-00 00:00"; /*結束時間*/
      foreach ($steps_data as $key => $value) {
        if($value['sdate']!="" && $value['sdate']!="1970-01-01 08:00" && $value['sdate']<$sdate){ $sdate = $value['sdate'];}
        if($value['edate']!="" && $value['edate']!="1970-01-01 08:00" && $value['edate']>$edate){ $edate = $value['edate'];}
      }
      // dump($sdate);
      // dump($edate);
      $sdate = $sdate!='9999-99-99 99:99' && $sdate!='' ? date('Y-m-d 00:00', strtotime($sdate)) : date('Y-m-d 00:00');	// 調整成當天0點
      $edate = $edate!='0000-00-00 00:00' && $edate!='' ? date('Y-m-d 00:00', strtotime($edate." +1Day")) : date('Y-m-d 00:00', strtotime($sdate." +1Day")); // 調整成隔天0點

    $returnData = [
      'sdate'		=> $sdate,
      'edate'		=> $edate,
      'steps_data'=> $steps_data,
      'events'	=> $events,
    ];
    // dump($returnData);exit;
    $this->ajaxReturn($returnData);
  }
  private function set_work_flow_step_data($eve_step, $wrong_job=null){
    $name = CustoHelper::get_crm_show_name($eve_step['cum_id']);
    $name .= '-'.$eve_step['title'].'：'.$eve_step['content'];
    if($wrong_job){     
      $step_id = $wrong_job['id'].'_wrong';
      $name .= '(驗收未過)';
      $sdate = $wrong_job['datestart'] ? $wrong_job['datestart'] : date('Y-m-d H:i');
      $edate = $wrong_job['dateline'] ? $wrong_job['dateline'] : date('Y-m-d H:i');
      $eve_step['price'] = $wrong_job['money'];
      $eve_step['estimated_time'] = $wrong_job['estimated_time'];
      $eve_step['exact_time'] = $wrong_job['exact_time'];
      $state = "hurry";
      $eve_step['time_type'] = $wrong_job['time_type'];
    }else{
      $step_id = $eve_step['id'];
      $sdate = $eve_step['start_time'] ? date('Y-m-d H:i', $eve_step['start_time']) : date('Y-m-d H:i');
      $edate = $eve_step['end_time'] ? date('Y-m-d H:i', $eve_step['end_time']) : date('Y-m-d H:i');
      $state = "normal";
    }
    return [
      'eve_id'=>		$eve_step['eve_id'], 
      'step_order'=>	$eve_step['orders'], 
      'step_id'=>		$step_id, 
      'u_name'=>		$eve_step['u_name'], 
      'name'=>		$name, 
      'sdate'=>		$sdate, 
      'edate'=>		$edate, 
      'price' =>		$eve_step['price'], 
      'estimated_time'=> $eve_step['estimated_time'],
      'exact_time'=> $eve_step['exact_time'],
      'state'=>		$state,
      'time_type' => $eve_step['time_type'],
    ];
  }
  /*取得假期資料*/
  public function get_holiday(){
    $holiday_data = [];
    $page = 1;
    $loop = true;
    while($loop){
      $request_url = "https://data.ntpc.gov.tw/api/datasets/308DCD75-6434-45BC-A95F-584DA4FED251/json?page=".$page."&size=500";
      $data = get_send_request($request_url);
      // dump($page);
      // echo $data;
      if($data!="[]" && $data!=""){
        /*處理資料*/
        $data = str_replace("\\/", "-", $data);		// 修改年月日間隔符號
        $data = str_replace("-1-", "-01-", $data);  // 修改月，填滿兩碼
        $data = str_replace("-2-", "-02-", $data);
        $data = str_replace("-3-", "-03-", $data);
        $data = str_replace("-4-", "-04-", $data);
        $data = str_replace("-5-", "-05-", $data);
        $data = str_replace("-6-", "-06-", $data);
        $data = str_replace("-7-", "-07-", $data);
        $data = str_replace("-8-", "-08-", $data);
        $data = str_replace("-9-", "-09-", $data);
        $data = str_replace('-1"', '-01"', $data);  // 修改日，填滿兩碼
        $data = str_replace('-2"', '-02"', $data);
        $data = str_replace('-3"', '-03"', $data);
        $data = str_replace('-5"', '-05"', $data);
        $data = str_replace('-4"', '-04"', $data);
        $data = str_replace('-6"', '-06"', $data);
        $data = str_replace('-7"', '-07"', $data);
        $data = str_replace('-8"', '-08"', $data);
        $data = str_replace('-9"', '-09"', $data);

        $data = json_decode($data);
        $holiday_data = array_merge($holiday_data, $data);
        $page += 1;
      }else{
        $loop = false;
      }
    }
    
    $this->ajaxReturn($holiday_data);
    // echo json_encode($holiday_data, JSON_UNESCAPED_UNICODE);
  }
  /*更新工作步驟(甘特圖用)*/
  public function update_working_steps(){
    // dump($_POST);exit;
    $steps = $_POST['steps'];

    if(!$steps){ $this->error('請提供排程'); }

    foreach ($steps as $k => $v) {
      $step_id = $v['step_id'];
      $step_id_array = explode('_', $step_id);
      $step_id = $step_id_array[0];

      /*判斷修改目標(預設是eve_steps)*/
      $target_table = 'eve_steps';
      if(count($step_id_array)==2){
        if($step_id_array[1]=='wrong'){
          $target_table = 'wrong_job'; /*此項目為驗收未過*/
        }
      }

      /*整理更新資料*/
        $update_steps = [];
        $update_steps['time_type'] = $v['time_type'];
        $update_steps['estimated_time'] = $v['estimated_time'];
        $update_steps['exact_time'] = $v['exact_time'];
        if($target_table=='wrong_job'){
          $update_steps['datestart'] = $v['sdate'] ? $v['sdate'] : "";
          $update_steps['dateline'] = $v['edate'] ? $v['edate'] : "";
          if($update_steps['datestart'] == $update_steps['dateline']){ /*時間一樣視為不排時間*/
            $update_steps['datestart']	= "";
            $update_steps['dateline']	= "";
          }

          $wrong_job = D('wrong_job')->where("id=".$step_id)->find();
          if(!$wrong_job){ continue; }
          $eve_events = D('eve_events')->where("id=".$wrong_job['eve_id'])->find();
          if(!$eve_events){ continue; }
          /*權限判斷*/
          if($this->my_access['fig_edi']==1){ /*有可編輯權限*/
          }else{/*無可編輯權限*/
            $distributeable = D('eve_steps')->where("eve_id=".$wrong_job['eve_id'].' AND user_id='.session('eid').' AND step_id=0')->select(); /*查看此事件你是否是分配者*/
            if(!$distributeable){ /*如果你不是分配者*/
              $distributers = D('eve_steps')->where("eve_id=".$wrong_job['eve_id'].' AND step_id=0')->select(); /*查看此事件有無分配者*/
              if(count($distributers)>0){ /*事件有分配者*/
                continue;
              }
              else if($eve_events['eid']!=session('eid')){ /*你不是發佈指者*/
                continue;
              }
            }
          }
          parent::error_log("修改排程(wrong_job):".$step_id.", 資料:".json_encode($update_steps, JSON_UNESCAPED_UNICODE));
          D('wrong_job')->data($update_steps)->where("id=".$step_id)->save();
        }
        else{
          $update_steps['time_type'] = $v['time_type'];
          $update_steps['start_time'] = $v['sdate'] ? strtotime($v['sdate']) : "";
          $update_steps['end_time'] = $v['edate'] ? strtotime($v['edate']) : "";
          if($update_steps['start_time'] == $update_steps['end_time']){ /*時間一樣視為不排時間*/
            $update_steps['start_time']	= "";
            $update_steps['end_time']	= "";
          }

          $eve_steps = D('eve_steps')->where("id=".$step_id)->find();
          if(!$eve_steps){ continue; }
          $eve_events = D('eve_events')->where("id=".$eve_steps['eve_id'])->find();
          if(!$eve_events){ continue; }
          /*權限判斷*/
          if($this->my_access['fig_edi']==1){ /*有可編輯權限*/
          }else{/*無可編輯權限*/
            $distributeable = D('eve_steps')->where("eve_id=".$eve_steps['eve_id'].' AND user_id='.session('eid').' AND step_id=0')->select(); /*查看此事件你是否是分配者*/
            if(!$distributeable){ /*如果你不是分配者*/
              $distributers = D('eve_steps')->where("eve_id=".$eve_steps['eve_id'].' AND step_id=0')->select(); /*查看此事件有無分配者*/
              if(count($distributers)>0){ /*事件有分配者*/
                continue;
              }
              else if($eve_events['eid']!=session('eid')){ /*你不是發佈指者*/
                continue;
              }
            }
          }
          parent::error_log("修改排程(eve_steps):".$step_id.", 資料:".json_encode($update_steps, JSON_UNESCAPED_UNICODE));
          D('eve_steps')->data($update_steps)->where("id=".$step_id)->save();
        }
      // $eve_id = $v['eve_id'];
      // $update['eve_level'] = $v['state']=='hurry' ? 2 : 1;
      // D('eve_events')->data($update)->where("id=".$eve_id)->save();
    }

    $this->success('更新完畢');
  }

  //執行-分配者設定排程 
  public function do_start(){
    // dump($_POST);exit;
    $this->check_fig_access(CONTROLLER_NAME, 'red', [$_POST['eve_id']], '0', 'eve_events'); /*檢查使否為此事件的分配者*/

    if(!isset($_POST['eve_id'])){ $this->error("執行失敗"); }
    $eve_id = $_POST['eve_id'];
    $eve_events = M("eve_events")->where('id ="'.$eve_id.'"')->find();
    if(!$eve_events){ $this->error("執行失敗"); }
    $current_order = $_POST['orders'];					/*當前流程*/
    $distribute_orders = $_POST['distribute_orders'];	/*分配者的流程*/
    $distribute_orders = $distribute_orders ? explode(",", $distribute_orders) : [];

    $step_num = $this->set_event_steps($eve_id); // 設定事件步驟
    $updata['step_num'] = $step_num; //流程數
    if($eve_events['step_flow']>=$step_num){
      $updata['step_flow'] = $step_num - 1;
      $current_order = $updata['step_flow'];
    }
    D("eve_events")->data($updata)->where("id=".$eve_id)->save();
    // dump($eve_events);exit;

    $steps = M("eve_steps")->where("eve_id='".$eve_id."' and orders='".$current_order."'")->select();
    $note['eve_id'] = $steps[0]['eve_id'];
    $note['orders'] = $steps[0]['orders'];
    $note['user_id'] = session('adminId');
    $note['user_name'] = session('userName');
    $note['step_id'] = 0;
    $note['create_time'] = time();
    $note['message'] = "設定排程績效";
    if(in_array($eve_events['step_flow'], $distribute_orders)){
      $note['value'] = "執行";
      M("eve_events_note")->data($note)->add();

      if($this->push($eve_id)){
        $this->remind_email($eve_id, 'nextjob'); // 寄送提醒信
        $this->success("工作開始執行!!",u('Fig/getlist'));
        exit;
      }
    }else{
      $note['value'] = "修改";
      M("eve_events_note")->data($note)->add();

      $this->success("修改排程成功!!");
      exit;
    }
  }
  
  //執行-核可者的操做
  public function do_passflow(){
    if($_POST['check'] == ""){
      $_POST['value'] = $_POST['buttom'];
    }
    
    // echo $_POST['eve_id'].'<br>';
    // echo $_POST['orders'];
    $steps = M("eve_steps")->where("eve_id = '".$_POST['eve_id']."' and orders = '".$_POST['orders']."'")->select();

    $note['eve_id'] = $steps[0]['eve_id'];
    $note['orders'] = $steps[0]['orders'];
    $note['user_id'] = $steps[0]['user_id'];
    $note['user_name'] = $steps[0]['user_name'];
    $note['step_id'] = 1;
    $note['message'] = $_POST['message'];
    $note['create_time'] = time();
    //dump($_POST['buttom']);exit;
    if(isset($_POST['eve_id']) && (isset($_POST['check']) || isset($_POST['buttom']))){
      $step_id=$_POST['step_id'];
      $ev=$_POST['eve_id'];
      switch($_POST['check']){
        case "0"://通過
          D()->execute("update `eve_steps` set `is_make`= '1' where `eve_id`='{$ev}' and orders = '".$_POST['orders']."'");
          $note['value'] = "通過";
          $this->push($_POST['eve_id']);
          $remind_type = 'nextjob'; // 提醒信種類
          break;

        case "1"://退回重送
          $note['value'] = "不核可、參照說明再送";
          D()->execute("update `eve_events` set `step_flow`=0, `result`='0' where `id`='{$ev}'");//回待發佈
          D()->execute("update `eve_steps` set `is_make`= '0' where `eve_id`='{$ev}' ");
          $remind_type = 'cancel'; // 提醒信種類
          break;

        case "2"://不給過
          $note['value'] = "不給過";
          D()->execute("update `eve_events` set `result`='4' where `id`='{$ev}'");
          $remind_type = 'cancel'; // 提醒信種類
          break;
          
        case "3"://待討論
          $note['value'] = "不核可、會議討論";
          D()->execute("update `eve_events` set `step_flow`=0, `result`='0' where `id`='{$ev}'");//回待發佈
          D()->execute("update `eve_steps` set `is_make`= '0' where `eve_id`='{$ev}' ");
          $remind_type = 'cancel'; // 提醒信種類
          break;
      }
      M("eve_events_note")->data($note)->add();
      $this->remind_email($_POST['eve_id'], $remind_type); // 寄送提醒信
      $this->success("審核成功!!",u('Fig/getlist'));
    }else{
      $this->error("審核失敗");
    }
  }

  //執行-提交案件歷程
  public function do_work(){
    if(isset($_POST['eve_id'])){
      //檔案上傳
      $_POST['file_name']=$_FILES['file']['name'];
      if($_FILES['file']['name']){
        $disname='Uploads/fig/';
        $file=parent::uploadfile($disname);
        $link='<a href="'.$file.'" download="'.$_POST['file_name'].'"><img src="/Public/qhand/images/save.png" />'.$_POST['file_name'].'</a>';
        //$_POST['message']=$link.strip_tags($_POST['message']);
      }
    $steps = M("eve_steps")->where("eve_id = '".$_POST['eve_id']."' and orders = '".$_POST['orders']."'")->select();
    $note['eve_id'] = $steps[0]['eve_id'];
    $note['orders'] = $steps[0]['orders'];
    $note['user_id'] = $steps[0]['user_id'];
    $note['user_name'] = $steps[0]['user_name'];
    $note['step_id'] = $steps[0]['step_id'];
    $note['value'] = $_POST['value'];
    $note['message'] = $_POST['message'];
    $note['create_time'] = time();
    $note['file'] = $link;
    M("eve_events_note")->data($note)->add();
      $this->success("上傳成功");exit;

    }
    $this->error("完成失敗");
  }
  //執行-提交驗收
  public function do_next(){
    $steps = M("eve_steps")->where("eve_id = '".$_POST['eve_id']."' and orders = '".$_POST['orders']."'")->select();
    $note['eve_id'] = $steps[0]['eve_id'];
    $note['orders'] = $steps[0]['orders'];
    $note['user_id'] = $steps[0]['user_id'];
    $note['user_name'] = $steps[0]['user_name'];
    $note['step_id'] = $steps[0]['step_id'];
    $note['value'] = $_POST['value'];
    $note['create_time'] = time();
    $note['message'] = '完成項目:'.$steps[0]['content'];
    M("eve_events_note")->data($note)->add();

    if(isset($_POST['eve_id'])){
      if($this->push($_POST['eve_id'])){ // 進度+1

        $auto_check = true; /*自動驗收通過*/
        // 進度+1後的人(下一個腳色)
        $eve_events = D("eve_events")->where("id=".$_POST['eve_id'])->find();
        $next_steps = M("eve_steps")->where("eve_id = '".$_POST['eve_id']."' and orders = '".$eve_events['step_flow']."'")->find();
        if($next_steps){ // 有下一個腳色
          if($next_steps['step_id'] == '5'){ // 下一個腳色是驗收者
            $auto_check = false;
          }
        }
        if($auto_check){
          // 自動更新執行狀態為驗收通過
          $time = time();
          M("eve_steps")->where("eve_id = '".$_POST['eve_id']."' and orders = '".$_POST['orders']."'")->data([
            'update_time'=> $time, 
            'kpi_time'=> $time, 
            'status'=> 1,
          ])->save();
        }
        $this->remind_email($_POST['eve_id'], 'nextjob'); // 寄送提醒信

        $this->success("完成工作成功");
        exit;
      }
    }else{
      $this->error("完成失敗");
    }
  }

  //執行-驗收通過
  public function do_check(){
    // dump($_POST);exit;
    // 當前進度下的上一個執行者(驗收者要驗收的對象)
    $eve_id = $_POST['eve_id'] ?? '';
    $eve_events = D('eve_events')->where('id="'.$eve_id.'"')->find();
    $last_execute = D('eve_steps')->where('step_id=3 and `orders` < '.$eve_events['step_flow'].' and eve_id='.$eve_id)->order('`orders` desc')->select()[0];
    $curret_step = D('eve_steps')->where('`orders` = '.$eve_events['step_flow'].' and eve_id='.$eve_id)->order('`orders` desc')->select()[0];

    $steps = M("eve_steps")->where("eve_id = '".$eve_id."' and step_id=5 and user_id=".session('adminId'))->select();
    $note['eve_id'] = $steps[0]['eve_id'];
    $note['orders'] = $steps[0]['orders'];
    $note['user_id'] = $steps[0]['user_id'];
    $note['user_name'] = $steps[0]['user_name'];
    $note['step_id'] = 5; // 驗收者
    $note['value'] = $_POST['check_result'];
    $note['message'] = '驗收項目：'.$last_execute['content'];
    $note['create_time'] = time();
    M("eve_events_note")->data($note)->add();

    $time = time();
    $eve_steps_id = D('eve_steps')->where("`eve_id`='".$eve_id."' and `orders`=".$last_execute['orders'])->find()['id'];
    if($eve_steps_id){
      // 更新最後一筆往返單為處理完畢(如果有的話)
      D('wrong_job')->where("`eve_id`='".$eve_id."' and `steps_id`=".$eve_steps_id." and complete_time is null")
            ->order('id desc')
            ->limit(1)
            ->data([
              'status' => 1,
              'complete_time' => $time,
            ])->save();
    }

    $events_num = M("eve_events")->where("id = '".$note['eve_id']."'")->Field('step_flow,step_num')->select();
    if($_POST['check_result'] == "完成確認"){
      D('eve_steps')->where("`eve_id`='".$eve_id."' AND `orders`=".$curret_step['orders'].' AND status=0')->data([
        'kpi_time' => $time,
      ])->save();
      D('eve_steps')->where("`eve_id`='".$eve_id."' and `orders`=".$curret_step['orders'])->data([
        'status' => 1,
        'update_time' => $time,
      ])->save();

      $this->push($eve_id); // 進度+1

      // 判斷是否驗收通過
      $finished = false;
      // 進度+1後的人(下一個腳色)
      $eve_events = D('eve_events')->where('id="'.$eve_id.'"')->find();
      $next_steps = M("eve_steps")->where("eve_id='".$eve_id."' and orders = '".$eve_events['step_flow']."'")->find();
      if($next_steps){ // 有下一步
        if($next_steps['step_id'] != '5'){ // 下一個腳色不是驗收者，表示驗收完了
          $finished = true;
        }
      }else{// 沒有下一步了，事件竣工，表示驗收完了
        $finished = true;
      }
      if($finished==true){
        D('eve_steps')->where("`eve_id`='".$eve_id."' and `orders`=".$last_execute['orders'].' AND status=0')->data([
          'kpi_time' => $time,
        ])->save();
        D('eve_steps')->where("`eve_id`='".$eve_id."' and `orders`=".$last_execute['orders'])->data([
          'status' => 1,
          'update_time' => $time,
        ])->save();
      }
      $this->remind_email($eve_id, 'nextjob'); // 寄送提醒信

      return $this->success("驗收成功");
    }

    $this->error("驗收失敗");
  }

  //往前一步 
  function push($id){
    // dump($_POST);exit;
    $eve_events=D("eve_events")->where("id=".$id)->limit(1)->select()[0];
    parent::error_log("推進事件:".$id.", 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));

    if($_POST['step_id']=="0" || $_POST['step_id']=="-1"){ //創建中 || 草稿
      $data['result']=2;
      $data['step_flow']= $eve_events['step_flow'] + 1;
      if($data['step_flow'] == $eve_events['step_num'])
        $data['step_flow']=$data['step_flow']-1;
    }
    elseif($_POST['step_id']=="1"){ //送件中
      $next_orders = (Int)$_POST['orders'] + 1;
      $next_step = D('eve_steps')->where("id=".$id)->where("orders='".$next_orders."'")->find(); // 抓取下個步驟
      if(!$next_step){
        return 0;
      }else{
        $data['result'] = $next_step['step_id'] == 0 ? '1' : '2';
        $data['step_flow']=$eve_events['step_flow']+1;
      }
    }
    elseif( ($eve_events['step_flow']+1) ==$eve_events['step_num']) {//完成
      $data['result']=6;
      $data['step_flow']=$eve_events['step_flow']+1;
    }
    else{ //進行中
      $data['step_flow']=$eve_events['step_flow']+1;
    }

    if($data['step_flow'] > $eve_events['step_num']){
      $data['step_flow'] = $eve_events['step_num'];
    }

    $ans=D("eve_events")->where("id=".$id)->data($data)->save();

    //定義流程
    $eve_steps=D("eve_steps")->where("eve_id='".$id."' and orders='".$data['step_flow']."'")->select()[0];
    switch($eve_steps['step_id']){
      case 2: // 會參者自動跳下一個
        $this->remind_email($id, 'nextjob');
        $this->push($id);
        break;

      case 1:
      case 3:
        break;
    }
    return 1;
  }

  //執行-往返單頁面(驗收未過時開啟)
  public function Wrongjob(){	
    $this->assign("de_date",date("Y-m-d",time()));

    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : -1;
    $eve_id = isset($_GET['eve_id']) ? $_GET['eve_id'] : 0;

    $eve_back_flow=D('eve_back_flow')->where('status=1')->select();
    $this->assign('eve_back_flow',$eve_back_flow);
  
    if($job_id==-1){
      $wrong_job = [];
    }
    else{
      if($job_id!=0){
        $wrong_job=D("wrong_job")->where("id=".$job_id)->find();
      }
      else{
        $wrong_job=D("wrong_job")->where("eve_id=".$eve_id." and user_id=".$_GET['user_id'])->order("id")->select();
        $wrong_times=count($wrong_job);//[$wrong_times-1]取得最後一筆
        $wrong_job = $wrong_job[$wrong_times-1];
      }
      $job_id = $wrong_job['id'];
      $eve_id = $wrong_job['eve_id'];

      $wrong_job['datestart'] = strtotime($wrong_job['datestart']);
      $wrong_job['dateline'] = strtotime($wrong_job['dateline']);
    }
    $this->assign('job_id', $job_id);
    $this->assign('eve_id', $eve_id);

    $can_edit_content = $this->check_can_edit_content($wrong_job['eve_id']);
    // dump($can_edit_content);exit;
    $this->assign('can_edit_content',$can_edit_content);

    $this->assign("wrong_job", $wrong_job);
    
    $this->display();
  }
  // 產生往返單
  public function do_wrong(){
    // dump($_POST);exit;
    $job_id = $_POST['job_id'];
    $data['eve_id']=$_POST['eve_id'];

    $_POST['content'] = save_img_in_content($_POST['content']); //將內容內的base64圖片上傳到主機
    if($_POST['datestart']){
      $ss=strtotime($_POST['datestart']);
      $_POST['datestart']=date("Y-m-d H:i", $ss);
    }else{
      $_POST['datestart'] = '';
    }
    if($_POST['dateline']){
      $s=strtotime($_POST['dateline']);
      $_POST['dateline']=date("Y-m-d H:i", $s);
    }else{
      $_POST['dateline'] = '';
    }

    $_POST['file_name']=$_FILES['file']['name'][0];
    $data['eve_tid']=$_POST['eve_tid'];
    $data['content']=$_POST['content'];
    $data['money']=$_POST['money'];
    $data['datestart']=$_POST['datestart'];
    $data['dateline']=$_POST['dateline'];
    $data['estimated_time']=$_POST['estimated_time'];
    $data['exact_time']=$_POST['exact_time'];

    $disname='Uploads/fig/';
    $data['file_name']=$this->uploadfile($disname);
    //$_POST['upfile']=$this->uploadfile($disname);
    // dump($data);exit;
    
    if($job_id==0 || $job_id==-1){ /*新增往返單*/
      $eve_events=D('eve_events')->where('id="'.$_POST['eve_id'].'"')->find();
      if(!$eve_events){ $this->error('此事件不存在，無法新增往返單'); }
      
      $is_acceptance = false;
      $eve_steps=D('eve_steps')->where('eve_id="'.$_POST['eve_id'].'" AND orders='.$eve_events['step_flow'])->select();
      foreach ($eve_steps as $key => $value) {
        if($value['step_id']==5 && $value['user_id']==session('adminId')){
          $is_acceptance = true;
          break;
        }
      }
      if(!$is_acceptance){ $this->error('您非驗收者 或 未達驗收階段，無法新增往返單'); }

      // 當前進度下的上一個執行者(驗收者要驗收的對象)
      $eve_events = D('eve_events')->find($_POST['eve_id']);
      $last_execute = D('eve_steps')->where('step_id=3 and `orders` < '.$eve_events['step_flow'].' and eve_id='.$_POST['eve_id'])->order('`orders` desc')->select()[0];
      // dump($eve_events);exit;
      $data['steps_id'] = $last_execute['id'];; // 最後執行者eve_steps的id
      $data['user_id']= $last_execute['user_id']; // 最後執行者user_id
  
      $steps = M("eve_steps")->where("eve_id = '".$_POST['eve_id']."' and step_id = 5 and user_id =".$_SESSION['adminId'])->select();
      $note['eve_id'] = $steps[0]['eve_id'];
      $note['orders'] = $steps[0]['orders'];
      $note['user_id'] = $steps[0]['user_id'];
      $note['user_name'] = $steps[0]['user_name'];
      $note['step_id'] = 5; // 驗收者
      $note['value'] = '驗收未過';
      $note['message'] = '驗收項目：'.$last_execute['content'].' '.$_POST['message'];
      $note['create_time'] = time();
      M("eve_events_note")->data($note)->add();
      $status['status'] = 1;
      M("eve_events_note")->where("eve_id = '".$_POST['eve_id']."'and user_id=".$_POST['user_id'])->data($status)->save();

      if($data['steps_id']){
        // 更新最後一筆往返單為處理完畢(如果有的話)
        D()->execute("update `wrong_job` set `complete_time`='".time()."' where `eve_id`='{$_POST['eve_id']}' and `steps_id`={$data['steps_id']} and complete_time is null order by id desc limit 1");
      }
      D('wrong_job')->data($data)->add();

      D()->execute("update `eve_steps` set `count`=`count`+1 where `eve_id`='{$_POST['eve_id']}' and `orders`={$_POST['orders']}"); // 驗收未過次數+1
      D()->execute("update `eve_events` set `step_flow`={$_POST['orders']} where `id`='{$_POST['eve_id']}'"); // 修改事件進度成驗收未過者的進度
      $this->remind_email($_POST['eve_id'], 'wrong'); // 寄送提醒信
      $this->success("排程成功");
    }
    else{ /*編輯往返單*/
      $can_edit_content = $this->check_can_edit_content($data['eve_id']);
      if($can_edit_content!=1){
        $this->error('您非分配者 或 此案件已停止執行，無法修改往返單');
      }

      D('wrong_job')->where('id="'.$job_id.'"')->data($data)->save();
      $this->success("儲存成功");
    }

    echo "<script>opener.reload();</script>";
    echo "<script>window.close();</script>";
  }

  //傳入項目id回傳排程功能
  public function aj_schedule(){
    if(isset($_POST['eve_id']) && $_POST['eve_id']!=''){ //有事件id則以事件id抓流程
      $rule=false;
      $num=0;
      $schedule = D("eve_steps es")->field('*, es.content as work, es.user_id as code, es.id as row_id')
                     ->join("left join eip_user on es.user_id = eip_user.id")
                     ->where("es.eve_id=".$_POST['eve_id'])
                     ->order('orders asc')->select();
      //dump($schedule);
      array_push($schedule, [
        'row_id'	=> "0",
        'step_id' 	=> "-1",// 腳色
        'code' 		=> "0",	// 人員
        'work' 		=> "",	// 行文紀錄
        'sdate' 	=> "0",	// 開始時間
        'edate' 	=> "0",	// 結束時時間
        'price' 	=> "0",	// 績效
        'count_type'=> "1",	// 內外單
        'estimated_time'=> "0",	// 預估工時
        'exact_time'	=> "0",	// 實際工時
      ]);
    }
    else if(isset($_POST['id']) && $_POST['id']!=''){// 模組事件
      $processes=D('eve_processes')->where('status=1 && id='.$_POST['id'])->select()[0];
      if($processes['schedule']!=""){
        $schedule = [];
        $data=json_decode($processes['schedule']);
        // dump($data);exit;

        $data[6] = (array)$data[6];
        foreach ($data[0] as $k => $v) {
          array_push($schedule, [
            'row_id'	=> "0",
            'step_id' 	=> isset($data[0][$k]) ? $data[0][$k] : "-1",	// 腳色
            'code' 		=> isset($data[1][$k]) ? $data[1][$k] : "0",	// 人員
            'work' 		=> isset($data[2][$k]) ? $data[2][$k] : "",		// 行文紀錄
            'sdate' 	=> isset($data[3][$k]) ? $data[3][$k] : "0",	// 開始時間
            'edate' 	=> isset($data[4][$k]) ? $data[4][$k] : "0",	// 結束時時間
            'price' 	=> isset($data[5][$k]) ? $data[5][$k] : "0",	// 績效
            'count_type'=> isset($data[6][$k]) ? $data[6][$k] : "1",	// 內外單
            'estimated_time'=> isset($data[7][$k]) ? $data[7][$k] : "0",// 預估工時
            'exact_time'=> isset($data[8][$k]) ? $data[8][$k] : "0",	// 實際工時
          ]);
        }
      }
    }
    else{// 專案事件
      $schedule = [
        [
          'row_id'	=> "0",
          'step_id' 	=> "-1",// 腳色
          'code' 		=> "0",	// 人員
          'work' 		=> "",	// 行文紀錄
          'sdate' 	=> "0",	// 開始時間
          'edate' 	=> "0",	// 結束時時間
          'price' 	=> "0",	// 績效
          'count_type'=> "1",	// 內外單
          'estimated_time'=> "0",	// 預估工時
          'exact_time'	=> "0",	// 實際工時
        ],
      ];
      // parent::index_set('eve_role_steps','do=0', "name",true,'sort asc');
    }

    // dump($schedule);
    $this->assign('schedule',$schedule);
    
    $steps_count = $schedule ? count($schedule) : 1;
    $this->assign('steps_count', $steps_count);

    parent::index_set('eve_role_steps','do=0');
    parent::index_set('eip_apart');
    parent::index_set('eip_user','is_job=1',"apartmentid");
    $this->assign("sckey",1);
    $this->assign("date",time());
    $dd=time();
    $this->assign("de_date",date("Y-m-d",$dd));

    $this->assign("time",time());

    $this->display();
  }

  /*Api:添加對話*/
  function do_addchats(){
    $disname='Uploads/fig/';
    $_POST['upfile']=$this->uploadfile($disname);
    $_POST['content']=save_img_in_content($_POST['content']); //將內容內的base64圖片上傳到主機
    $_POST['appmdate']=time();
    $_POST['dateline']='0';
    $_POST['eid']=session('eid');
    if(D("eve_chats")->data($_POST)->add()){
      $this->remind_email($_POST['eve_id'], 'talk'); // 寄送提醒信
      $this->success("留言成功");
    }else{
      $this->error("留言失敗");
    }
  }

  /*Api:修改事件狀態(執行or歸檔區中)*/
  function do_updata(){
    if($_POST['flag']==6 || $_POST['flag']==10){ /*還原、垃圾桶*/
      $this->check_fig_access(CONTROLLER_NAME, 'hid');
    }
    else{ /*暫停、歸檔*/
      $this->check_fig_access(CONTROLLER_NAME, 'edi');
    }

    //dump($_POST);
    $result_temp='';
    foreach ($_POST['ids'] as $key => $value) {
      $data = [];
      if($_POST['flag']==6){ /*還原*/
        $result_temp=D("eve_events")->where("id=".$value)->getField("result_temp");
        if($result_temp==''){ $result_temp=2; }
        $data['result']=$result_temp;
        D("eve_events")->where("id=".$value)->data($data)->save();
      }else{
        $result=D("eve_events")->where("id=".$value)->getField("result");
        if($result!=8 && $result!=9 && $result!=10){
          $data['result_temp']=$result; /*紀錄暫存狀態，未來還原用*/
        }
        $data['result']=$_POST['flag'];
        D("eve_events")->where("id=".$value)->data($data)->save();
        //D("eve_events")->where("id=".$value)->delete();
      }
    }
    $this->success("修改成功");
  }
  /*Api:修改事件狀態(垃圾桶中)*/
  function do_trash_can(){
    if($_POST['flag']==9){ /*還原*/
      $this->check_fig_access(CONTROLLER_NAME, 'hid');
    }else{ /*刪除*/
      $this->check_fig_access(CONTROLLER_NAME, 'del');
    }

    //dump($_POST);
    $data['result']=$_POST['flag'];
    foreach ($_POST['ids'] as $key => $value) {
      if($data['result']==9){ /*還原*/
        $result=D("eve_events")->where("id=".$value)->getField("result_temp"); /*讀取暫存狀態*/
        if($result=='')$result=9; /*無暫存狀態則預設歸檔*/
        $data['result']=$result;
        D("eve_events")->where("id=".$value)->data($data)->save();
      }else{
        D("eve_events")->where("id=".$value)->delete();
        D("eve_steps")->where("eve_id=".$value)->delete();
        D("eve_events_note")->where("eve_id=".$value)->delete();
        D("wrong_job")->where("eve_id=".$value)->delete();
        //echo $_POST['flag'];
      }
    }
    $this->success("修改成功");
  }

  // 回傳模組事件下拉選(新增事件時)
  public function aj_html(){
    if(isset($_POST['id']) && ($_POST['id']!="")){
      $processes=D('eve_processes')->where("`id`='".$_POST['id']."'")->limit(1)->select();//等級
      echo $processes[0]['html'];
    }
  }

  // 提醒使用者
  function remind_email($eve_id, $remind_type){
    $eve_events = D("eve_events")->where("id=".$eve_id)->find();

    $where = "eve_id=".$eve_id;
    $remind_message = '';
    $subject = '';
    switch ($remind_type) {
      case 'cancel':
        $user_id = $eve_events['eid']; // 找出發布者id
        $eve_events_note = M("eve_events_note")->where($where." and step_id = 1")->order('id desc')->select()[0];
        $remind_message = "
                  <p>被核可者判定".$eve_events_note['value']."</p>
                  <p>說明原因如下：</p>"
                  .$eve_events_note['message'];
        $subject = '事件不核可-'.$eve_events['title'];
        break;

      case 'nextjob':
        $step_flow = $eve_events['step_flow'];
        $eve_steps = D("eve_steps")->where($where." and orders = ".$step_flow)->find(); // 找出此階段
        if($eve_steps){ //檢查是否還有流程
          $user_id = $eve_steps['user_id'];
          // 有流程
          if($eve_steps['step_id']=='1'){ 		// 核可者
            $remind_message = "
                  <p>已輪到您審核是否核可執行，並有以下說明：<br>".$eve_steps['content']."</p>
                  <p>再請您用電腦登入處理</p>
                ";
            $subject = '審核事件-'.$eve_events['title'];

          }else if($eve_steps['step_id']=='0'){ // 分配者
            $remind_message = "
                      <p>已輪到您分配績效、排程，並有以下說明：<br>".$eve_steps['content']."</p>
                      <p>再請您用電腦登入處理</p>";
            $subject = '分配事件-'.$eve_events['title'];

          }else if($eve_steps['step_id']=='2'){ // 會參者
            $remind_message = "
                      <p>通知您已發布事件，並有以下說明：<br>".$eve_steps['content']."</p>
                      <p>再請您用電腦登入查看</p>";
            $subject = '執行事件-'.$eve_events['title'];

          }else if($eve_steps['step_id']=='3'){ // 執行者
            $remind_message = "
                      <p>已輪到您執行，並有以下說明：<br>".$eve_steps['content']."</p>
                      <p>再請您用電腦登入處理</p>";
            $subject = '執行事件-'.$eve_events['title'];

          }else if($eve_steps['step_id']=='5'){ // 驗收者
            $remind_message = "
                  <p>已輪到您驗收事件，並有以下說明：<br>".$eve_steps['content']."</p>
                  <p>再請您用電腦登入處理</p>";
            $subject = '驗收事件-'.$eve_events['title'];
          }
        }else{
          // 沒有流程了，代表事件完成，通知發件者
          $user_id = $eve_events['eid']; // 找出發布者id
          $remind_message = "
                    <p>事件已全部執行、驗收完成</p>
                    <p>再請您用電腦登入查看</p>";
          $subject = '事件竣工-'.$eve_events['title'];

        }
        break;

      case 'wrong':
        $step_flow = $eve_events['step_flow'];
        $user_id = D("eve_steps")->where($where." and orders = ".$step_flow)->find()['user_id']; // 找出驗收未過的執行者id
        $wrongjob = M("wrong_job")->where($where)->order('id desc')->select()[0]; // 找最後一個往返單
        $remind_message = "
                  <p>您執行的結果驗收未過</p>
                  <p>說明原因如下：</p>"
                  .$wrongjob['content']."
                  <p>再請您用電腦登入處理</p>";
        $subject = '驗收未過-'.$eve_events['title'];
        break;

      case 'talk':
        $eve_chats = M("eve_chats")->where($where)->order('id desc')->select()[0]; // 找最後一個對話紀錄
        $user_id = $eve_chats['lxrid']; // 取得對話對象
        if($user_id==0){ // 對話對象是所有人
          $ids=[];
          $users = D("eve_steps")->where($where." and user_id != ".$_SESSION['adminId'])->group('user_id')->select(); // 找出事件中其他人員的id
          foreach ($users as $key => $value) {
            array_push(	$ids, $value['user_id']);
          }
          $user_id = empty($ids) ? 0 : join(',', $ids);
        }
        $remind_message = "
                  <p>有新的對話</p>
                  <p>內容如下：</p>"
                  .$eve_chats['content']."
                  <p>再請您用電腦登入查看完整內容</p>";
        $subject = '對話通知-'.$eve_events['title'];
        break;
      
      default:
        return;
        break;
    }

    $id_where .= "id in (".$user_id.")";
    $user = D("eip_user")->where($id_where)->select();
    $crm_show_name = CustoHelper::get_crm_show_name($eve_events['cum_id']);

    $eve_url = 'http://'.$_SERVER['HTTP_HOST'].'/index.php/Fig/view.html?id='.$eve_id;
    foreach ($user as $key => $value) {
      if(!empty($value['email'])){
        $body ="
          <p>親愛的_".$value['name']."_您好：</p>
          <p>事件：".$eve_events['title']."</p>
          <p>編號：".$eve_events['evesno']."</p>
          <p>公司名稱：".$crm_show_name."</p>
          ".$remind_message."
          <p>事件網址：<a href='".$eve_url."'>".$eve_url."</a></p>
        ";
        send_email($body, $value['email'], $subject);
      }

      $payload = [
        'title' => $subject,
        'msg' => "請登入系統查看",
        'open_url' => $eve_url,
      ];
      Common::send_notification_to_user($value['id'], $payload);
    }
  }

  // 權限檢查
  public function check_fig_access($acc_type,  $acc_method, $ids=[], $role_step="", $target_table='eve_events'){
    parent::check_has_access($acc_type, $acc_method); /*檢查是否有設定此權限*/
    // dump($ids);exit;
    if($ids!=[]){ /*須依根據處理對象檢查權限*/
      switch ($target_table) {
        case 'eve_events':			
        default:
          $eve_id_column = 'id';
          break;
      }

      $error_msg = "";
      foreach ($ids as $key => $value) {
        $event = D($target_table)->field('*, '.$eve_id_column.' as eve_id')->where('id="'.$value.'"')->find();
        // dump($event);exit;
        if(!$event){ $error_msg="無此事件"; break; }

        $eve_steps = D('eve_steps')->where('eve_id="'.$event['eve_id'].'"')->select();

        $you_has_role = false;
        foreach ($eve_steps as $key2 => $value2){
          if($value2['user_id']==session('adminId') && $value2['step_id']==$role_step){ /*你處理此步驟，且此步驟腳色與比對腳色一樣*/
            $you_has_role = true; break;
          }
        }
        if($role_step=='0' && !$you_has_role){ /*檢查分配者，且你未被指派為分配者*/
          $distributers = D('eve_steps')->where('eve_id="'.$event['eve_id'].'" and step_id="0"')->count();
          if($distributers==0 && $event['eid']==session('adminId')){ /*此事件無分配者，且你為此事件的發布者*/
            $you_has_role = true;
          }
        }
        if(!$you_has_role){
          $error_msg="您不屬於對應腳色，無法操作"; break;
        }
      }

      if($error_msg){
        $this->error($error_msg);
      }
    }
  }

  /*測試用----------------------*/
    function email(){
      dump(send_email());
    }
    function notification($id=0){
      $payload = [
        'title' => '測試通知',
        'msg' => "測試通知內容",
        'open_url' => 'http://'.$_SERVER['HTTP_HOST'].'/',
      ];
      $result = Common::send_notification_to_user($id, $payload);
      dump($result);
    }
}

?>
