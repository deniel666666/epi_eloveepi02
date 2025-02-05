<?php
namespace Photonic;
use Think\Controller;

use Photonic\Common;
use Photonic\ScheduleHelper;
use Photonic\MensHelper;
use Photonic\SalaryHelper;

class ScheduleDetailHelper extends Controller
{
  /*日程檢查用階段(可根據不同階段設定不同權限檢查)*/
  public static $schedule_status_to_check = [
    'turn_salary_time', /*檢查到是否已拋薪*/
    'examine_time', /*檢查到是否已驗收*/
    'all', /*檢查全部*/
  ];
  function _initialize(){
  }
  public static function instance(){
    return new ScheduleDetailHelper();
  }

  public static function copy_date(int $schedule_id, int $schedule_date_primary, array $period_array, array $user_ids=[]){
    if(!$schedule_id){ throw new \Exception('資料有誤'); }
    if(!$schedule_date_primary){ throw new \Exception('資料有誤'); }
    if(count($period_array)==0){ throw new \Exception('資料有誤'); }

    $schedule_date = D('schedule_date')->where('schedule_id="'.$schedule_id.'" AND id="'.$schedule_date_primary.'"')->find();
    if(!$schedule_date){ throw new \Exception('無此日程'); }
    unset($schedule_date['id']);
    $schedule_date['examine_note'] = '';
    $schedule_date['examine_time'] = '';
    $schedule_date['turn_salary_time'] = '';
    $schedule_date['turn_salary_time_name'] = '';
    $schedule_date['moneyid'] = 0;
    $schedule_date['create_money_name'] = '';
    /*複製對象-名單*/
    $user_in_query = '';
    if(count($user_ids)>0){ $user_in_query = 'user_id in ('.implode(',', $user_ids).') AND '; }
    $date_users = D('schedule_date_user')->where($user_in_query.' schedule_date_id="'.$schedule_date_primary.'"')->select();
    // dump($date_users);exit;
    foreach ($date_users as $key => $value) {
      unset($date_users[$key]['id']);
      $date_users[$key]['roll_call_come'] = '';
      $date_users[$key]['roll_call_come_name'] = '';
      $date_users[$key]['roll_call_leave'] = '';
      $date_users[$key]['roll_call_leave_name'] = '';
      $date_users[$key]['roll_call_confirm'] = '';
      $date_users[$key]['roll_call_confirm_name'] = '';
      $date_users[$key]['pay_total'] = 0;
      $date_users[$key]['change_num'] = 0;
      $date_users[$key]['schedule_date_user_pay_id'] = 0;
    }
    // dump($date_users);exit;

    /*複製對象-驗收項目*/
    $date_reports = D('schedule_date_report')->where('schedule_date_id="'.$schedule_date_primary.'"')->select();
    foreach ($date_reports as $key => $value) {
      unset($date_reports[$key]['id']);
      $date_reports[$key]['note_examine'] = '';
      $date_reports[$key]['note_examine_time'] = '';
    }
    // dump($date_reports);exit;

    foreach ($period_array as $date) {
      $schedule_date['date'] = $date;
      $salary_ym = substr($date,0,4).substr($date,5,2);

      try {
        // dump($schedule_date);exit;
        /*嘗試建立日程*/
        $result = ScheduleHelper::set_schedule_date($schedule_date, false);
        $schedule_date_primary = $result['schedule_date_primary'];
      } catch (\Throwable $th) {
        /*有重複日程*/
        $schedule_dates = ScheduleHelper::get_schedules([
          'schedule_id'=>$schedule_date['schedule_id'],
          'date'=>$schedule_date['date'],
        ], true);
        $schedule_date_primary = $schedule_dates[0]['schedule_date_primary'];
      }
      // dump($schedule_date_primary);
      /*逐個使用者建立複製新增的名單資料*/
      foreach ($date_users as $key => $value) {
        $date_users[$key]['schedule_date_id'] = $schedule_date_primary;
        /*調整工作時段*/
        $date_users[$key]['worktime_s'] = strtotime($date.' '.date('H:i:s', $date_users[$key]['worktime_s']));
        $date_users[$key]['worktime_e'] = strtotime($date.' '.date('H:i:s', $date_users[$key]['worktime_e']));
        /*依照日期更新人員時薪*/
        $salary_record = MensHelper::get_user_salary_record($value['user_id'], $salary_ym, $value['user_skill']);
        if($salary_record){
          if(count($salary_record['salary_records_skill'])>0){
            $date_users[$key]['user_hour_pay'] = $salary_record['salary_records_skill'][0]['hour_pay'];
            $date_users[$key]['user_hour_pay_over'] = $salary_record['salary_records_skill'][0]['hour_pay_over'];
          }
        }
      }
      D('schedule_date_user')->addAll($date_users);

      foreach ($date_reports as $key => $value) {
        $date_reports[$key]['schedule_date_id'] = $schedule_date_primary;
      }
      D('schedule_date_report')->addAll($date_reports);
    }
  }

