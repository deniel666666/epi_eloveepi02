<?php
namespace Photonic;
use Think\Controller;

use Photonic\Common;
use Photonic\CustoHelper;
use Photonic\ContractHelper;
use Photonic\FigHelper;

class ScheduleHelper extends Controller
{
  function _initialize(){
  }
  public static function instance(){
      return new ScheduleHelper();
  }

  public static function get_schedules($params, $with_schedule_date=false, $with_schedule_date_user=false){
    if(isset($params['schedule_date'])){
      if($params['schedule_date']){
        $with_schedule_date = true; /*需依照日程搜尋*/
        $params['date']= $params['schedule_date']; /*搜尋日程有設定此日期的*/
      }
    }

    /* 處理回傳欄位 */
    $field_query = 'e.evesno, e.title as eve_title, e.cum_id as crm_id, e.caseid,
                    es.start_time, es.end_time,
                    FROM_UNIXTIME(es.start_time) as start_time_format, FROM_UNIXTIME(es.end_time) as end_time_format';
    if($with_schedule_date){/* 有要撈取日程 */
      $field_query .= ', sd.*, sd.id as schedule_date_primary, 
                          FROM_UNIXTIME(sd.examine_time) as examine_time_format, FROM_UNIXTIME(sd.turn_salary_time) as turn_salary_time_format,
                          CONCAT(money.qh, "-", money.count) as qh_count';
      if($with_schedule_date_user){/* 有要撈取日程的人力 */
        $field_query .= ', sdu.*, sdu.id as schedule_date_user_primary, sdu.user_id as schedule_date_user_user_id,
                            FROM_UNIXTIME(sdu.worktime_s) as worktime_s_format,
                            FROM_UNIXTIME(sdu.worktime_e) as worktime_e_format,
                            FROM_UNIXTIME(sdu.roll_call_come) as roll_call_come_format,
                            FROM_UNIXTIME(sdu.roll_call_leave) as roll_call_leave_format,
                            FROM_UNIXTIME(sdu.roll_call_confirm) as roll_call_confirm_format,
                            u3.name as schedule_date_user_name, u3.ename as schedule_date_user_ename';
      }
    }
    $field_query .= ', s.*';
    // dump($field_query);exit;

    /* 處理條件篩選 */
    $where_query = 'true ';
    if(isset($params['schedule_id'])){ /*日程組id*/
      if($params['schedule_id']!==''){
        $where_query .= ' AND s.id="'.$params['schedule_id'].'"';
      }
    }
    if(isset($params['searchtext'])){ /*日程組關鍵字*/
      $where_query .= ' AND ( 
        s.name LIKE "%'.$params['searchtext'].'%" OR 
        s.location LIKE "%'.$params['searchtext'].'%"
      )';
    }
    if(isset($params['auto_money'])){ /*自動請款*/
      if($params['auto_money']!==''){
        $where_query .= ' AND s.auto_money ="'.$params['auto_money'].'"';
      }
    }
    
    if(isset($params['evesno'])){ /*事件編號*/
      if($params['evesno']!==''){
        $where_query .= ' AND e.evesno LIKE "%'.$params['evesno'].'%"';
      }
    }

