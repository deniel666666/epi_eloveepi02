<?php
  namespace Trade\Controller;

  use Exception;
  use Think\Controller;

  use PhpOffice\PhpSpreadsheet\Spreadsheet;
  use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
  use Photonic\MensHelper;
  use Photonic\SalaryHelper;
  use Photonic\ScheduleHelper;
  use Photonic\AttendanceHelper;

  class SalaryController extends GlobalController {
    function _initialize(){
      parent::_initialize();
      
      $excludeAction = ['salary_detail'] ;// 排除權限的功能
      if(!in_array(strtolower(ACTION_NAME),$excludeAction)){
        parent::check_has_access(CONTROLLER_NAME, 'red');
      }
      $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
      $this->table_name = 'salary';

      $powercat_id = 140;
      $powercat = D('powercat')->find($powercat_id);
      $this->powercat_current = $powercat;
      $this->assign('page_title_active', $powercat_id);	/*右上子選單active*/

      $next_month = strtotime(date('Y-m-d').' +1Month');
      $num=0;
      $year=date("Y", $next_month);
      for($j=date("m", $next_month);$j>=1;$j--){
        $salary_ym[$num++]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
        if(count($salary_ym)>=3){ break; }
      }
      for($y=1;$y<=3;$y++){
        if(count($salary_ym)>=3){ break; }
        for($i=12;$i>=1;$i--){
          if(count($salary_ym)>=3){ break; }
          $salary_ym[$num++]=($year-$y)."".str_pad($i,2,'0',STR_PAD_LEFT);
        }
      }
      $this->get_salary_ym = $salary_ym;
      $this->assign("salary_ym",$salary_ym);
    }

    public function index(){
      /*預設操作前一個月的薪資*/
      if(!isset($_GET['salary_ym'])){
        $get_salary_ym = end($this->get_salary_ym);
        $this->redirect('index?salary_ym='.$get_salary_ym);
      }

      $salary_ym_selected = $_GET['salary_ym'] ?? '';
      $this->assign('salary_ym_selected', $salary_ym_selected);
      $this->display();
    }
    public function get_salarys(){
      $user_id_selected = $_GET['user_id'] ?? '0';
      $this->assign('user_id_selected', $user_id_selected);
      $cond['salary_date_s'] = $_GET['salary_date_s'] ?? '';
      $cond['salary_date_e'] = $_GET['salary_date_e'] ?? '';
      $salary = MensHelper::get_user_salary($user_id_selected, $cond);
      // dump($salary);exit;
      $return_data['salary'] = $salary;

      $salary_all['total_pay_hour'] = 0;
      $salary_all['total_pay_month'] = 0;
      $salary_all['total_bonus'] = 0;
      $salary_all['total_bonus_award'] = 0;
      $salary_all['total_rest_deduct'] = 0;
      $salary_all['total_salary'] = 0;
      $salary_all['insurance_personal_pay'] = 0;
      $salary_all['total_salary_can_get'] = 0;
      $salary_all['insurance_company_pay'] = 0;
      foreach ($salary as $key => $value) {
        $salary_all['total_pay_hour'] += $value['total_pay_hour'];
        $salary_all['total_pay_month'] += $value['total_pay_month'];
        $salary_all['total_bonus'] += $value['total_bonus'];
        $salary_all['total_bonus_award'] += $value['total_bonus_award'];
        $salary_all['total_rest_deduct'] += $value['total_rest_deduct'];
        $salary_all['total_salary'] += $value['total_salary'];
        $salary_all['insurance_personal_pay'] += $value['insurance_personal_pay'];
        $salary_all['total_salary_can_get'] += $value['total_salary'] - $value['insurance_personal_pay'];
        $salary_all['insurance_company_pay'] += $value['insurance_company_pay'];
      }
      $return_data['salary_all'] = $salary_all;


      $user_is_options = [];
      $mens_working = MensHelper::get_mens_working();
      foreach ($mens_working as $key => $value) {
        $user_is_options[$value['id']] = ['user_id'=>$value['id'], 'name'=>$value['name']];
      }
      foreach ($salary as $key => $value) {
        $user_is_options[$value['user_id']] = ['user_id'=>$value['user_id'], 'name'=>$value['name']];
      }
      $return_data['user_is_options'] = $user_is_options;

      $this->ajaxreturn($return_data);
    }
    /*下載月薪格式範例*/
    public function example_month_salary(){
      $salary_ym = $_GET['salary_ym'] ?? '';
      $decode_salary_ym = SalaryHelper::decode_salary_ym($salary_ym);
      $title=array(
        "ID",
        "員工類型",
        "員工碼",
        "姓名",
        "報到日期",
        SalaryHelper::$example_month_count_name,
      );
      array_push($title, SalaryHelper::$example_month_salary_note);
      // dump($title);exit;
      
      $eip_user_right_type = D('eip_user_right_type')->index('id')->select();
      $salary = D('salary')->where('year='.$decode_salary_ym['year'].' AND month='.$decode_salary_ym['month'])->index('user_id')->select();
      // dump($salary);exit;
      
      $export_data = [];
      $user = MensHelper::get_mens_with_salary_record([
        'working_and_stop' => 1, /*在職或留職停薪*/
        'salary_record_date' => $decode_salary_ym['year'].'-'.$decode_salary_ym['month'].'-01',
        'pay_type' => 1,
      ]);
      // dump($user);exit;
      foreach ($user as $key => $value) {
        array_push($export_data, [
          "ID" => $value['id'],
          "員工類型" => $eip_user_right_type[$value['right']]['name'] ?? '',
          "員工碼" => $value['no'],
          "姓名" => $value['name'],
          "報到日期" => $value['dutday'],
          SalaryHelper::$example_month_count_name => $salary[$value['id']]['month_count'] ?? '',
          SalaryHelper::$example_month_salary_note => $value['is_job']==2 ? '(留職停薪)' : '',
        ]);
      }
      // dump($export_data);exit;
      parent::DataDbOut($export_data,$title,$list_start="A2",$file_title=$salary_ym."月薪格式匯入檔");
    }
    /*下載加給格式範例*/
    public function example_bonus_salary(){
      $salary_ym = $_GET['salary_ym'] ?? '';
      $decode_salary_ym = SalaryHelper::decode_salary_ym($salary_ym);
      $title=array(
        "ID",
        "員工類型",
        "員工碼",
        "姓名",
        "報到日期",
      );
      $bonus_names_result = SalaryHelper::get_bonus_names();
      $bonus_names = $bonus_names_result['bonus_names'];
      $bonus_array = $bonus_names_result['bonus_array'];
      $title = array_merge($title, $bonus_names);
      array_push($title, SalaryHelper::$example_bonus_salary_note);
      // dump($title);exit;

      $export_data = [];
      $user = MensHelper::get_mens_with_salary_record([
        'working_and_stop' => 1, /*在職或留職停薪*/
        'salary_record_date' => $decode_salary_ym['year'].'-'.$decode_salary_ym['month'].'-01',
      ]);
      // dump($user);exit;
      foreach ($user as $key => $value) {
        $eip_user_right_type = D('eip_user_right_type')->where('id='.$value['right'])->find();
        $user_data = [
          "ID" => $value['id'],
          "員工類型" => $eip_user_right_type ? $eip_user_right_type['name'] : '',
          "員工碼" => $value['no'],
          "姓名" => $value['name'],
          "報到日期" => $value['dutday'],
        ];
        $bonus = $value['bonus'] ? json_decode($value['bonus'], true) : [];
        foreach ($bonus_array as $k => $v) {
          $user_data[$v['name']] = isset($bonus[$v['id']]) ? $bonus[$v['id']]["num"] : 0;
        }
        $user_data[SalaryHelper::$example_bonus_salary_note] = $value['is_job']==2 ? '(留職停薪)' : '';
        array_push($export_data, $user_data);
      }
      // dump($export_data);exit;
      parent::DataDbOut($export_data,$title,$list_start="A2",$file_title=$salary_ym."加給格式匯入檔");
    }

    /*以匯入檔案方式設定薪資(月薪、獎金、勞健保)*/
    public function set_salary(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      $salary_ym = $_POST['salary_ym'] ?? '';
      $year      = substr($salary_ym, 0, 4);
      $month     = substr($salary_ym, 4, 2);
      if (!$year || !$month) {
        $this->error('請先選擇計薪年月');
      }
      if (!$salary_ym) {
        $this->error('請先選擇計薪年月');
      }
      // dump($year); dump($month); dump($month_day);

      if (
        $_FILES['month_pay']['error'] > 0
        && $_FILES['hour_pay']['error'] > 0
        && $_FILES['bonus']['error'] > 0
      ) {
        $this->error('未上傳任何檔案');
      }

      try {
        /*月薪檔案*/
        if ($_FILES['month_pay']['error'] == 0 and strlen($_FILES['month_pay']['tmp_name']) > 0) {
          SalaryHelper::importMonthPay($year, $month);
        }
        
        SalaryHelper::importHourPay($year, $month); /*計算時薪(時薪付薪)*/
        
        /*加給檔案*/
        if ($_FILES['bonus']['error'] == 0 and strlen($_FILES['bonus']['tmp_name']) > 0) {
          SalaryHelper::importBonus($year, $month);
        }

        /*計算時薪、月新、投保(個人&公司負擔)*/
        SalaryHelper::calculateSalary($salary_ym);
      } catch (Exception $e) {
        $this->error($e->getMessage());
      }

      $this->success(
        '操作完成',
        u('Salary/index', [
          'salary_ym' => $salary_ym,
          'salary_date_s' => $year . '-' . $month . '-01',
          'salary_date_e' => $year . '-' . $month . '-01',
        ])
      );
    }
    /**
     * 已打卡紀錄設定薪資 (月薪、勞健保)
     */
    public function set_salary_by_attendance(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      // 計算薪資的年月
      $salary_ym = I('post.salary_ym');
      $decode_salary_ym = SalaryHelper::decode_salary_ym($salary_ym);
      $year = $decode_salary_ym['year'];
      $month = $decode_salary_ym['month'];
      $month_day = $decode_salary_ym['month_day'];
      
      // 撈取月薪人員
      $params = [
        'salary_record_date' => $year.'-'.$month.'-01',
        'pay_type' => 1,
      ];
      $mens_pay_month = MensHelper::get_mens_with_salary_record($params);
      // dump($mens_pay_month);
      // 取得所有需打卡日
      $attendance_dates = AttendanceHelper::getDataList($salary_ym, false, [
        'need_show' => 1,
      ]);
      // dump($attendance_dates);exit;

      $user_work_rate = []; // 在職率
      foreach ($mens_pay_month as $men) {
        $attendance_date_stamp = strtotime($year.'-'.$month.'-01');
        $dutday_stamp = strtotime($men['dutday']);
        /*初始化在職率*/
        if( !isset($user_work_rate[$men['id']]) ){
          $rate = 1.00;
          if($attendance_date_stamp < $dutday_stamp && $men['dutday']){ /*尚未報到 或 未設定到職日*/
            $date1 = date_create($year.'-'.$month.'-01'); /*本月第一天*/
            $date2 = date_create($men['dutday']);         /*到職日*/
            $diff = date_diff($date1,$date2)->days;       /*本月未到職的天數*/
            $rate -= $diff * (1 / $month_day);            /*扣除未到日數的在職率*/
          }
          $user_work_rate[$men['id']] = ['month_count'=>$rate];
        }

        // 非此員工免打卡
        if($men['use_attendance']==0){ continue; }

        foreach ($attendance_dates as $attendance) {
          // 非月薪者的打卡紀錄，跳過
          if(!isset($mens_pay_month[$attendance['user_id']])){ continue; }
          // 非此員工的打卡紀錄，跳過
          if($men['id']!=$attendance['user_id']){ continue; }
          
          // 1. 檢查日期是否大於在職日期
          if($attendance_date_stamp >= $dutday_stamp){ /*已報到*/
            // 2. 檢查月薪打卡是否有打卡上班或下班
            if (is_null($attendance['time_come']) && is_null($attendance['time_leave'])){ /*上班下班都沒打卡*/
              // 3. 檢查該日是否有請假紀錄(rest_records， apply_status需為0)
              $map                 = array();
              $map['user_id']      = $men['id'];
              $map['apply_status'] = 0;
              $rest_date = D('rest_records')->where($map)->where("'%s' BETWEEN rest_day_s AND rest_day_e", $attendance['date'])->find();
              if (count($rest_date) == 0) { /*沒有請假紀錄*/
                // 4. 檢查「排班功能」中是否有於該日點過名(上班、下班任意)
                $params                               = array();
                $params['date']                       = $attendance['date'];
                $params['roll_called']                = 1;
                $params['schedule_date_user_user_id'] = $men['id'];
                $schedule_list = ScheduleHelper::get_schedules($params, true, true);
                if (count($schedule_list) == 0) { /*也沒有排班點名*/
                  // 扣在職率
                  $user_work_rate[$men['id']]['month_count'] -= (1 / $month_day);
                }
              }
            }
          }
        }
      }
      // dump($user_work_rate);exit;

      // 計算月薪
      foreach ($user_work_rate as $user_id => $work_rate) {
        $month_count = $work_rate['month_count'] > 0 ? $work_rate['month_count'] : 0; /*在職率不可為負*/
        $month_count = round($month_count, 3);
        $month_count_detail = array();
        $month_count_detail[$this->our_company_id] = array(
          'id'  => $this->our_company_id,
          'num' => $month_count,
        );
        if ($user_id!=0 && $month_count_detail) {
          SalaryHelper::set_salary_data($user_id, $year, $month, [
            'month_count'        => $month_count,
            'month_count_detail' => json_encode($month_count_detail, JSON_UNESCAPED_UNICODE),
          ]);
        }
      }
      // 計算時薪
      SalaryHelper::importHourPay($year, $month);
      // 計算時薪、月薪、勞健保
      SalaryHelper::calculateSalary($salary_ym);

      $this->success(
        '操作完成',
        u('Salary/index', [
          'salary_ym'  => $salary_ym,
        ])
      );
    }

    /*匯出支薪列表*/
    public function excel_bank(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');
      $title=array(
        "年/月",
        "姓名",
        "薪資帳戶銀行",
        "銀行分行代號",
        "薪資帳戶帳號",
        "實領薪資",
      );

      $user_id_selected = $_GET['user_id'] ?? '0';
      $cond['salary_date_s'] = $_GET['salary_date_s'] ?? '';
      $cond['salary_date_e'] = $_GET['salary_date_e'] ?? '';
      $salary = MensHelper::get_user_salary($user_id_selected, $cond);
      // dump($salary);exit;

      $export_data = [];
      foreach($salary as $key=>$v){
        array_push($export_data, [
          "年/月" => $v['year'].'/'.$v['month'],
          "姓名"=> $v['name'],
          "薪資帳戶銀行"=> $v['bank'],
          "銀行分行代號"=> $v['bank_code'],
          "薪資帳戶帳號"=> $v['bank_account'],
          "實領薪資"=> $v['total_salary'] - $v['insurance_personal_pay'],
        ]);
      }
      // dump($export_data);exit;
      parent::DataDbOut($export_data,$title,$list_start="A2",$file_title="支薪列表-".$v['year'].str_pad($v['month'],2,'0',STR_PAD_LEFT));
    }

    /*編輯薪資資料*/
    public function update_salary(){
      parent::check_has_access(CONTROLLER_NAME, 'edi');
      $user_id = $_POST['user_id'] ?? '';
      $year = $_POST['year'] ?? '';
      $month = $_POST['month'] ?? '';
      if(!$user_id || !$year || !$month){ $this->error('資料不完整'); }
      $note = $_POST['note'];
      $insurance = $_POST['insurance'] ?? '[]';
      
      $insurance = json_decode($insurance, true);
      $insurance_personal_pay = 0;
      $insurance_company_pay = 0;
      foreach ($insurance as $key => $value) {
        if(
          array_key_exists("name",$value)
          && array_key_exists("accountant_item_id",$value)
          && array_key_exists("insurance_personal_pay",$value)
          && array_key_exists("insurance_company_pay",$value)
        ){
          $num = is_numeric($value['insurance_personal_pay']) ? (Int)$value['insurance_personal_pay'] : 0;
          $insurance_personal_pay += $num;
          $insurance[$key]['insurance_personal_pay'] = $num;
            
          $num = is_numeric($value['insurance_company_pay']) ? (Int)$value['insurance_company_pay'] : 0;
          $insurance_company_pay += $num;
          $insurance[$key]['insurance_company_pay'] = $num;
        }else{
          $this->error('投保資料有誤');
        }
      }

      $config = \HTMLPurifier_Config::createDefault();
      $purifier = new \HTMLPurifier($config);
      $update_data = [
        'insurance' => json_encode($insurance, JSON_UNESCAPED_UNICODE),
        'insurance_personal_pay' => $insurance_personal_pay,
        'insurance_company_pay' => $insurance_company_pay,
        'note' => $purifier->purify($note),
      ];
      $error_msg = SalaryHelper::set_salary_data($user_id, $year, $month, $update_data);
      if($error_msg){
        $this->error($error_msg);
      }
      $this->success('操作成功');
    }
    /*核可薪資資料*/
    public function confirm_salary($id=''){
      parent::check_has_access(CONTROLLER_NAME, 'edi');

      $salary = D('salary')->where('id="'.$id.'"')->find();
      if(!$salary){ $this->error('無此對象'); }
      D('salary')->where('id="'.$id.'"')->save(['confirm_time' => time()]);
      parent::error_log('核可薪資資料:'.$id);

      $this->success('操作成功');
    }
    /*薪資單內容*/
    public function salary_detail($id=''){
      $isAdmin = parent::IsAdmin(CONTROLLER_NAME, 'red');
      $userId = session('eid');
      $queryWhere = ['id' => $id];
      if (!$isAdmin) {
        $queryWhere['user_id'] = $userId;
      }

      $salary = D('salary')->where($queryWhere)->find();
      if (!$salary) {$this->error('無此對象或者你沒有這個權限');}

      $salary['bonus_detail'] = $salary['bonus_detail'] ? json_decode($salary['bonus_detail'], true) : [];
      $salary['rest_detail'] = $salary['rest_detail'] ? json_decode($salary['rest_detail'], true) : [];
      $salary['insurance'] = $salary['insurance'] ? json_decode($salary['insurance'], true) : [];
      // dump($salary);exit;
      $this->assign('salary', $salary);

      $hour_count_detail = $salary['hour_count_detail'] ? json_decode($salary['hour_count_detail'], true) : [];
      $hour_count_detail_sort = new \ArrayObject($hour_count_detail);
      $hour_count_detail_sort->uasort(function($a, $b){
        if ($a['date'] == $b['date']) {
          if ($a['name'] == $b['name']) {
            return 0;
          }
          return ($a['name'] < $b['name']) ? -1 : 1;
        }
        return ($a['date'] < $b['date']) ? -1 : 1;
      });
      // dump($hour_count_detail);exit;
      $this->assign('hour_count_detail', (Array)$hour_count_detail_sort);
  
      $month_day = date('t', $salary['year'] . '-' . $salary['month'] . '-01');
      $this->assign('month_day', $month_day);
      $this->display();
    }
  }