  public static function get_user_ids_used($date, $schedule_date_primary='', $worktime=['00:00:00', '23:59:59']){
    $params['date'] = $date;
    $params['worktime_range'] = $date.' '.$worktime[0].','.$date.' '.$worktime[1];
    if($schedule_date_primary){ $params['schedule_date_primary'] = $schedule_date_primary; }
    $schedules = ScheduleHelper::get_schedules($params, true, true);
    $ids = [0];
    foreach ($schedules as $schedule) {
      array_push($ids, $schedule['schedule_date_user_user_id']);
    }
    return $ids;
  }
  public static function get_mens_available($schedule_date_primary='', $params=[]){
    $schedule_date = D('schedule_date')->where('id="'.$schedule_date_primary.'"')->find();
    if(!$schedule_date){ return []; }

    $users = MensHelper::get_mens_working([], $params);    
    $skillid = $params['skillid'] ?? '';
    $salary_ym = date('Ym', strtotime($schedule_date['date']));
    foreach ($users as $key => $value) {
      $salary_record = MensHelper::get_user_salary_record($value['id'], $salary_ym, $skillid);
      unset($users[$key]['userpw']);
      $users[$key]['salary_records_skill'] = $salary_record['salary_records_skill'];
      $users[$key]['hour_pay'] = $salary_record['pay_hour'] ?? 0;
      $search_data = [
        'date'=>$schedule_date['date'],
        'schedule_date_user_user_id'=>$value['id'],
      ];
      $users[$key]['used'] = count(ScheduleHelper::get_schedules($search_data, true, true));

      // 檢查該日是否有請假紀錄(rest_records， apply_status需為0)
      $map                 = array();
      $map['user_id']      = $value['id'];
      $map['apply_status'] = 0;
      $rest_records = D('rest_records')->where($map)->where("'%s' BETWEEN rest_day_s AND rest_day_e", $schedule_date['date'])->select();
      $users[$key]['rest'] = count($rest_records);
    }
    if($skillid){ /*有依技能篩選*/
      $users = array_filter($users, function($item){ return count($item['salary_records_skill'])>0; });
    }
    return $users;
  }
  public static function get_schedule_date_users($params){
    if(isset($params['schedule_date_primary'])){
      if(!self::check_schedule_detail_right_view($params['schedule_date_primary'])){ // 沒有檢視權限
        // $params['schedule_date_user_user_id'] = session('adminId');  // 限看自己
      }
    }
    $data = ScheduleHelper::get_schedules($params, true, true);
    foreach ($data as $key => $value) {
      $search_data = [
        'date'=>$value['date'],
        'schedule_date_user_user_id'=>$value['schedule_date_user_user_id'],
      ];
      /*檢查是否重複*/
      $data[$key]['used'] = count(ScheduleHelper::get_schedules($search_data, true, true));

      // 檢查該日是否有請假紀錄(rest_records， apply_status需為0)
      $map                 = array();
      $map['user_id']      = $value['schedule_date_user_user_id'];
      $map['apply_status'] = 0;
      $rest_records = D('rest_records')->where($map)->where("'%s' BETWEEN rest_day_s AND rest_day_e", $value['date'])->select();
      $data[$key]['rest'] = count($rest_records);

      /*取得對應薪資的工種選項*/
      $salary_ym = date('Ym', strtotime($value['date']));
      $salary_record = MensHelper::get_user_salary_record($value['schedule_date_user_user_id'], $salary_ym);
      $data[$key]['salary_records_skill'] = $salary_record['salary_records_skill'];
    }
    return $data;
  }

