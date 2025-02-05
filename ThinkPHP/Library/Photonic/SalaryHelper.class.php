<?php

namespace Photonic;

use Exception;
use Think\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Photonic\ScheduleDetailHelper;
use Photonic\ScheduleHelper;
use Photonic\MensHelper;

class SalaryHelper extends Controller
{
  public static $example_month_count_name = '在職率';
  public static $example_month_salary_note = '(請於在職率下輸入「計薪日」佔「本月日數」之比率，最大為1)';
  public static $example_bonus_salary_note = '(請於加給名目下方輸入「金額」)';

  function _initialize()
  {
  }
  public static function instance()
  {
    return new SalaryHelper();
  }

  /**
   * 匯入月薪檔案
   * @param string $year
   * @param string $month
   * @throws Exception
   * @return void
   */
  public static function importMonthPay(string $year, string $month)
  {
    $PHPExcel = new Spreadsheet();
    $PHPReader = new Xlsx();
    if (!$PHPReader->canRead($_FILES['month_pay']['tmp_name'])) {
      throw new Exception('檔案錯誤, 請確認檔案為 Excel');
    }

    $PHPExcel = $PHPReader->load($_FILES['month_pay']['tmp_name']);
    $sheetData = $PHPExcel->getSheet(0)->toArray(null, true, true, true);
    $header = $sheetData[1]; /*表頭*/
    if (!in_array(self::$example_month_salary_note, $header)) {
      throw new Exception('此檔案非月薪格式，請重新選擇');
    }

    /*找出本月該處理的月薪員工*/
    $user = MensHelper::get_mens_with_salary_record([
      'working_and_stop' => 1, /*在職或留職停薪*/
      'salary_record_date' => $year.'-'.$month.'-01',
      'pay_type' => 1,
    ]);
    $user_should_import = [];
    foreach ($user as $key => $value) {
      array_push($user_should_import, $value['id']);
    }

    $sheetData = array_slice($sheetData, 1); /*資料*/
    foreach ($sheetData as $row) {
      $user_id = 0;
      $month_count = 0;
      foreach ($header as $column_key => $column) {
        if ($column_key == 'A') {
          $user_id = $row[$column_key];
        } else {
          $companys_names_index = array_search($column, [SalaryHelper::$example_month_count_name]);
          if ($companys_names_index !== false) {
            $month_count_one = trim($row[$column_key]);
            if ($month_count_one != '') {
              $month_count += $month_count_one;
            }
          }
        }
      }
      if ($user_id != 0) {
        if(in_array($user_id, $user_should_import)){
          self::set_salary_data($user_id, $year, $month, [
            'month_count' => $month_count,
          ]);
        }
      }
    }
  }

