<?php
namespace Photonic;
use Think\Controller;

use Think\Db;
use Photonic\Common;
use Photonic\MensHelper;

class AttendanceHelper extends Controller
{
  public static $default_work_time = ['09:00', '17:00'];

  function _initialize(){
  }
  public static function instance()
  {
    return new AttendanceHelper();
  }
  public static function get_default_work_time(){
    $result = self::$control_working_time;
    $result = explode(',', $result);
    $default_work_time = ['id'=>0, 'time_come'=>'', 'time_leave'=>'',];
    if(count($result)!=2){
      $default_work_time['time_come'] = self::$default_work_time[0];
      $default_work_time['time_leave'] = self::$default_work_time[1];
    }else{
      $default_work_time['time_come'] = trim($result[0]);
      $default_work_time['time_leave'] = trim($result[1]);
    }
    return [$default_work_time];
  }
  public static function get_work_time(){
    $work_time = D('work_time')->order('time_come asc, id asc')->select();
    return $work_time;
  }
  public static function get_work_time_options($show_default=false){
    $work_times = self::get_work_time();
    if($show_default){
      $default_work_time = self::get_default_work_time();
    }
    $work_times = array_merge($default_work_time, $work_times);
    return $work_times;
  }

  /**
   * 根據傳入的年月回傳需打卡的日期
   * @param string $ym 年月文字(YYYYMM)
   * @return array 該月全部日期
  */
  public static function getMonthDates($ym){
    [$db_name, $ym_format] = self::get_db_name($ym);
    $time_come = strtotime($ym_format.'-01');
    $time_leave = strtotime(date('Y-m-t', $time_come).'+1Day');
    $dates = [];
    while ($time_come < $time_leave) {
      $date = date('Y-m-d', $time_come);
      $dates[] = $date;
      $time_come = strtotime($date.'+1Day');
    }
    return ['dates' => $dates,];
  }
  public static function getUserList($ym, $men_filter=[], $date_params=[]){
    $mens = self::getMens($ym, $men_filter);
    $list = self::getDataList($ym, false, $date_params);
    foreach ($mens as $key => $men) {
      $mens[$key]['need_works'] = [];
      $mens[$key]['works_date'] = [];
      foreach ($list as $item) {
        if($item['user_id']==$men['id']){
          $mens[$key]['need_works'][] = $item['date'];
          $mens[$key]['works_date'][$item['date']] = $item;
        }
      }
    }
    return ['mens' => $mens,];
  }