  public static function set_schedule_date_user($schedule_date_primary, $ids, $skillid='', $worktime=['00:00:00', '23:59:59']){
    $params['schedule_date_primary'] = $schedule_date_primary;
    $schedules = ScheduleHelper::get_schedules($params, true);
    if(count($schedules)!=1){ throw new \Exception('資料有誤'); }
    if(!self::check_schedule_detail_right_edit($schedules[0]['schedule_date_primary'], 'turn_salary_time')){
      throw new \Exception('您無權限，或已無法修改');
    }

    /*檢查人員是否已於 該日 該日程 被指定到*/
    $user_ids_used = self::get_user_ids_used($schedules[0]['date'], $schedule_date_primary, $worktime);
    foreach ($ids as $id) {
      if(in_array($id, $user_ids_used)){
        $user_data = MensHelper::get_user_data($id);
        $msg = $user_data['name'];
        $msg.= '已於'.$schedules[0]['date'];
        $msg.= '參與'.$schedules[0]['name'].'，無法選擇';
        throw new \Exception($msg);
      }
    }

    /*建立資料*/
    $worktime_s = strtotime($schedules[0]['date'].' '.$worktime[0]);
    $worktime_e = strtotime($schedules[0]['date'].' '.$worktime[1]);
    $salary_ym = date('Ym', $worktime_s);
    
    foreach ($ids as $id) {
      $salary_record = MensHelper::get_user_salary_record($id, $salary_ym, $skillid);
      $salary_records_skill = $salary_record['salary_records_skill'];
      if(count($salary_records_skill)>0){
        $skillid = $salary_records_skill[0]['user_skill_id'];
        $hour_pay = $salary_records_skill[0]['hour_pay'];
        $hour_pay_over = $salary_records_skill[0]['hour_pay_over'];
      }else{
        $skillid =  0;
        $hour_pay =  0;
        $hour_pay_over = 0;
      }

      $data = [
        'schedule_date_id' => $schedule_date_primary,
        'worktime_s' => $worktime_s,
        'worktime_e' => $worktime_e,
        'user_id' => $id,
        'user_skill' => $skillid,
        'user_hour_pay' => $hour_pay,
        'user_hour_pay_over' => $hour_pay_over,
        'do_hour' => ceil(($worktime_e-$worktime_s) / (60*60)),
        'do_hour_overtime' => 0,
      ];
      // dump($data);exit;
      D('schedule_date_user')->data($data)->add();
    }
    Common::error_log('設定日程之工作者, 日程id：'.$schedule_date_primary.', 員工id:'.json_encode($ids, JSON_UNESCAPED_UNICODE));
  }
  public static function delete_schedule_date_user($schedule_date_user_primary=0){
    $schedule_date_user = D('schedule_date_user')->where('id="'.$schedule_date_user_primary.'"')->find();
    if(!$schedule_date_user){ throw new \Exception('無此對象'); }
    if(!self::check_schedule_detail_right_edit($schedule_date_user['schedule_date_id'], 'turn_salary_time')){
      throw new \Exception('您無權限，或已無法修改');
    }

    // dump($schedule_date_user_primary);exit;
    D('schedule_date_user')->where('id="'.$schedule_date_user_primary.'"')->delete();
    Common::error_log('刪除日程之工作者, id：'.$schedule_date_user_primary);
  }
  public static function update_schedule_date_user($schedule_date_user_primary=0, $data=[]){
    $schedule_date_user = D('schedule_date_user')->where('id="'.$schedule_date_user_primary.'"')->find();
    if(!$schedule_date_user){ throw new \Exception('無此對象'); }
    if(!self::check_schedule_detail_right_edit($schedule_date_user['schedule_date_id'], 'turn_salary_time')){
      throw new \Exception('您無權限，或已無法修改');
    }
    $update_data = [];
    foreach ($data as $key => $value) {
      if($key=='user_skill'){ /*修改技能，需要更改時薪*/
        $update_data[$key] = $value;
        /*取得對應薪資的工種選項*/
        $schedule_date = D('schedule_date')->where('id="'.$schedule_date_user['schedule_date_id'].'"')->find();
        if(!$schedule_date){ throw new \Exception('無此對象'); }
        $salary_ym = date('Ym', strtotime($schedule_date['date']));
        $salary_record = MensHelper::get_user_salary_record($schedule_date_user['user_id'], $salary_ym, $value);
        $salary_records_skill = $salary_record['salary_records_skill'];
        $update_data['user_hour_pay'] = count($salary_records_skill)>0 ? $salary_records_skill[0]['hour_pay'] : 0;
        $update_data['user_hour_pay_over'] = count($salary_records_skill)>0 ? $salary_records_skill[0]['hour_pay_over'] : 0;
      }
      else if(in_array($key, ['roll_call_come', 'roll_call_leave', 'roll_call_confirm'])){
        if($schedule_date_user[$key]){ throw new \Exception('重複操作'); }
        $update_data[$key] = $value != -1 ? time() : '';
        $update_data[$key.'_name'] = session('userName');
      }
      else if(in_array($key, ['do_hour', 'do_hour_overtime'])){
        if($value<0){ throw new \Exception('不可為負數'); }
        $update_data[$key] = $value;
      }
      else if(in_array($key, ['note', 'change_num'])){
        $update_data[$key] = $value;
      }
    }
    if(!$update_data){ throw new \Exception('資料不完整'); }
    // dump($update_data);exit;
    D('schedule_date_user')->where('id="'.$schedule_date_user_primary.'"')->data($update_data)->save();
    Common::error_log('編輯日程之工作者, id：'.$schedule_date_user_primary.', 資料:'.json_encode($update_data, JSON_UNESCAPED_UNICODE));
  }