  /**
   * 匯入時薪，由日程人員(schedule_date_user)算出
   * @param string $year
   * @param string $month
   * @throws Exception
   * @return void
   */
  public static function importHourPay(string $year, string $month)
  {
    $hour_salarys = []; /*需要更新薪資的資料*/
    
    /*取得時薪計薪人員*/
    $men_filter = [
      'working_and_stop' => 1, /*在職或留職停薪*/
      'salary_record_date' => $year.'-'.$month.'-01',
      'pay_type' => '0',
    ];
    $mens = MensHelper::get_mens_with_salary_record($men_filter);
    foreach ($mens as $men) {
      $eip_user_id = $men['id'];
      if(!isset($hour_salarys[$eip_user_id])){
        $hour_salarys[$eip_user_id] = [
          'total_pay_hour' => 0,
          'hour_count' => 0,
          'hour_count_detail' => [],
        ];
      }
    }

    /*取得符合時間區間的付款單，順便統計保險金額*/
    $date_stamp = strtotime($year.'-'.$month.'-01');
    $params = [
      'comfirm_time_s' => $year.'-'.$month.'-01',
      'comfirm_time_e' => date('Y-m-t', $date_stamp),
    ];
    $pay_pages = ScheduleDetailHelper::get_schedule_pay_page($params);
    // dump($pay_pages);exit;
    $pay_page_ids = [ -1 ];
    $insurance_personal_pay_by_user = []; 
    foreach ($pay_pages as $pay_page) {
      $pay_page_ids[] = $pay_page['id'];
      if(!isset($insurance_personal_pay_by_user[$pay_page['user_id']])){ $insurance_personal_pay_by_user[$pay_page['user_id']] = 0; }
      $insurance_personal_pay_by_user[$pay_page['user_id']] += $pay_page['insurance_personal_pay'];
    }
    // dump($pay_page_ids);exit;

    /*取得對應付款單們的日程人員(必須對此人員資料拋薪且生成付款單才能符合搜尋條件)*/
    $params = [
      'schedule_date_user_pay_ids' => $pay_page_ids,
    ];
    $schedule_date_users = ScheduleHelper::get_schedules($params, true, true);
    // dump($schedule_date_users);exit;
    foreach ($schedule_date_users as $row) {
      $eip_user_id = $row['schedule_date_user_user_id'];
      if(!isset($hour_salarys[$eip_user_id])){
        $hour_salarys[$eip_user_id] = [
          'total_pay_hour' => 0,
          'hour_count' => 0,
          'hour_count_detail' => [],
        ];
      }
      $total_pay_hour = $row['pay_total'] + (int)$row['change_num']; /*時薪總計 + 調薪(忽略保險，在calculateSalary會計算)*/
      $hour_salarys[$eip_user_id]['total_pay_hour'] += $total_pay_hour;
      $do_hours = (float)$row['do_hour'] + (float)$row['do_hour_overtime'];
      $hour_salarys[$eip_user_id]['hour_count'] += $do_hours;

      array_push($hour_salarys[$eip_user_id]['hour_count_detail'], [
        'total_pay_hour' => $total_pay_hour,
        'schedule_date_user_primary' => $row['schedule_date_user_primary'],
        'user_skill' => $row['user_skill'],
        'date' => $row['date'],
        'name' => $row['name'],
        'location' => $row['location'],
        'user_skill_name' => $row['user_skill_name'],
        'user_hour_pay' => $row['user_hour_pay'],
        'do_hour' => $row['do_hour'],
        'user_hour_pay_over' => $row['user_hour_pay_over'],
        'do_hour_overtime' => $row['do_hour_overtime'],
        'change_num' => $row['change_num'],
        'note' => $row['note'],
      ]);
    }

    // dump($insurance_personal_pay_by_user);exit;
    // dump($hour_salarys);exit;
    foreach ($hour_salarys as $user_id => $value) {
      self::set_salary_data($user_id, $year, $month, [
        'total_pay_hour' => $value['total_pay_hour'],                                                 /*總蒔薪*/
        'hour_count' => $value['hour_count'],                                                         /*總時數(加班加薪轉化成時數)*/
        'pay_hour' => $value['hour_count']==0 ? 0 : $value['total_pay_hour'] / $value['hour_count'],  /*平均時薪*/
        'hour_count_detail' => json_encode($value['hour_count_detail'], JSON_UNESCAPED_UNICODE),
        'insurance_personal_pay' => $insurance_personal_pay_by_user[$user_id] ?? 0,                   /*保險自負額*/
      ]);
    }
  }

  /**
   * 匯入加給檔案
   * @param string $year
   * @param string $month
   * @throws Exception
   * @return void
   */
  public static function importBonus(string $year, string $month)
  {
    $bonus_names_result = self::get_bonus_names();
    $bonus_names = $bonus_names_result['bonus_names'];
    $bonus_array = $bonus_names_result['bonus_array'];

    $PHPExcel = new Spreadsheet();
    $PHPReader = new Xlsx();
    if (!$PHPReader->canRead($_FILES['bonus']['tmp_name'])) {
      throw new Exception('檔案錯誤, 請確認檔案為 Excel');
    }
    $PHPExcel = $PHPReader->load($_FILES['bonus']['tmp_name']);
    $sheetData = $PHPExcel->getSheet(0)->toArray(null, true, true, true);
    $header = $sheetData[1]; /*表頭*/
    if (!in_array(self::$example_bonus_salary_note, $header)) {
      throw new Exception('此檔案非加給格式，請重新選擇');
    }

    $user = MensHelper::get_mens_with_salary_record([
      'working_and_stop' => 1, /*在職或留職停薪*/
      'salary_record_date' => $decode_salary_ym['year'].'-'.$decode_salary_ym['month'].'-01',
    ]);
    $user_should_import = [];
    foreach ($user as $key => $value) {
      array_push($user_should_import, $value['id']);
    }

    $sheetData = array_slice($sheetData, 1); /*資料*/
    foreach ($sheetData as $row) {
      $user_id = 0;
      $total_bonus = 0;
      $total_bonus_award = 0;
      $bonus_detail = [];
      $note = "";
      foreach ($header as $column_key => $column) {
        if ($column_key == 'A') {
          $user_id = $row[$column_key];
        } else {
          $companys_names_index = array_search($column, $bonus_names);
          if ($companys_names_index !== false) {
            $total_bonus_one = trim($row[$column_key]);
            if ($column == '備註') {
              $note = $total_bonus_one;
            } else {
              if ($total_bonus_one != '') {
                if ($bonus_array[$companys_names_index]['type'] == 1) { /*本薪*/
                  $total_bonus += $total_bonus_one;
                } else { /*獎金*/
                  $total_bonus_award += $total_bonus_one;
                }
                $bonus_detail[$bonus_array[$companys_names_index]['id']] = [
                  'id' => $bonus_array[$companys_names_index]['id'],
                  'type' => $bonus_array[$companys_names_index]['type'],
                  'name' => $bonus_array[$companys_names_index]['name'],
                  'num' => $total_bonus_one,
                ];
              }
            }
          }
        }
      }
      if ($user_id != 0 && $bonus_detail) {
        if(in_array($user_id, $user_should_import)){
          self::set_salary_data($user_id, $year, $month, [
            'total_bonus' => $total_bonus,
            'total_bonus_award' => $total_bonus_award,
            'bonus_detail' => json_encode($bonus_detail, JSON_UNESCAPED_UNICODE),
            'note' => $note,
          ]);
        }
      }
    }
  }

