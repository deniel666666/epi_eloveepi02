<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\FigHelper;
	use Photonic\ScheduleHelper;
	use Photonic\ContractHelper;
	use Photonic\AttendanceHelper;
	use Photonic\ScheduleDetailHelper;

	class ScheduleController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$powercat_id = 151;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/
		} 

    public function index(){
      $this->display();
    }
    public function date(){
      parent::index_set('eip_apart', $where="true", $word="name", $rname=false);
      $this->display();
    }

    public function contract_action(){
      $contract_id = filter_var($_GET['pid'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
      $show_name = filter_var($_GET['show_name'] ?? '', FILTER_SANITIZE_STRING);
      $data = ScheduleHelper::get_schedules(['contract_id'=>$contract_id]);
      if(count($data)>0){
        $this->redirect('Schedule/index_date', ['schedule_id'=>$data[0]['id']]);
      }else{
        $this->redirect('Schedule/index', ['contract_id_create'=>$contract_id, 'show_name'=>$show_name]);
      }
    }

    public function get_schedules(){
      $get_data = $_GET;
      $list = ScheduleHelper::get_schedules($get_data);
      unset($get_data['count_of_page']);
      $all = ScheduleHelper::get_schedules($get_data);
      $this->ajaxReturn([
        'list' => $list,                  /*分頁搜尋結果*/
        'count_of_items' => count($all),  /*不依分頁搜尋的總數量*/
      ]);
    }
    public function eve_step_create(){
      $eve_step_id = isset($_GET['eve_step_id']) ? $_GET['eve_step_id'] : '';
      try {
        $schedule_id = ScheduleHelper::eve_step_create($eve_step_id);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->redirect('Schedule/index_date', ['schedule_id'=>$schedule_id]);
    }
    public function set_schedule(){
      try {
        if(!ScheduleHelper::check_schedule_right_new($_POST['id']??'', $_POST['eve_step_id'])){
          throw new \Exception('您權限不足，無法操作');
        }
        ScheduleHelper::set_schedule($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function delete_schedule(){
      $schedule_id = $_GET['schedule_id'] ?? '0';
      try {
        ScheduleHelper::delete_schedule($schedule_id);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function copy_date(){
      $schedule_id          = I('post.schedule_id')??'';
      $schedule_date_primary= I('post.schedule_date_primary');
      try {
        if(!ScheduleHelper::check_schedule_right_new($schedule_id, '')){
          throw new \Exception('您權限不足，無法操作');
        }
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }

      // 檢查欄位
      $start_date   = I('post.start_date');
      $end_date     = I('post.end_date');
      $checked_days = I('post.checked_days');
      $users = I('post.users', []);
      if (!$start_date || !$end_date) $this->error('未選擇日期區間');
      if (strtotime($start_date) > strtotime($end_date)) $this->error('日期區間設定錯誤');

      // 取得選擇區間中有效的日期
      $period_array = AttendanceHelper::get_days($start_date, $end_date, $checked_days);
      // dump($period_array);exit;
      if (count($period_array) == 0) {
        $this->error('選擇區間無新日期可建立');
      }

      try {
        ScheduleDetailHelper::copy_date($schedule_id, $schedule_date_primary, $period_array, $users);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('複製成功');
    }

    public function index_date(){
      $this->assign('page_title_link', u(CONTROLLER_NAME.'/index'));

      $schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : '';
      if(!$schedule_id){ $this->error('網址有誤'); }
      $this->assign('schedule_id', $schedule_id);

      $this->assign('has_schedule_right_new', ScheduleHelper::check_schedule_right_new($schedule_id));

      parent::index_set('eip_apart', $where="true", $word="name", $rname=false);

      $this->display();
    }
    public function get_schedule_dates(){
      $list = ScheduleHelper::get_schedules($_GET, true);
      foreach ($list as $key => $value) {
        $params = [ 'schedule_date_primary' => $value['schedule_date_primary'], ];
        $list[$key]['user_selected'] = count(ScheduleHelper::get_schedules($params, true, true)); /*已選人數*/
        $params['roll_called'] = 1;
        $list[$key]['user_roll_called'] = count(ScheduleHelper::get_schedules($params, true, true)); /*點名人數*/
      }
      $this->ajaxReturn([
        'list' => $list,
      ]);
    }
    public function set_schedule_date(){
      try {
        if(!ScheduleHelper::check_schedule_right_new($_POST['schedule_id'])){
          throw new \Exception('您權限不足，無法操作');
        }
        ScheduleHelper::set_schedule_date($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function delete_schedule_date(){
      parent::check_has_access(CONTROLLER_NAME, 'del');

      if(!isset($_GET['schedule_date_primary'])){ $this->error('資料不完整'); }
      if($_GET['schedule_date_primary']==''){ $this->error('資料不完整'); }
      $schedule_date_primary = $_GET['schedule_date_primary'] ?? '0';

      try {
        ScheduleHelper::delete_schedule_date($schedule_date_primary);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }

    public function get_schedule_user_skill(){
      $schedule_id = $_POST['schedule_id'] ?? '';
      $resault_obj = ScheduleHelper::get_schedule_user_skill($schedule_id);
      $this->ajaxReturn($resault_obj);
    }
    public function get_contracts(){
      $get_data = $_GET;
      $contracts = ContractHelper::get_contracts($get_data, 0, false);
      $this->ajaxReturn([
        'contracts' => $contracts,
      ]);
    }
	}
?>