  /**
   * 根據傳入的年月回傳需打卡的日期
   * @param string $ym 年月文字(YYYYMM)
   * @param bool $group_date 是否需依日期合併資料
   * @param array $params 篩選參數
   * - user_id: 員工id
   * @return array 需打卡日期
  */
  public static function getDataList($ym='', $group_date=true, $params=[]){
    [$db_name, $ym_format] = self::get_db_name($ym);
    self::check_db_exist($ym);
    $data_list = D('attendance_date')->set_table_name($db_name)->getDataList($ym_format, $group_date, $params);
    return $data_list;
  }
  /**
   * 根據傳入的年月測試資料表是否存在，若不存在則建立新表
   * @param string $ym 年月文字(YYYYMM)
  */
  public static function check_db_exist($ym=''){
    [$db_name, $ym_format] = self::get_db_name($ym);
    try { /*測試資料表是否存在*/
      D('attendance_date')->set_table_name($db_name)->find();
    } catch (\Throwable $th) { /*測試資料表不存在，建立資料表*/
      $query = "
        CREATE TABLE `".$db_name."`(
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `date` DATE NOT NULL COMMENT '出勤日期',
          `user_id` INT(10) UNSIGNED NOT NULL COMMENT '員工ID ref:eip_user.id',
          `time_come` TIME NULL DEFAULT NULL COMMENT '上班時間',
          `time_leave` TIME NULL DEFAULT NULL COMMENT '下班時間',
          `need_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否需計算出席率',
          PRIMARY KEY(`id`) USING BTREE,
          UNIQUE INDEX `date_user_id`(`date`, `user_id`) USING BTREE
        ) COMMENT = '出勤紀錄(".$ym_format.")' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
      ";
      try { /*測試資料表是否存在*/
        Db::query($query);
      } catch (\Throwable $th) {
        // dump($th);exit;
      }
    }
  }
  /**
   * 根據傳入的年月回傳對應格式之需打卡資料表名稱、篩選年月格式
   * @param string $ym 年月文字(YYYYMM)
   * @return array [string 資料表名稱, string 篩選年月格式(YYYY-MM)] 
  */
  public static function get_db_name($ym=''){
    $ym = filter_var($ym, FILTER_SANITIZE_STRING);
    $ym_format = '';
    if (strlen($ym) == 6){
      if(checkdate(substr($ym, 4, 2), 1, substr($ym, 0, 4))){
        $ym_format = substr($ym, 0, 4) . "-" . substr($ym, 4, 2);
      }
    }
    if(!$ym_format){
      $ym_format = date('Y') . "-" . date('m');
    }
    $db_name = 'attendance_date_'.$ym_format;

    return [$db_name, $ym_format];
  }

  /**
   * 根據傳入開始日期、結束日期、勾選星期，回傳符合選擇的日期陣列
   * @param string $start_date 年月日文字(YYYY-MM-DD)
   * @param string $end_date 年月日文字(YYYY-MM-DD)
   * @param array $checked_days 元素為0~6(日~六)的陣列，空時視為[0,1,2,3,4,5,6]
   * @return array [ string 日期(YYYY-MM-DD), ] 
  */
  public static function get_days($start_date, $end_date, $checked_days){
    // 產生日期區間
    date_default_timezone_set('Asia/Taipei');
    $period = new \DatePeriod(
      new \DateTime($start_date),
      new \DateInterval('P1D'),
      new \DateTime(date('Y-m-d', strtotime("+1 days", strtotime($end_date))))
    );

    // 判斷是否勾選星期幾
    $period_array = array();
    foreach ($period as $key => $value) {
      $weekday = date('w', strtotime($value->format('Y-m-d')));
      // $checked_days 空值視為全選
      if (empty($checked_days) || in_array($weekday, $checked_days))
        $period_array[] = $value->format('Y-m-d');
    }
    return $period_array;
  }
  /**
   * 根據傳入日期陣列，重新整理成以年月為key，日期陣列為value的陣列
   * @param string $period_array [ string 日期(YYYY-MM-DD), ]
   * @return array  [ YYYYMM=>[YYYY-MM-DD,], ]
  */
  public static function arrange_days($period_array){
    /*依年月統整選擇區間的日期*/
    $period_array_ym = array();
    foreach ($period_array as $key => $value) {
        $ym = substr($value,0,4).substr($value,5,2);
        if(!isset($period_array_ym[$ym])){ $period_array_ym[$ym] = array(); }
        array_push($period_array_ym[$ym], $value);
    }
    return $period_array_ym;
  }
  /**
   * 根據傳入日的年月、需處理的日期、需處理的人員，回傳需處理的打卡紀錄
   * @param string $ym 年月文字(YYYYMM)
   * @param string $dates 日期陣列 [YYYY-MM-DD,]
   * @return array 
   * - array insertable_data 需批次新增的空打卡紀錄
   * - array need_update_to_need_show 需修改成需計算出缺席日期
  */
  public static function get_todo_data($ym, $dates, $men_filter=[]){
    $mens = self::getMens($ym, $men_filter);
    // dump($mens);exit;
    if(count($mens)==0){
      return [];
    }
    [$db_name, $ym_format] = self::get_db_name($ym);
    $data_list = self::getDataList($ym, false);
    // dump($data_list);exit;
    $dates_in_db = array_column($data_list, 'date');
    $userIds_in_db = array_column($data_list, 'user_id');

    $insertable_data = array(); /*需要新增的日期*/
    $need_update_to_need_show = array(); /*需要編輯的日期*/
    foreach ($dates as $date) {
      foreach ($mens as $men) {
        $need_add = false;
        if(count($data_list)==0){ /*過去無紀錄*/
          $need_add = true;
        }else{ // 檢查重複
          $date_repeate = array_intersect($dates_in_db, [$date]);         // 找出哪幾列日期重複
          $user_repeate = array_intersect($userIds_in_db, [$men['id']]);  // 找出哪幾列人員重複
          $date_repeate_row = array_keys($date_repeate);                  // 將日期重複的列轉換成比較對象
          $user_repeate_row = array_keys($user_repeate);                  // 將人員重複的列轉換成比較對象
          $repeat_same_row = array_intersect($date_repeate_row, $user_repeate_row); // 找出日期、人員都有重複的列
          if (count($repeat_same_row)==0){ // 無同時重複
            $need_add = true;
          }
        }
        if($need_add){
          $insertable_data[] = array('date'=>$date, 'user_id'=>$men['id']);
        }else{
          $need_update_to_need_show[] = $date;
        }
      }
    }
    return ['insertable_data'=>$insertable_data, 'need_update_to_need_show'=>$need_update_to_need_show];
  }

  /**
   * 根據傳入日的年月、插入資料，過濾出屬於此年月的資料後批次新增至資料庫(重複會報錯)
   * @param string $ym 年月文字(YYYYMM)
   * @param array $insertable_data 需打卡日資料
   * @return int $result 新增的數量(0表示沒新增或錯誤)
  */
  public static function addRows($ym='', $insertable_data=[]){
    [$db_name, $ym_format] = self::get_db_name($ym);
    try {
      $insertable_data = array_filter($insertable_data, function($item)use($ym){
        /*篩選限本年月的日期*/
        return date('Ym', strtotime($item['date'])) == $ym;
      });
      $result = D('attendance_date')->set_table_name($db_name)->addAll($insertable_data);
      if ($result) {
        Common::error_log('新增' . $db_name . ' 資料：' . json_encode($inser_data, JSON_UNESCAPED_UNICODE));
      }
    } catch (\Throwable $th) { /*因為(date,user_id)的獨一限制 或 其他*/
      $result = 0;
    }
    return $result;
  }
  /**
   * 根據傳入日的年月、需修改資料，過濾出屬於此年月的資料後逐一至資料庫修改
   * @param string $ym 年月文字(YYYYMM)
   * @param array $need_update_to_need_show 需打修改的資料([YYYY-mm-dd])
   * @return int $result 新增的數量(0表示沒新增或錯誤)
  */
  public static function updateRows($ym='', $need_update_to_need_show=[]){
    [$db_name, $ym_format] = self::get_db_name($ym);
    try {
      $need_update_to_need_show = array_filter($need_update_to_need_show, function($item)use($ym){
        /*篩選限本年月的日期*/
        return date('Ym', strtotime($item)) == $ym;
      });
      $result = D('attendance_date')->set_table_name($db_name)
                                    ->where('date in ("'.implode('","', $need_update_to_need_show).'")')
                                    ->save(['need_show'=>1]);
      if ($result) {
        Common::error_log('新增' . $db_name . ' 資料：' . json_encode($inser_data, JSON_UNESCAPED_UNICODE));
      }
    } catch (\Throwable $th) { /*因為(date,user_id)的獨一限制 或 其他*/
      $result = 0;
    }
    return $result;
  }
  /**
   * 根據傳入日的年月、篩選參數，刪除目標的打卡日
   * @param string $ym 年月文字(YYYYMM)
   * @param array $params 篩選參數
   * - date: 日期
   * - user_id: 員工id
   * @return int $result 刪除的數量(0表示沒刪除或錯誤)
  */
  public static function deleteRows($ym='', $params=[]){
    $where_query = [];
    foreach ($params as $key => $value) {
      if(in_array($key, ['date', 'user_id'])){
        $value = filter_var($value, FILTER_SANITIZE_STRING);
        if($value != ''){ array_push($where_query, $key.'="'.$value.'"'); }
      }
    }

    // 刪除資料
    [$db_name, $ym_format] = self::get_db_name($ym);
    try {
      $result = D('attendance_date')->set_table_name($db_name)->where($where_query)->delete();
      if ($result) {
        Common::error_log('刪除 ' . $db_name . ' 日期：' . $date);
      }
    } catch (\Exception $e) {
      $result = 0;
    }
    return $result;
  }
  /**
   * 根據傳入日的年月、篩選參數，刪除目標的打卡日
   * @param string $ym 年月文字(YYYYMM)
   * @param array $id 打卡日id
   * @param array $update_data 編輯資料，僅允許以下欄位:
   * - time_come: 上班打卡時間(HH:mm:ii)(-1則為清空)
   * - time_leave: 下班打卡時間(HH:mm:ii)(-1則為清空)
   * @return int $result 編輯的數量(0表示沒刪除或錯誤)
  */
  public static function saveData($ym='', $id, $update_data=[]){
    // 過濾出可編輯的資料
    $data = [];
    if(isset($update_data['time_come'])){ 
      $data['time_come'] = $update_data['time_come']!=-1 ? $update_data['time_come'] : null;
    }
    if(isset($update_data['time_leave'])){ 
      $data['time_leave'] = $update_data['time_leave']!=-1 ? $update_data['time_leave'] : null;
    }
    // dump($data);exit;
    // 編輯資料
    [$db_name, $ym_format] = self::get_db_name($ym);
    try {
      $result = D('attendance_date')->set_table_name($db_name)->where('id=%d', $id)->data($data)->save();
      if ($result) {
        Common::error_log('編輯 '.$db_name.' 資料：' . json_encode($update_data, JSON_UNESCAPED_UNICODE));
      }
    } catch (\Exception $e) {
      $result = 0;
    }
    return $result;
  }
  /**
   * 根據傳入日的年月，回傳員工陣列
   * @param string $ym 年月文字(YYYYMM)
   * @param array  $men_filter 人員篩選條件
   * (限在「在職/留職停薪 且 需打卡 且 到職日期大於指定年月 且 指定年月之約定薪資為月薪」下額外添加篩選)
   * @return array 
  */
  public static function getMens($ym='', $men_filter=[]){
    [$db_name, $ym_format] = self::get_db_name($ym);
    $men_filter = array_merge($men_filter, [
      'working_and_stop' => 1, /*在職或留職停薪*/
      'use_attendance' => 1,
      'salary_record_date' => substr($ym,0,4).'-'.substr($ym,4,2).'-01',
      'pay_type' => 1,
    ]);
    $mens = MensHelper::get_mens_with_salary_record($men_filter);
    return $mens;
  }

  /** 
   * 
   * 計算兩點間的距離
   * 
   * @param $lng1 第一組地址的經度
   * @param $lat1 第一組地址的緯度
   * @param $lng2 第二組地址的經度
   * @param $lat2 第二組地址的緯度
   * @return int 單位公尺
   */
  public function getDistance($lng1, $lat1, $lng2, $lat2){
    //將角度轉為弧度 
    $radLat1 = deg2rad($lat1);
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $tmp1 = $radLat1 - $radLat2;
    $tmp2 = $radLng1 - $radLng2;
    //計算公式 
    $d = 2 * asin(sqrt(pow(sin($tmp1 / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($tmp2 / 2), 2))) * 6378.137 * 1000;
    //單位公尺
    return intval($d);
  }
}