  /**
   * 解析年月字串
   * @param string $salary_ym YYYYMM
   * @return array An associative array with the following keys:
   *   - year: string
   *   - month: string
   *   - month_day: int (固定30天)
   */
  public static function decode_salary_ym(string $salary_ym){
    $year      = substr($salary_ym, 0, 4);
    $month     = substr($salary_ym, 4, 2);
    $month_day = date("t", strtotime($year . '-' . $month . '-01'));
    return [
      'year' => $year,
      'month' => $month,
      'month_day' => 30,
    ];
  }

  /**
   * 計算時薪、月薪、勞健保
   * @param string $salary_ym YYYYMM
   * @return void
   */
  public static function calculateSalary(string $salary_ym)
  {
    $decode_salary = self::decode_salary_ym($salary_ym);
    $year       = $decode_salary['year'];
    $month      = $decode_salary['month'];
    $month_day  = $decode_salary['month_day'];

    $user = MensHelper::get_mens_working([], [
      'working_definition' => 'is_job in (1,2)', /*在職或留職停薪*/
    ]);
    foreach ($user as $key => $value) {
      $salary_record = MensHelper::get_user_salary_record($value['id'], $salary_ym);
      if (isset($salary_record['pay_month']) || count($salary_record['salary_records_skill']) > 0) {
        // 讀取目前此員工的匯薪資料
        $one_salary_data = self::get_one_salary_data($value['id'], $year, $month);

        $salary_data['pay_month'] = $salary_record['pay_month'];
        if($salary_record['pay_type']==0){ /*時薪計薪*/
          // 依時薪付款單統計
          // $insurance_personal_pay = $one_salary_data['insurance_personal_pay'] ?? 0;
          // $salary_data['insurance_personal_pay'] = $insurance_personal_pay;
          // $insurance = json_decode($salary_record['insurance'], true) ?? [];
          // if(count($insurance)>0){
          //   $sum = 0;
          //   foreach ($insurance as $item) {
          //     $sum += $item['insurance_personal_pay'];
          //   }
          //   $sum_used = 0;
          //   foreach ($insurance as $key => $item) {
          //     if($key==count($insurance)-1){
          //       $insurance[$key]['insurance_personal_pay'] = $insurance_personal_pay - $sum_used;
          //     }else{
          //       $insurance[$key]['insurance_personal_pay'] = round($insurance_personal_pay * $item['insurance_personal_pay'] / $sum);
          //       $sum_used += $insurance[$key]['insurance_personal_pay'];
          //     }
          //   }
          // }else{
          //   $insurance[] = ['name'=>'無項目保額', 'accountant_item_id'=>0, 'insurance_personal_pay'=>$insurance_personal_pay, 'insurance_company_pay'=>0,];
          // }
          // $salary_data['insurance'] = json_encode($insurance, JSON_UNESCAPED_UNICODE);

          // 依約定薪資統計
          $salary_data['insurance_personal_pay'] = $salary_record['insurance_personal_pay'];
          $salary_data['insurance'] = $salary_record['insurance'] ?? '[]';
        }
        else{ /*月薪計薪*/
          $salary_data['insurance_personal_pay'] = $salary_record['insurance_personal_pay'];
          $salary_data['insurance'] = $salary_record['insurance'] ?? '[]';
        }
        $salary_data['insurance_company_pay'] = $salary_record['insurance_company_pay'];
        $salary_data['month_day'] = $month_day;
        $salary_data['total_rest_deduct'] = 0;

        
        if ($one_salary_data) { /*有匯薪資料*/
          $salary_base = ($salary_data['pay_month'] + $one_salary_data['total_bonus']); /*本薪=月薪+加給本薪*/
          $salary_data['total_pay_month'] = $salary_base * $one_salary_data['month_count'];
          $salary_base_hour = round($salary_base / $month_day / 8.00, 2);
          $salary_data['salary_base_hour'] = $salary_base_hour;
          /*計算請假變動*/
          $rest_record = MensHelper::get_user_rest_record([
            'user_id' => $value['id'],  /*某員工*/
            'salary_ym' => $salary_ym,  /*某月份的請假*/
            'apply_status' => 0,        /*審核通過*/
          ]);
          // dump($rest_record);exit;
          foreach ($rest_record as $rest_k => $rest) {
            $rest_record[$rest_k]['deduct_pay_hour'] = $salary_base_hour;
            if ($rest['deduct_percent']) {
              $deduct_pay = $rest['hours'] * $rest['deduct_percent'] * $salary_base_hour;
              $deduct_pay = round($deduct_pay, 2);
            } else {
              $deduct_pay = 0;
            }
            $rest_record[$rest_k]['deduct_pay'] = $deduct_pay;
            $salary_data['total_rest_deduct'] += $deduct_pay;
            $rest_record[$rest_k]['hours_h'] = (int)$rest['hours'];
            $rest_record[$rest_k]['hours_m'] = round(($rest['hours'] - $rest_record[$rest_k]['hours_h']) * 60);
          }
          $salary_data['rest_detail'] = json_encode($rest_record, JSON_UNESCAPED_UNICODE);
        }else{ /*尚無匯薪資料*/
          $salary_data['total_pay_month'] = 0;
          $salary_data['salary_base_hour'] = 0;
          $salary_data['rest_detail'] = json_encode([], JSON_UNESCAPED_UNICODE);
        }
        $salary_data['total_rest_deduct'] = round($salary_data['total_rest_deduct']);
        $total_salary = 0;
        $total_salary += $salary_data['total_pay_month'];
        $total_salary += $salary_data['total_rest_deduct'];
        $total_salary += $one_salary_data['total_pay_hour'] ?? 0;
        $total_salary += $one_salary_data['total_bonus_award'] ?? 0;
        $salary_data['total_salary'] = $total_salary;

        self::set_salary_data($value['id'], $year, $month, $salary_data);
      } else {
        // $this->error('員工：'.$value['name'].'('.$value['id'].')尚未建立'.$salary_ym.'的薪資資料');
      }
    }
  }