    if(isset($params['sn'])){ /*合約編號*/
      if($params['sn']!==''){
        /*找出符合條件的合約id們*/
        $contract_ids = D('crm_contract c')->field('id')->where('c.sn LIKE "%'.$params['sn'].'%"')->index('id')->select();
        $contract_ids = array_keys($contract_ids);
        $contract_ids[] = -1;
        /*若為對應事件簿的日程組，則e的caseid要符合模糊比對後的id們，且eve_step_id!=0*/
        /*若為對應合約的日程組，則s的contract_id要符合模糊比對後的id們，且eve_step_id=0*/
        $where_query .= ' AND (
          (e.caseid IN ('.implode(',', $contract_ids).') AND s.eve_step_id!=0 ) OR
          (s.contract_id IN ('.implode(',', $contract_ids).') AND s.eve_step_id=0)
        )';
      }
    }
    if(isset($params['contract_id'])){ /*合約ID*/
      if($params['contract_id']!==''){
        $where_query .= ' AND (
          (e.caseid= "'.$params['contract_id'].'" AND s.eve_step_id!=0 ) OR
          (s.contract_id= "'.$params['contract_id'].'" AND s.eve_step_id=0)
        )';
      }
    }
    if(isset($params['contract_ids'])){ /*合約IDs*/
      if(count($params['contract_ids'])>0){
        $where_query .= ' AND (
          (e.caseid IN ('.implode(',', $params['contract_ids']).') AND s.eve_step_id!=0 ) OR
          (s.contract_id IN ('.implode(',', $params['contract_ids']).') AND s.eve_step_id=0)
        )';
      }
    }

    if(isset($params['crm_text'])){ /*客戶名稱/簡稱*/
      if($params['crm_text']!==''){
        /*找出符合條件的客戶id們*/
        $params['crm_text'] = strtolower($params['crm_text']);
        $crm_ids = D('crm_crm crm')->field('id')->where('
                    (LOWER(crm.name) LIKE "%'.$params['crm_text'].'%" OR LOWER(crm.nick) LIKE "%'.$params['crm_text'].'%" )
                  ')->index('id')->select();
        $crm_ids = array_keys($crm_ids);
        $crm_ids[] = -1;
        /*若為對應事件簿的日程組，則crm的cum_id要符合模糊比對後的id們，且eve_step_id!=0*/
        /*若為對應合約的日程組，則crm2的cid要符合模糊比對後的id們，且eve_step_id=0*/
        $where_query .= ' AND (
          (e.cum_id IN ('.implode(',', $crm_ids).') AND s.eve_step_id!=0 )OR
          (c2.cid IN ('.implode(',', $crm_ids).') AND s.eve_step_id=0)
        )';
      }
    }
    if(isset($params['crm_id'])){ /*客戶id*/
      if($params['crm_id']!==''){
        /*若為對應事件簿的日程組，則crm.id=指定id*/
        /*若為對應合約的日程組，則crm2.id=指定id且eve_step_id=0*/
        $where_query .= ' AND (
          e.cum_id="'.$params['crm_id'].'" OR
          (c2.cid="'.$params['crm_id'].'" AND s.eve_step_id=0)
        )';
      }
    }
    if(isset($params['user_id'])){ /*建立者(審核者)(對應事件步驟執行者)*/
      if($params['user_id']!==''){
        $where_query .= ' AND s.user_id="'.$params['user_id'].'"';
      }
    }
    if(isset($params['eve_step_id'])){ /*事件步驟id*/
      if($params['eve_step_id']!==''){
        $where_query .= ' AND s.eve_step_id="'.$params['eve_step_id'].'"';
      }
    }
    if($with_schedule_date){/* 有要撈取日程 */
      if(isset($params['schedule_date_primary'])){ /*日程id*/
        if($params['schedule_date_primary']!==''){
          $where_query .= ' AND sd.id="'.$params['schedule_date_primary'].'"';
        }
      }
      if(isset($params['turn_salary_time'])){ /*轉薪資時間*/
        if($params['turn_salary_time']=='1'){ /*已轉*/
          $where_query .= ' AND sd.turn_salary_time !=""';
        }else if($params['turn_salary_time']=='0'){ /*未轉*/
          $where_query .= ' AND sd.turn_salary_time =""';
        }
      }
      if(isset($params['turn_salary_time_s'])){ /*轉薪資時間-開始*/
        if($params['turn_salary_time_s']!==''){
          $where_query .= ' AND sd.turn_salary_time !="" AND sd.turn_salary_time>="'.$params['turn_salary_time_s'].'"';
        }
      }
      if(isset($params['turn_salary_time_e'])){ /*日程日期區間-結束*/
        if($params['turn_salary_time_e']!==''){
          $where_query .= ' AND sd.turn_salary_time !="" AND sd.turn_salary_time<="'.$params['turn_salary_time_e'].'"';
        }
      }
      if(isset($params['date'])){ /*日程日期*/
        if($params['date']!==''){
          $where_query .= ' AND sd.date="'.$params['date'].'"';
        }
      }
      if(isset($params['date_s'])){ /*日程日期區間-開始*/
        if($params['date_s']!==''){
          $where_query .= ' AND sd.date>="'.$params['date_s'].'"';
        }
      }
      if(isset($params['date_e'])){ /*日程日期區間-結束*/
        if($params['date_e']!==''){
          $where_query .= ' AND sd.date<="'.$params['date_e'].'"';
        }
      }
      if(isset($params['user_in_charge'])){ /*日程當日負責人員id*/
        if($params['user_in_charge']!==''){
          $where_query .= ' AND sd.user_in_charge="'.$params['user_in_charge'].'"';
        }
      }
      if(isset($params['moneyid'])){ /*請款單*/
        if($params['moneyid']!==''){
          $where_query .= ' AND sd.moneyid="'.$params['moneyid'].'"';
        }
      }

      if($with_schedule_date_user){/* 有要撈取日程的人力 */
        if(isset($params['schedule_date_user_primary_in'])){ /*日程人員主鍵id(IN)*/
          if(count($params['schedule_date_user_primary_in'])>0){
            $where_query .= ' AND sdu.id in ('.implode(',', $params['schedule_date_user_primary_in']).')';
          }
        }
        if(isset($params['schedule_date_user_user_id'])){ /*日程人員員工id*/
          if($params['schedule_date_user_user_id']!==''){
            $where_query .= ' AND sdu.user_id="'.$params['schedule_date_user_user_id'].'"';
          }
        }
        if(isset($params['schedule_date_user_user_id_in'])){ /*日程人員員工id(IN)*/
          if(count($params['schedule_date_user_user_id_in'])>0){
            $where_query .= ' AND sdu.user_id in ('.implode(',', $params['schedule_date_user_user_id_in']).')';
          }
        }
        if(isset($params['worktime_range'])){ /*日程人員上班時間區間*/ 
          if($params['worktime_range']!==''){
            $worktime_range = explode(',', $params['worktime_range']);
            $worktime_s = strtotime($worktime_range[0]??'1970-01-01');
            $worktime_e = strtotime($worktime_range[1]??'3000-01-01');
            $where_query .= ' AND (
              (sdu.worktime_s<="'.$worktime_s.'" AND sdu.worktime_e>="'.$worktime_s.'") OR 
              (sdu.worktime_s<="'.$worktime_e.'" AND sdu.worktime_e>="'.$worktime_e.'") OR
              (sdu.worktime_s<="'.$worktime_s.'" AND sdu.worktime_e>="'.$worktime_e.'") OR
              (sdu.worktime_s>="'.$worktime_s.'" AND sdu.worktime_e<="'.$worktime_e.'")
            )';
          }
        }
        if(isset($params['worktime_e'])){ /*日程人員上班時間區間-結束*/
          if($params['worktime_e']!==''){
            $where_query .= ' AND sdu.worktime_e<="'.$params['worktime_e'].'"';
          }
        }
        if(isset($params['user_skill'])){ /*日程人員技術*/
          if($params['user_skill']!==''){
            $where_query .= ' AND sdu.user_skill="'.$params['user_skill'].'"';
          }
        }
        if(isset($params['mens_keyword'])){ /*日程人員關鍵字*/
          if($params['mens_keyword']!==''){
            $where_query .= ' AND (
              u3.name LIKE "%'.$params['mens_keyword'].'%" OR 
              u3.ename LIKE "%'.$params['mens_keyword'].'%" OR 
              u3.phone LIKE "%'.$params['mens_keyword'].'%" OR 
              u3.mphone LIKE "%'.$params['mens_keyword'].'%" OR 
              u3.email LIKE "%'.$params['mens_keyword'].'%" OR 
              u3.email2 LIKE "%'.$params['mens_keyword'].'%"
            )';
          }
        }
        if(isset($params['roll_called'])){ /*點名相關篩選*/
          if($params['roll_called']==1){ /*有點過名的*/
            $where_query .= ' AND (sdu.roll_call_come !="" OR sdu.roll_call_leave !="")';
          }else if($params['roll_called']==2){/*有點過名的 或 會計審核過的*/
            $where_query .= ' AND (
              (sdu.roll_call_come !="" OR sdu.roll_call_leave !="") OR 
              sdu.roll_call_confirm !=""
            )';
          }
        }
        if(isset($params['schedule_date_user_pay_id'])){ /*對應付款*/
          if($params['schedule_date_user_pay_id']==-1){
            $where_query .= ' AND sdu.schedule_date_user_pay_id!="0"';
          }
          else if($params['schedule_date_user_pay_id']!==''){
            $where_query .= ' AND sdu.schedule_date_user_pay_id="'.$params['schedule_date_user_pay_id'].'"';
          }
        }
        if(isset($params['schedule_date_user_pay_ids'])){ /*對應付款(在列舉付款單id中)*/
          if(count($params['schedule_date_user_pay_ids'])){
            $where_query .= ' AND sdu.schedule_date_user_pay_id in ('.implode(',', $params['schedule_date_user_pay_ids']).')';
          }
        }
        if(isset($params['pay_count_type'])){ /*統計方式人員*/
          if($params['pay_count_type']!==''){
            $where_query .= ' AND u3.pay_count_type = "'.$params['pay_count_type'].'"';
          }
        }
      }
    }
    /* 排除垃圾桶事件 */
    $where_query .= ' AND (e.result != 10 OR s.eve_step_id=0)';
    // dump($where_query);exit;

    /* 處理排序 */
    $order_query = 's.id desc';
    if($with_schedule_date){/* 有要撈取日程 */
      $order_query = 'sd.date desc, sd.id desc, '.$order_query;
      if($with_schedule_date_user){/* 有要撈取日程的人力 */
        $order_query = 'sdu.user_id asc, sdu.worktime_s asc, '.$order_query;
      }
    }
    // dump($order_query);exit;

    $schedules = D('schedule as s')
                ->field($field_query);
    if($with_schedule_date){/* 有要撈取日程 */
      $schedules = $schedules->join('schedule_date as sd ON sd.schedule_id=s.id','right')
                              ->join('crm_othermoney as money ON money.id=sd.moneyid','left');
      if($with_schedule_date_user){/* 有要撈取日程的人力 */
        $schedules = $schedules->join('schedule_date_user as sdu ON sdu.schedule_date_id=sd.id','right')
                              ->join('eip_user as u3 ON u3.id=sdu.user_id','left');
      }
    }
    $schedules = $schedules->join('eve_steps as es ON es.id=s.eve_step_id','left')
                          ->join('eve_events as e ON e.id=es.eve_id','left')
                          ->join('crm_contract as c2 ON c2.id=s.contract_id','left')
                          ->where($where_query)
                          ->order($order_query);
    if(isset($params['count_of_page'])){ /*處理分頁*/
      if($params['count_of_page']){
        $current_page = $params['current_page'] ?? 1;
        $index_start = ($current_page -1) < 0 ? 0 : ($current_page -1) * $params['count_of_page'];
        $schedules->limit($index_start, $params['count_of_page']);
      }
    }
    // dump($schedules->fetchSql(true)->select());exit;
    $schedules = $schedules->select();

    $eip_user_obj = D('eip_user')->field('id, name, ename')->index('id')->select();
    $user_skill_obj = D('user_skill')->field('id, name')->index('id')->select();
    foreach ($schedules as $key => $value) {
      if($value['eve_step_id']==0){ /*若對應關係非來自事件簿*/
        $schedules[$key]['caseid'] = $value['contract_id'];
      }
      $crm_contract = D('crm_contract')->where('id ='.$schedules[$key]['caseid'])->find();
      $schedules[$key]['crm_id'] = $crm_contract['cid'] ?? '-1';
      $schedules[$key]['sn'] = $crm_contract['sn'] ?? '';
      // $get_data['crm_contract_id'] = $schedules[$key]['caseid'] ?? -1;
      // $contracts = ContractHelper::get_contracts($get_data, 0, false);
      // if(count($contracts)>0){
      //   $schedules[$key]['crm_id'] = $contracts[0]['cid'];
      //   $schedules[$key]['sn'] = $contracts[0]['sn'];
      // }else{
      //   $schedules[$key]['crm_id'] = '-1';
      //   $schedules[$key]['sn'] = '';
      // }
      unset($schedules[$key]['contract_id']); /*統一回傳的對應合約欄位為 caseid*/
      $schedules[$key]['show_name'] = CustoHelper::get_crm_show_name($schedules[$key]['crm_id']);
      $schedules[$key]['show_name_full'] = CustoHelper::get_crm_show_name($schedules[$key]['crm_id'], true);

      /*調整回傳資料*/
      $schedules[$key]['user_name'] = $eip_user_obj[$value['user_id']]['name'] ?? ''; /*審核者人名*/
      if($with_schedule_date){/* 有要撈取日程 */
        $schedules[$key]['user_in_charge_name'] = $eip_user_obj[$value['user_in_charge']]['name'] ?? ''; /*當日管理者人名*/
        if($with_schedule_date_user){/* 有要撈取日程的人力 */
          $schedules[$key]['user_skill_name'] = $user_skill_obj[$value['user_skill']]['name'] ?? ''; /*工種名稱*/
        }
      }
    }
    // dump($schedules);exit;
    return $schedules;
  }
  public static function get_schedule_user_skill($schedule_id){
    if(in_array(153, self::$use_function_top)){
      $tabel_name = 'schedule_user_skill'; 
      $schedule_user_skills = M($tabel_name)->where('schedule_id="'.$schedule_id.'"')->order("id asc")->select();
      // dump($schedule_user_skills);exit;
    }else{
      $schedule_user_skills = [];
    }

    $data['schedule_user_skills'] = $schedule_user_skills;
    // dump($data);exit;

    return $data;
  }

  public static function eve_step_create($eve_step_id){
    $eve_step = FigHelper::get_eve_by_step_id($eve_step_id);
    // dump($eve_step);exit;
    if(!$eve_step){ throw new \Exception('連結有誤'); }
    if($eve_step['step_id']!=3){ throw new \Exception('連結有誤'); }
      
    $new_schedule_name = $eve_step['eve_title'];
    $new_schedule_name.= $eve_step['content'] ? '-'.$eve_step['content'] : '';
    $data = [
      'name' => $new_schedule_name,
      'location' => "",
      'eve_step_id' => $eve_step['id'],
    ];
    // dump($data);exit;
    try {
      $id = self::set_schedule($data);
    } catch (\Exception $e) {
      $schedule =D('schedule')->where('eve_step_id="'.$eve_step_id.'"')->find();
      if(!$schedule){ throw new \Exception('連結有誤，或權限不足'); }
      $id = $schedule['id'];
    }
    return $id;
  }
  public static function set_schedule($data=[]){
    $id = $data['id'] ?? ''; unset($data['id']);
    $eve_step_id = $data['eve_step_id'] ?? '';
    // dump($data);exit;
    if($data['name']==''){ throw new \Exception('請設定名稱'); }
    // if($data['location']==''){ throw new \Exception('請設定地點'); }
    // if($data['location_gps']==''){ throw new \Exception('請設定地點GPS'); }
    $units = $data['units'] ?? []; unset($data['units']);
    $units_del = $data['units_del'] ?? []; unset($data['units_del']);

    if($id){ /*編輯*/
      if($eve_step_id){
        $schedules = ScheduleHelper::get_schedules(['eve_step_id'=>$eve_step_id]);
        foreach ($schedules as $schedule) {
          if($id!=$schedule['id']){
            throw new \Exception('一個事件步驟只能對應一個日程組');
          }
        }
      }
      D('schedule')->where('id="'.$id.'"')->data($data)->save();
      Common::error_log('編輯日程組, ID：'.$id.', 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    else{ /*新增*/
      if($eve_step_id){
        $eve = FigHelper::get_eve_by_step_id($eve_step_id);
        if(!$eve){ throw new \Exception('連結有誤'); }
        if($eve['step_id']!=3){ throw new \Exception('連結有誤'); }

        $schedules = ScheduleHelper::get_schedules(['eve_step_id'=>$eve_step_id]);
        if(count($schedules)>0){
          throw new \Exception('一個事件步驟只能對應一個日程組');
        }
        $data['user_id'] = $eve['user_id'];
      }else{
        $data['user_id'] = session('adminId');
      }
      // dump($data);exit;
      $id = D('schedule')->data($data)->add();
      Common::error_log('新增日程組, 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    self::save_crm_contract_user_skill($id, $units, $units_del);
    return $id;
  }
  private function save_crm_contract_user_skill($schedule_id, $units=[], $units_del=[]){
    $table_name = 'schedule_user_skill';
    /*編輯或新增*/
    foreach ($units as $key => $unit) {
      $unit['schedule_id'] = $schedule_id;
      $unit_id = isset($unit['id']) ? $unit['id'] : 0;
      $unit_id = $unit_id ? $unit_id : 0;
      unset($unit['id']);
      if($unit_id){ /*編輯*/
        D($table_name)->data($unit)->where('id="'.$unit_id.'"')->save();
      }else{ /*新增*/
        D($table_name)->data($unit)->add();
      }
    }
    /*刪除*/
    foreach ($units_del as $key => $unit_id) {
      D($table_name)->where('id="'.$unit_id.'"')->delete();
    }
  }
  public static function delete_schedule($schedule_id=0){
    $access = Common::get_my_access();
    if($access['schedule_del'] != '1'){
      throw new \Exception('您沒有權限操作');
    }

    $schedule = D('schedule')->where('id="'.$schedule_id.'"')->find();
    if(!$schedule){  throw new \Exception('無此對象'); }
    // 檢查是否有新增日程?

    D('schedule')->where('id="'.$schedule_id.'"')->delete();
    D('schedule_user_skill')->where('schedule_id="'.$schedule_id.'"')->delete();
    Common::error_log('刪除日程組, id：'.$schedule_id);
  }


  public static function set_schedule_date($data=[], $need_remind=true){
    $schedule_date_primary = $data['schedule_date_primary'] ?? '';
    unset($data['schedule_date_primary']);
    unset($data['examine_time']);     /*不允許修改驗收時間*/
    unset($data['turn_salary_time']); /*不允許修改拋轉薪資時間*/
    // dump($data);exit;
    if(!$data['schedule_id']){ throw new \Exception('請設定工作id'); }

    if(!$data['date']){ throw new \Exception('請設定工作日期'); }
    if(!$data['user_in_charge']){ throw new \Exception('請設定當日管理者'); }

    $schedules = self::get_schedules(['schedule_id'=>$data['schedule_id']]);
    if(count($schedules)!=1){ throw new \Exception('設定之工作id無效'); }

    // dump($data);exit;
    if($schedule_date_primary){ /*編輯*/
      $schedule_dates = self::get_schedules([
        'schedule_id'=>$data['schedule_id'],
        'date'=>$data['date'],
      ], true);
      foreach ($schedule_dates as $schedule_date) {
        if($schedule_date_primary!=$schedule_date['schedule_date_primary']){
          throw new \Exception('此工作在'.$data['date'].'已有日程，無法重複新增');
        }
      }
      D('schedule_date')->where('id="'.$schedule_date_primary.'"')->data($data)->save();
      Common::error_log('編輯日程, ID：'.$schedule_date_primary.', 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    else{ /*新增*/
      $schedule_dates = self::get_schedules([
        'schedule_id'=>$data['schedule_id'],
        'date'=>$data['date'],
      ], true);
      if(count($schedule_dates)>0){
        throw new \Exception('此工作在'.$data['date'].'已有日程，無法重複新增');
      }
      $schedule_date_primary = D('schedule_date')->data($data)->add();
      Common::error_log('新增日程, 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    if($need_remind){
      /*提醒當日管理者排班*/
      $subject = '需設定'.$data['date'].'班表('.$schedules[0]['name'].')';
      $open_url = 'http://'.$_SERVER['HTTP_HOST'].U('ScheduleDetail/users').'?schedule_date_primary='.$schedule_date_primary;
      $body ="
        <p>您好：</p>
        <p>".$data['date']."的".$schedules[0]['name']."需您安排班表</p>
        <p>請點擊下方網址進入設定，以利工作進行</p>
        <p>網址：<a href='".$open_url."'>".$open_url."</a></p>
      ";
      send_email_user_id($body, $data['user_in_charge'], $subject);
      $payload = [
        'title' => $subject,
        'msg' => "請登入系統查看",
        'open_url' => $open_url,
      ];
      $result = Common::send_notification_to_user($data['user_in_charge'], $payload);
    }
    return ['schedule_date_primary'=>$schedule_date_primary];
  }
  public static function delete_schedule_date($schedule_date_primary=0){
    $schedule_date = D('schedule_date')->where('id="'.$schedule_date_primary.'"')->find();
    if(!$schedule_date){ throw new \Exception('無此對象'); }
    
    // 檢查是否有新增排班狀態
    if($schedule_date['turn_salary_time']){ throw new \Exception('已轉薪資，無法刪除'); }
    if($schedule_date['examine_time']){ throw new \Exception('已驗收，無法刪除'); }
    // dump($schedule_date_primary);exit;

    D('schedule_date')->where('id="'.$schedule_date_primary.'"')->delete();
    Common::error_log('刪除日程, id：'.$schedule_date_primary);
  }

  public static function check_schedule_right_new($schedule_id, $eve_step_id=''){
    $access = Common::get_my_access();
    if($access['schedule_new'] == '1'){
      return true;
    }else{
      $schedule = ScheduleHelper::get_schedules(['schedule_id'=>$schedule_id]);
      if(count($schedule)==1){
        $schedule = $schedule[0];
        if(session('adminId')==$schedule['user_id']){ /*建立者*/
          return true;
        }
        $eve_step_id = $schedule['eve_step_id'];
      }
      if($eve_step_id){
        $eve = FigHelper::get_eve_by_step_id($eve_step_id);
        if($eve['user_id']==session('adminId')){
          return true;
        }
      }
      return false;
    }
  }
}
?>