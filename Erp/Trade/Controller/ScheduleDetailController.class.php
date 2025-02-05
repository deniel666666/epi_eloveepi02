<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

  use Photonic\MensHelper;
	use Photonic\ScheduleHelper;
	use Photonic\ScheduleDetailHelper;
  use Photonic\GoogleStorage;
  use Photonic\AttendanceHelper;
	
	class ScheduleDetailController extends GlobalController {
		function _initialize(){
			parent::_initialize();

			$powercat_id = 152;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/

      $this->assign('page_title_link', u(CONTROLLER_NAME.'/index'));
		}

    public function index(){
      $this->redirect('ScheduleDetail/calendar');
    }
    public function calendar(){
      $this->display();
    }
    /*取得我可自行排班的日程*/
    public function get_my_schedules(){
      $params = $_GET;

      $data = [];
      $contract_ids = [-1];
      $crm_contract_user = M('crm_contract_user')->where('user_id="'.session('adminId').'"')->select();
      foreach ($crm_contract_user as $value) {
        array_push($contract_ids, $value['caseid']);
      }
      $params['contract_ids'] = $contract_ids;
      $data['list'] = ScheduleHelper::get_schedules($params);
      $this->ajaxReturn($data);
    }
    /*取得我指定年月的工種*/
    public function get_my_skill(){
      $date = I('post.date');
      $salary_ym = date('Ym', strtotime($date));
      $salary_record = MensHelper::get_user_salary_record(session('adminId'), $salary_ym);
      $data['salary_record'] = $salary_record;
      $this->ajaxReturn($data);
    }
    /*設定我的排班*/
    public function set_my_schedule(){
      $schedule_id = I('post.schedule_id');
      $user_skill = I('post.user_skill');
      $date = I('post.date');
      $date_e = I('post.date_e');
      $checked_days = I('post.checked_days');
      $worktime_s = I('post.worktime_s');
      $worktime_e = I('post.worktime_e');

      $schedules = ScheduleHelper::get_schedules(['schedule_id'=>$schedule_id]);
      if(count($schedules)!=1){
        $this->error('無效上班地點');
      }

      // 取得選擇區間中有效的日期
      $period_array = AttendanceHelper::get_days($date, $date_e, $checked_days);
      // dump($period_array);exit;
      if (count($period_array) == 0) {
        $this->error('選擇區間無新日期可建立');
      }
      
      $has_success_resault = false; /*是否有過成功結果*/
      foreach ($period_array as $target_date) {
        /*撈取日程日期*/
        $schedules_that_date = ScheduleHelper::get_schedules([
          'schedule_id' => $schedule_id, 
          'date' => $target_date,
          'turn_salary_time' => '0',
        ], true);
        $schedule_date_primary = 0;
        if(count($schedules_that_date)>0){ 
          $schedule_date_primary = $schedules_that_date[0]['schedule_date_primary'];
        }else{ /*日期不存在，新增日程日期*/
          try {
            $result = ScheduleHelper::set_schedule_date([
              'schedule_id' => $schedule_id,
              'date' => $target_date,
              'user_in_charge' => $schedules[0]['user_id'],
            ]);
            $schedule_date_primary = $result['schedule_date_primary'];
          } catch (\Exception $e) {
            if($has_success_resault){
              $this->success('操作成功，但有部分未如期新增：'.$e->getMessage());
            }else{
              $this->error($e->getMessage());
            }
          }
        }
  
        /*根據日程新增名單*/
        try {
          $result = ScheduleDetailHelper::set_schedule_date_user(
            $schedule_date_primary, 
            [ session('adminId') ], 
            $user_skill,
            [$worktime_s, $worktime_e]
          );
          $schedule_date_primary = $result['schedule_date_primary'];
        } catch (\Exception $e) {
          if($has_success_resault){
            $this->success('操作成功，但有部分未如期新增：'.$e->getMessage());
          }else{
            $this->error($e->getMessage());
          }
        }

        $has_success_resault = true; /*記錄為有成功結果*/
      }

      $this->success('操作成功');
    }
    /*自己點名*/
    public function do_my_roll_call(){
      $data = I('post.data');
      $schedule_date_user_primary = $data['schedule_date_user_primary'] ?? '';
      unset($data['schedule_date_user_primary']);

      /*檢查修改對象是否為自己*/
      $params = [
        'schedule_date_user_primary_in' => [$schedule_date_user_primary],
        'schedule_date_user_user_id' => session('adminId'),
      ];
      $schedules = ScheduleHelper::get_schedules($params, true, true);
      if(count($schedules)!=1){
        $this->error('無法修改此筆資料');
      }

      if(date('Y-m-d')!=$schedules[0]['date']){
        $this->error('限打今日的卡');
      }

      /*檢查GPS位置*/
      $location_gps = $schedules[0]['location_gps'] ?? '';
      $location_gps = explode(',', $location_gps);
      if(count($location_gps)==2){
        $distance = AttendanceHelper::getDistance(
          I('post.longitude'), // 第1組經度
          I('post.latitude'),  // 第1組經度
          trim($location_gps[1]), // 第2組緯度
          trim($location_gps[0])  // 第2組緯度
        );
        // 取絕對值做比較，是否在距離範圍內
        $location_range = $schedules[0]['location_range'] ?? '50';
        if (abs($distance) > intval($location_range)) {
          $this->error('距離太遠');
        }
      }

      /*檢查修改欄位*/
      foreach ($data as $key => $value) {
        if(!in_array($key, ['roll_call_come', 'roll_call_leave'])){
          $this->error('無法修改此欄位');
        }
      }
      try {
        ScheduleDetailHelper::update_schedule_date_user($schedule_date_user_primary, $data);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }


    public function users(){
      $this->assign('page_title', '人事管理 > 人員名單');

      $schedule_date_primary = isset($_GET['schedule_date_primary']) ? $_GET['schedule_date_primary'] : '';
      if(!$schedule_date_primary){ $this->error('網址有誤'); }
      $this->assign('schedule_date_primary', $schedule_date_primary);

      $schedule_date = ScheduleHelper::get_schedules(['schedule_date_primary'=>$schedule_date_primary], true);
      if(count($schedule_date)!=1){ $this->error('設定之工作日id無效'); }
      $this->assign('schedule_id', $schedule_date[0]['id']);
      $this->assign('has_schedule_detail_right_edit', ScheduleDetailHelper::check_schedule_detail_right_edit($schedule_date[0]['schedule_date_primary']));
      $this->assign('has_schedule_detail_right_examine', ScheduleDetailHelper::check_schedule_detail_right_examine($schedule_date[0]['schedule_date_primary']));
      $this->assign('has_schedule_detail_right_view', ScheduleDetailHelper::check_schedule_detail_right_view($schedule_date[0]['schedule_date_primary']));

      parent::index_set('eip_apart', $where="true", $word="name", $rname=false);
      parent::index_set('user_skill', $where="true", $word="name", $rname=false);

      $this->display();
    }
    public function get_mens_available(){
      $schedule_date_primary = $_POST['schedule_date_primary'] ?? '';
      $users = ScheduleDetailHelper::get_mens_available($schedule_date_primary, $_POST['params'] ?? []);
			$this->ajaxReturn([
        'users' => $users,
      ]);
		}
    public function get_schedule_dates(){
      $get_data = $_GET;
      $list = ScheduleHelper::get_schedules($get_data, true);
      unset($get_data['count_of_page']);
      $all = ScheduleHelper::get_schedules($get_data, true);
      $this->ajaxReturn([
        'list' => $list,                  /*分頁搜尋結果*/
        'count_of_items' => count($all),  /*不依分頁搜尋的總數量*/
      ]);
    }
    public function get_schedule_date_users(){
      $list = ScheduleDetailHelper::get_schedule_date_users($_GET, true, true);
      $this->ajaxReturn([
        'list' => $list,                  /*分頁搜尋結果*/
        'count_of_items' => count($list), /*不依分頁搜尋的總數量*/
      ]);
    }
    public function set_schedule_date_user(){
      $schedule_date_primary = $_POST['schedule_date_primary'] ?? '';
      $ids = $_POST['ids'] ?? [];
      $skillid = $_POST['skillid'] ?? '';
      $worktime_s = $_POST['worktime_s'] ?? '';
      $worktime_e = $_POST['worktime_e'] ?? '';
      $worktime = [];
      if($worktime_s && $worktime_e){ 
        $worktime = [$worktime_s, $worktime_e];
      }else{
        $worktime = ['00:00:00', '23:59:59'];
      }
      if(!$schedule_date_primary){ $this->error('資料不完整'); }
      if(count($ids)==0){ $this->error('資料不完整'); }
      try {
        if(!ScheduleDetailHelper::check_schedule_detail_right_edit($schedule_date_primary)){
          throw new \Exception('您無權限，或已無法修改');
        }
        ScheduleDetailHelper::set_schedule_date_user($schedule_date_primary, $ids, $skillid, $worktime);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function delete_schedule_date_user(){
      if(!isset($_GET['schedule_date_user_primary'])){ $this->error('資料不完整'); }
      if($_GET['schedule_date_user_primary']==''){ $this->error('資料不完整'); }
      $schedule_date_user_primary = $_GET['schedule_date_user_primary'] ?? '0';

      try {
        ScheduleDetailHelper::delete_schedule_date_user($schedule_date_user_primary);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function update_schedule_date_user(){
      $schedule_date_user_primary = $_POST['schedule_date_user_primary'] ?? '';
      unset($_POST['schedule_date_user_primary']);

      $schedule_date_user = D('schedule_date_user')->where('id="'.$schedule_date_user_primary.'"')->find();
      try {
        if(!ScheduleDetailHelper::check_schedule_detail_right_edit($schedule_date_user['schedule_date_id'] ?? '')){
          throw new \Exception('您無權限，或已無法修改');
        }
        ScheduleDetailHelper::update_schedule_date_user($schedule_date_user_primary, $_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }


    public function report(){
      $schedule_date_primary = isset($_GET['schedule_date_primary']) ? $_GET['schedule_date_primary'] : '';
      if(!$schedule_date_primary){ $this->error('網址有誤'); }
      $this->assign('schedule_date_primary', $schedule_date_primary);

      $schedule_date = ScheduleHelper::get_schedules(['schedule_date_primary'=>$schedule_date_primary], true);
      if(count($schedule_date)!=1){ $this->error('設定之工作日id無效'); }
      $this->assign('schedule_id', $schedule_date[0]['id']);
      $this->assign('has_schedule_detail_right_edit', ScheduleDetailHelper::check_schedule_detail_right_edit($schedule_date[0]['schedule_date_primary']));
      $this->assign('has_schedule_detail_right_examine', ScheduleDetailHelper::check_schedule_detail_right_examine($schedule_date[0]['schedule_date_primary']));
      if(!ScheduleDetailHelper::check_schedule_detail_right_view($schedule_date[0]['schedule_date_primary'])){
        $this->error('您沒有權限操作');
      }

      $this->display();
    }
    public function get_schedule_date_reports(){
      $list = ScheduleDetailHelper::get_schedule_date_reports($_GET);
      $this->ajaxReturn([
        'list' => $list,
      ]);
    }
    public function set_schedule_date_report(){
      try {
        ScheduleDetailHelper::set_schedule_date_report($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function delete_schedule_date_report(){
      try {
        ScheduleDetailHelper::delete_schedule_date_report($_GET);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function upload_files(){
      try {
        ScheduleDetailHelper::upload_files($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function delete_file(){
      try {
        ScheduleDetailHelper::delete_file($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }

    public function save_report_model(){ /*儲存驗收項目成模板*/
      try {
        ScheduleDetailHelper::save_report_model($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function get_report_models(){ /*取得驗收項目模板*/
      $list = ScheduleDetailHelper::get_report_models($_GET);
      $this->ajaxReturn([
        'list' => $list,
      ]);
    }
    public function use_report_model(){ /*套用驗收項目模板*/
      try {
        ScheduleDetailHelper::use_report_model($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function delete_report_model(){ /*刪除驗收項目模板*/
      parent::check_has_access('schedule', 'edi');
      try {
        ScheduleDetailHelper::delete_report_model($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }

    public function set_schedule_date_examine(){ /*總驗收*/
      try {
        $examine_time = ScheduleDetailHelper::set_schedule_date_examine($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success(date('Y-m-d H:i:s', $examine_time));
    }
    public function schedule_date_turn_salary(){ /*拋轉薪資*/
      try {
        $turn_salary_time = ScheduleDetailHelper::schedule_date_turn_salary($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success(date('Y-m-d H:i:s', $turn_salary_time));
    }
	}
?>