  /**
   * 設定薪資資料(儲存則編輯、不存在則新增)
   * @param string $user_id 員工ID
   * @param string $year YYYY
   * @param string $month MM
   * @param array $update_data 更改資料(鍵值對)
   * @return string $error_msg 錯誤訊息
   */
  static public function set_salary_data($user_id, $year, $month, $update_data)
  {
    Common::error_log('設定薪資 年月:' . $year . $month . ', 人員：' . $user_id . ', 資料:' . json_encode($update_data, JSON_UNESCAPED_UNICODE));
    $record = self::get_one_salary_data($user_id, $year, $month);
    if ($record) {
      if ($record['confirm_time'] != 0) { return '薪資已核可'; }
      D('salary')->where('user_id=' . $user_id . ' AND year=' . $year . ' AND month=' . $month)->save($update_data);
    } else {
      $update_data['user_id'] = $user_id;
      $update_data['year'] = $year;
      $update_data['month'] = $month;
      D('salary')->add($update_data);
      return '';
    }
  }

  static public function get_one_salary_data($user_id, $year, $month)
  {
    $record = D('salary')->where('user_id=' . $user_id . ' AND year=' . $year . ' AND month=' . $month)->find();
    return $record;
  }

  /*取得匯新的加給名目欄位名稱及對照資料*/
  static public function get_bonus_names(){
    $bonus_names = [];
    $bonus_array = [];

    $bonus_type = MensHelper::get_bonus_type();
    foreach ($bonus_type as $key => $value) {
      array_push($bonus_names, $value['name']);
      array_push($bonus_array, [
        'id' => $value['id'],
        'type' => $value['type'],
        'name' => $value['name'],
      ]);
    }
    array_push($bonus_names, '備註');
    array_push($bonus_array, [
      'id' => 'note',
      'type' => 'note',
      'name' => '備註',
    ]);

    return [
      'bonus_names' => $bonus_names,
      'bonus_array' => $bonus_array,
    ];
  }
}