  public static function get_schedule_date_reports($params, $google_file=true){
    /* 處理條件篩選 */
    $where_query = 'true ';
    if(isset($params['schedule_date_primary'])){ /*日程id*/
      if($params['schedule_date_primary']!==''){
        $where_query .= ' AND sdr.schedule_date_id="'.$params['schedule_date_primary'].'"';
      }
    }
    if(isset($params['report_keyword'])){ /*日程驗收關鍵字*/
      if($params['report_keyword']!==''){
        $where_query .= ' AND (
          sdr.name LIKE "%'.$params['report_keyword'].'%" OR
          sdr.note LIKE "%'.$params['report_keyword'].'%"
        )';
      }
    }
    // dump($where_query);exit;
    $date_report = D('schedule_date_report sdr')
                    ->field('sdr.*, FROM_UNIXTIME(sdr.note_examine_time) as note_examine_time_format')
                    ->where($where_query)
                    ->order('id asc')->select();
    if($google_file){
      $GoogleStorage = new GoogleStorage();
      foreach ($date_report as $key => $value) {
        $date_report[$key]['imgs'] = $GoogleStorage->show_files('schedule_date_report/'.$value['id'], true);
      }
    }
    return $date_report;
  }
  public static function set_schedule_date_report($data){
    unset($data['note_examine_time_format']);
    $id = $data['id'] ?? ''; unset($data['id']);
    $schedule_date_primary = $data['schedule_date_id'] ?? '';
    if(!$schedule_date_primary){ throw new \Exception('資料有誤'); }
    if($data['name']==''){ throw new \Exception('請輸入名稱'); }

    if($id){ /*編輯*/
      /*權限判斷*/
      self::check_schedule_detail_right_edit_by_report_id($id);
      if(!ScheduleDetailHelper::check_schedule_detail_right_examine($schedule_date_primary)){
        unset($data['note_examine']);
        unset($data['note_examine_time']);
      }else{
        $data['note_examine_time'] = $data['note_examine'] ? time() : '';
      }
      // dump($data);exit;
      D('schedule_date_report')->where('id="'.$id.'"')->data($data)->save();
      Common::error_log('編輯日程驗收項目, ID：'.$id.', 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    else{ /*新增*/
      /*權限判斷*/
      if(!self::check_schedule_detail_right_edit($schedule_date_primary)){
        throw new \Exception('您無權限，或已無法修改');
      }
      unset($data['note_examine']);
      unset($data['note_examine_time']);
      $id = D('schedule_date_report')->data($data)->add();
      Common::error_log('新增日程驗收項目, 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    }
  }
  public static function delete_schedule_date_report($data){
    $id = $data['id'] ?? '';
    /*權限判斷*/
    self::check_schedule_detail_right_edit_by_report_id($id);

    // dump($id);exit;
    D('schedule_date_report')->where('id="'.$id.'"')->delete();
    Common::error_log('刪除日程之工作者, id：'.$id);
  }
  public static function upload_files($data){/*上傳google storage檔案*/
    // dump($_FILES);exit;
    // dump($_POST);exit;
    $id = $_POST['id'] ?? '';
    /*權限判斷*/
    self::check_schedule_detail_right_edit_by_report_id($id);

    $GoogleStorage = new GoogleStorage();
    $file_path = 'schedule_date_report/'.$id;
    $imgs = $GoogleStorage->upload('google_file', $file_path);
    D('schedule_date_report')->where('id="'.$id.'"')->data([
      'imgs' => json_encode($imgs, JSON_UNESCAPED_UNICODE),
    ])->save();
    Common::error_log('上傳日程驗收項目圖片, ID：'.$id.', 資料：'.json_encode($imgs, JSON_UNESCAPED_UNICODE));
  }
  public static function delete_file($data){/*刪除google storage檔案*/
    $id = $data['id'] ?? '';
    $file_path = $data['file_path'] ?? '';
    /*權限判斷*/
    self::check_schedule_detail_right_edit_by_report_id($id);

    $GoogleStorage = new GoogleStorage();
    $GoogleStorage->delete($file_path);
  }

  public static function save_report_model($data){
    $schedule_date_primary = $data['schedule_date_primary'] ?? '';
    $params = ['schedule_date_primary'=>$schedule_date_primary, ];
    $date_report = self::get_schedule_date_reports($params, false);
    if(count($date_report)==0){ throw new \Exception('請先新增驗收項目後再存成模組'); }

    $name = $data['name'] ?? '';
    if($name==''){ throw new \Exception('請輸入模組名稱'); }
    $add_data = ['name'=>$name, ];
    $model_id = D('date_report_model')->data($add_data)->add();

    foreach ($date_report as $value) {
      $add_data = [
        'date_report_model_id' => $model_id,
        'name' => $value['name'],
        'note' => $value['note'],
      ];
      D('date_report_model_detail')->data($add_data)->add();
    }
    Common::error_log('新增驗收模板,ID：'.$model_id.', 資料：'.json_encode($date_report, JSON_UNESCAPED_UNICODE));
  }
  public static function get_report_models($params=[]){
    $date_report_model = D('date_report_model')->order('id asc')->select();
    return $date_report_model;
  }
  public static function use_report_model($data){
    $schedule_date_primary = $data['schedule_date_primary'] ?? '';

    $model_id = $data['model_id'] ?? '';
    $date_report_model_detail = D('date_report_model_detail')->where('date_report_model_id="'.$model_id.'"')->order('id asc')->select();
    if(count($date_report_model_detail)==0){ throw new \Exception('此模組並無內容，無法套用'); }

    $params = ['schedule_date_primary'=>$schedule_date_primary, ];
    $date_report = self::get_schedule_date_reports($params, false);
    foreach ($date_report as $value) {
      self::delete_schedule_date_report(['id'=>$value['id']]);
    }
    foreach ($date_report_model_detail as $value) {
      unset($value['id']);
      unset($value['date_report_model_id']);
      $value['schedule_date_id'] = $schedule_date_primary;
      self::set_schedule_date_report($value);
    }
    Common::error_log('套用驗收模板,ID：'.$model_id.', 資料：'.json_encode($date_report_model_detail, JSON_UNESCAPED_UNICODE));
  }
  public static function delete_report_model($data){
    $id = $data['id'] ?? '';
    // dump($id);exit;
    D('date_report_model')->where('id="'.$id.'"')->delete();
    D('date_report_model_detail')->where('date_report_model_id="'.$id.'"')->delete();
    Common::error_log('刪除驗收模板, id：'.$id);
  }

  public static function set_schedule_date_examine($data){
    $id = $data['schedule_date_primary'] ?? ''; unset($data['schedule_date_primary']);
    if(!$id){ throw new \Exception('資料有誤'); }
    if(!ScheduleDetailHelper::check_schedule_detail_right_examine($id)){ 
      throw new \Exception('您無權限，或已無法修改');
    }
    if(!$data['examine_note']){ throw new \Exception('請輸入批示內容'); }
    $data['examine_time'] = time();

    D('schedule_date')->where('id="'.$id.'"')->data($data)->save();
    Common::error_log('日程驗收, ID：'.$id.', 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));

    return $data['examine_time'];
  }
  public static function schedule_date_turn_salary($data){
    $id = $data['schedule_date_primary'] ?? ''; unset($data['schedule_date_primary']);
    if(!$id){ throw new \Exception('資料有誤'); }
    if(!ScheduleDetailHelper::check_schedule_detail_right_examine($id)){ 
      throw new \Exception('您無權限，或已無法修改');
    }
    $schedule_date = D('schedule_date')->where('id="'.$id.'"')->find();
    if($schedule_date['turn_salary_time']){ throw new \Exception('薪資已拋轉過'); }
    if(!$schedule_date['examine_time']){ throw new \Exception('請先批示總驗收'); }

    /*生成付款資料*/
    $schedule_date_user = D('schedule_date_user')->where('schedule_date_id="'.$id.'"')->select();
    foreach ($schedule_date_user as $date_user) {
      if(!$date_user['roll_call_come'] && !$date_user['roll_call_leave']){ /*沒上班也沒下班*/
        $do_hour = 0;           /*時數歸0*/
        $do_hour_overtime = 0;  /*時數歸0*/
      }else{
        $do_hour = $date_user['do_hour'];
        $do_hour_overtime = $date_user['do_hour_overtime'];
      }
      /*更新工作時數*/
      $update_data['do_hour'] = $do_hour;
      $update_data['do_hour_overtime'] = $do_hour_overtime;
      /*計算正規薪資*/
      $pay_total = 0;
      $pay_total += $do_hour * $date_user['user_hour_pay'];
      $pay_total += $do_hour_overtime * $date_user['user_hour_pay_over'];
      $update_data['pay_total'] = round($pay_total);
      // dump($update_data);exit;
      D('schedule_date_user')->where('id="'.$date_user['id'].'"')->data($update_data)->save();
    }
    $update_data['turn_salary_time'] = time();
    $update_data['turn_salary_time_name'] = session('userName');
    D('schedule_date')->where('id="'.$id.'"')->data($update_data)->save();
    Common::error_log('日程薪資拋轉, ID：'.$id.', 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
    return $update_data['turn_salary_time'];
  }

  /*檢查是否有權限修改工作日的細項資料(人力&報告)-透過傳入的驗收項目id*/
  public static function check_schedule_detail_right_edit_by_report_id($date_report_id=''){
    $schedule_date_report = D('schedule_date_report')->where('id="'.$date_report_id.'"')->find();
    if(!$schedule_date_report){ throw new \Exception('無此對象'); }
    if(!self::check_schedule_detail_right_edit($schedule_date_report['schedule_date_id'], 'examine_time')){
      throw new \Exception('您無權限，或已無法修改');
    }
  }
  /*檢查是否有權限修改工作日的細項資料(人力&報告)*/
  public static function check_schedule_detail_right_edit($schedule_date_primary, $schedule_status='all'){
    $schedule_date = ScheduleHelper::get_schedules(['schedule_date_primary'=>$schedule_date_primary], true);
    if(count($schedule_date)!=1){ return false; }
    $schedule_date = $schedule_date[0];
    // dump($schedule_date);exit;
    
    /*找出需檢查到哪階段*/
    $schedule_status_idx = array_search($schedule_status, self::$schedule_status_to_check);
    if($schedule_status_idx===false){ /*階段不存在*/
      return false;
    }
    /*根據日程檢查用階段逐個檢查權限*/
    foreach (self::$schedule_status_to_check as $check_idex=>$schedule_status) {
      if($schedule_status=='turn_salary_time'){ /*依拋薪狀態檢查*/
        if($schedule_date['turn_salary_time']){ /*已轉薪資*/
          return false; 
        }
      }
      else if($schedule_status=='examine_time'){ /*依驗收狀態檢查*/
        $access = Common::get_my_access();
        if($access['schedule_edi'] == '1'){ /*有可編輯權限*/
          return true;
        }else if(session('adminId') == $schedule_date['user_id']){ /*工程組審核者(事件步驟執行者)*/
          return true;
        }
        if($schedule_date['examine_time']){ return false; } /*已驗收*/
      }
      else{
        if(session('adminId') != $schedule_date['user_in_charge']){ /*非當日管理者*/
          return false;
        }
      }

      if($check_idex>=$schedule_status_idx){ /*檢查階段 已達或超過 需檢查的階段*/
        break;
      }
    }
    return true;
  }
  /*檢查是否有權限驗收工作日&其資料(拋薪、批示總驗收、驗收項目批示)*/
  public static function check_schedule_detail_right_examine($schedule_date_primary){
    $schedule_date = ScheduleHelper::get_schedules(['schedule_date_primary'=>$schedule_date_primary], true);
    if(count($schedule_date)!=1){ return false; }
    $schedule_date = $schedule_date[0];

    /* 檢查對應的日程狀態 */
    if($schedule_date['turn_salary_time']){ return false; } /*已轉薪資*/
    $access = Common::get_my_access();
    if($access['schedule_edi'] == '1'){
      return true;
    }else if(session('adminId') == $schedule_date['user_id']){ /*工程組審核者(事件步驟執行者)*/
      return true;
    }
    return false;
  }
  /*檢查是否有權限查看工作日的細項資料*/
  public static function check_schedule_detail_right_view($schedule_date_primary){
    $schedule_date = ScheduleHelper::get_schedules(['schedule_date_primary'=>$schedule_date_primary], true);
    if(count($schedule_date)!=1){ return false; }
    $schedule_date = $schedule_date[0];

    /* 檢查對應的日程狀態 */
    $access = Common::get_my_access();
    if($access['schedule_red'] == '1'){
      return true;
    }else if(session('adminId') == $schedule_date['user_id']){ /*工程組審核者(事件步驟執行者)*/
      return true;
    }else if(session('adminId') == $schedule_date['user_in_charge']){ /*當日管理者*/
      return true;
    }
    return false;
  }
  

  public static function create_pay_page($data){
    $acc = Common::get_my_access(); /*取得我的權限*/
    if(!isset($acc['schedulepay_edi'])){ throw new \Exception('您沒有權限操作'); }
    if($acc['schedulepay_edi']==0){ throw new \Exception('您沒有權限操作'); }

    $ids = $data['ids'] ?? [];
    if(count($ids)==0){ throw new \Exception('請選擇對象'); }
    // dump($ids);exit;
    $params = [
      'schedule_date_user_primary_in' => $ids,
      'schedule_date_user_pay_id' => 0, /*未生成付款單的*/
    ];
    $schedule_pays = ScheduleHelper::get_schedules($params, true, true);
    if(count($schedule_pays)==0){ throw new \Exception('無須生成付款單項目'); }
    // dump($schedule_pays);exit;

    $pay_pages = [];
    $time = time();
    foreach ($schedule_pays as $key => $value) {
      $user_id = $value['schedule_date_user_user_id'];
      if(!isset($pay_pages[$user_id])){
        $pay_pages[$user_id] = [
          'user_id'=>$user_id, 
          'pay_total' => 0,
          'insurance_personal_pay' => 0,
          'change_num'=>0,
          'pay_sum'=>0, 
          'create_time'=>$time,
          'schedule_date_user_primary_in' => [],
        ];
      }
      $pay_pages[$user_id]['pay_total'] += $value['pay_total'];

      /*TODO*//*自動計算保險個人負擔額，要小心同一天重複派工、跨月項目合倂(不同約定薪資)*/
      $insurance_personal_pay_this_day = 0;
      $pay_pages[$user_id]['insurance_personal_pay'] += $insurance_personal_pay_this_day;
      $pay_pages[$user_id]['change_num'] += $value['change_num'];
      $pay_pages[$user_id]['pay_sum'] += $value['pay_total'] - $value['insurance_personal_pay'] + $value['change_num'];
      array_push($pay_pages[$user_id]['schedule_date_user_primary_in'], $value['schedule_date_user_primary']);
    }
    // dump($pay_pages);exit;
    foreach ($pay_pages as $pay_pages) {
      $schedule_date_user_primary_in = $pay_pages['schedule_date_user_primary_in'];
      unset($pay_pages['schedule_date_user_primary_in']);
      $pay_id = D('schedule_date_user_pay')->data($pay_pages)->add();
      D('schedule_date_user')->where('id in ('.implode(',', $schedule_date_user_primary_in).')')
                             ->data(['schedule_date_user_pay_id'=>$pay_id])->save();
    }
    Common::error_log('建立日程付款單, 資料：'.json_encode($pay_pages, JSON_UNESCAPED_UNICODE));
  }
  public static function get_schedule_pay_page($params=[], $need_detail=false){
    $where_query = 'true ';
    if(isset($params['date_s'])){ /*建立時間-開始*/
      if($params['date_s']!==''){
        $where_query .= ' AND sdup.create_time>="'.strtotime($params['date_s']).'"';
      }
    }
    if(isset($params['date_e'])){ /*建立時間-結束*/
      if($params['date_e']!==''){
        $where_query .= ' AND sdup.create_time<"'.strtotime($params['date_e'].' +1Day').'"';
      }
    }
    if(isset($params['mens_keyword'])){ /*人員關鍵字*/
      if($params['mens_keyword']!==''){
        $where_query .= ' AND (
          u.name LIKE "%'.$params['mens_keyword'].'%" OR 
          u.ename LIKE "%'.$params['mens_keyword'].'%" OR 
          u.phone LIKE "%'.$params['mens_keyword'].'%" OR 
          u.mphone LIKE "%'.$params['mens_keyword'].'%" OR 
          u.email LIKE "%'.$params['mens_keyword'].'%" OR 
          u.email2 LIKE "%'.$params['mens_keyword'].'%"
        )';
      }
    }
    if(isset($params['comfirm_status'])){ /*審核狀態*/
      if($params['comfirm_status']=='1'){
        $where_query .= ' AND sdup.comfirm_time!=""';
      }else if($params['comfirm_status']=='0'){
        $where_query .= ' AND sdup.comfirm_time=""';
      }
    }
    if(isset($params['comfirm_time_s'])){ /*核可時間-開始*/
      if($params['comfirm_time_s']!==''){
        $where_query .= ' AND sdup.comfirm_time>="'.strtotime($params['comfirm_time_s']).'" AND sdup.comfirm_time!=""';
      }
    }
    if(isset($params['comfirm_time_e'])){ /*核可時間-結束*/
      if($params['comfirm_time_e']!==''){
        $where_query .= ' AND sdup.comfirm_time<"'.strtotime($params['comfirm_time_e'].' +1Day').'" AND sdup.comfirm_time!=""';
      }
    }
    if(isset($params['user_id'])){ /*給付對象id*/
      if($params['user_id']!==''){
        $where_query .= ' AND sdup.user_id="'.$params['user_id'].'"';
      }
    }
    if(isset($params['crm_id'])){ /*客戶id*/
      if($params['crm_id']!==''){
        $where_query .= ' AND e.cum_id="'.$params['crm_id'].'"';
      }
    }
    // dump($where_query);exit;

    $field_query = 'u.name, u.ename, u.phone, u.mphone, u.email, u.email2, u.bank, u.bank_code, u.bank_account,
                    sdu.do_hour, sdu.do_hour_overtime,
                    sdup.*, 
                    FROM_UNIXTIME(sdup.create_time) as create_time_format, 
                    FROM_UNIXTIME(sdup.comfirm_time) as comfirm_time_format
                    ';
    $pay_pages = D('schedule_date_user_pay sdup')->field($field_query)
                  ->join('eip_user as u ON u.id=sdup.user_id','left')
                  ->join('schedule_date_user as sdu ON sdu.schedule_date_user_pay_id=sdup.id','left')
                  ->where($where_query)
                  ->order('sdup.id desc')
                  ->group('sdup.id');
    if(isset($params['count_of_page'])){ /*處理分頁*/
      if($params['count_of_page']){
        $current_page = $params['current_page'] ?? 1;
        $index_start = ($current_page -1) < 0 ? 0 : ($current_page -1) * $params['count_of_page'];
        $pay_pages->limit($index_start, $params['count_of_page']);
      }
    }
    $pay_pages = $pay_pages->select();

    if($need_detail){
      foreach ($pay_pages as $key => $value) {
        $pay_pages[$key]['schedule_dates'] = ScheduleHelper::get_schedules([
          'schedule_date_user_pay_id' => $value['id'],
        ], true, true);
      }
    }
    return $pay_pages;
  }
  public static function update_pay_page($id, $data){
    $acc = Common::get_my_access(); /*取得我的權限*/
    if(!isset($acc['schedulepay_edi'])){ throw new \Exception('您沒有權限操作'); }
    if($acc['schedulepay_edi']==0){ throw new \Exception('您沒有權限操作'); }

    $user_pay = D('schedule_date_user_pay')->where('id="'.$id.'"')->find();
    if(!$user_pay){ throw new \Exception('無此項目'); }
    if($user_pay['comfirm_time']){ throw new \Exception('已核可，不可修改'); }

    $update_data = [];
    foreach ($data as $key => $value) {
      if($key=='insurance_personal_pay'){
        $update_data[$key] = $value;
        $update_data['pay_sum'] = $user_pay['pay_total'] - $value + $user_pay['change_num'];
      }
    }
    // dump($update_data);exit;
    D('schedule_date_user_pay')->where('id="'.$id.'"')->data($update_data)->save();
    Common::error_log('修改日程付款單, ID：'.$id.', 資料：'.json_encode($update_data, JSON_UNESCAPED_UNICODE));
  }
  public static function confirm_pay_page($data){
    $acc = Common::get_my_access(); /*取得我的權限*/
    if(!isset($acc['schedulepay_edi'])){ throw new \Exception('您沒有權限操作'); }
    if($acc['schedulepay_edi']==0){ throw new \Exception('您沒有權限操作'); }

    $ids = $data['ids'] ?? [];
    if(count($ids)==0){ throw new \Exception('請選擇對象'); }
    // dump($ids);exit;

    $time = time();
    if(isset($data['date'])){ $time = strtotime($data['date']); }
    // dump($time);exit;
    foreach ($ids as $id) {
      $user_pay = D('schedule_date_user_pay')->where('id="'.$id.'"')->find();
      if($user_pay['comfirm_time']){ continue; }
      D('schedule_date_user_pay')->where('id="'.$id.'"')->data(['comfirm_time'=>$time])->save();
    }
    Common::error_log('核可日程付款單, 資料：'.json_encode($pay_pages, JSON_UNESCAPED_UNICODE));
  }
}
?>