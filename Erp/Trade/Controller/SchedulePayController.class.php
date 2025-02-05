<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\ScheduleHelper;
	use Photonic\ScheduleDetailHelper;
	
	class SchedulePayController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$powercat_id = 154;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/
		}

    public function index(){
      $this->redirect('SchedulePay/pay');
    }
    public function pay(){
      $this->display();
    }

    public function get_schedule_pay(){
      $_GET['roll_called'] = 2;               /*有點過名的 或 會計確認的*/
      $_GET['turn_salary_time'] = 1;          /*已拋轉薪資*/
      $_GET['schedule_date_user_pay_id'] = 0; /*未生成付款單*/
      $list = ScheduleHelper::get_schedules($_GET, true, true);
      $this->ajaxReturn([
        'list' => $list,
      ]);
    }
    public function create_pay_page(){
      try {
        $turn_salary_time = ScheduleDetailHelper::create_pay_page($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }

    public function create_pay_page_patch(){
      $_GET['roll_called'] = 2;               /*有點過名的 或 會計確認的*/
      $_GET['turn_salary_time'] = 1;          /*已拋轉薪資*/
      $_GET['schedule_date_user_pay_id'] = 0; /*未生成付款單*/
      $schedule_pays = ScheduleHelper::get_schedules($_GET, true, true);
      // dump($schedule_pays);exit;
      $ids = [];
      foreach ($schedule_pays as $value) {
        array_push($ids, $value['schedule_date_user_primary']);
      }
      if(count($ids)==0){
        $this->error('無款項需生成付款單');
      }
      try {
        $turn_salary_time = ScheduleDetailHelper::create_pay_page(['ids'=>$ids]);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
  
    public function pay_page(){
      $this->display();
    }
  
    public function get_schedule_pay_page(){
      $get_data = $_GET;
      $list = ScheduleDetailHelper::get_schedule_pay_page($get_data, true);
      unset($get_data['count_of_page']);
      $all = ScheduleDetailHelper::get_schedule_pay_page($get_data, true);
      $this->ajaxReturn([
        'list' => $list,                  /*分頁搜尋結果*/
        'all' => $all,                    /*全部搜尋結果*/
        'count_of_items' => count($all),  /*不依分頁搜尋的總數量*/
      ]);
    }
    public function update_pay_page(){
      $id = $_POST['id'] ?? '';
      $data = $_POST['data'] ?? [];
      try {
        ScheduleDetailHelper::update_pay_page($id, $data);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    public function confirm_pay_page(){
      try {
        ScheduleDetailHelper::confirm_pay_page($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
  }
?>