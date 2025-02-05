<?php
	namespace Trade\Controller;
	use Think\Controller;

	use Photonic\AttendanceHelper;

	class WorkTimeController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->table_name = 'work_time';

			$powercat_id = 157;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/
		}

		public function index(){
			$work_time = AttendanceHelper::get_work_time();
			$this->assign('work_time', $work_time);

			$this->display();
		}

		public function add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');
      $time_come = I('post.time_come');
      $time_leave = I('post.time_leave');
			if(!$time_come){ $this->error('請設定上班時間'); }
			if(!$time_leave){ $this->error('請設定下班時間'); }
			D($this->table_name)->data([
        'time_come' => $time_come,
        'time_leave' => $time_leave,
      ])->add();

			parent::error_log('添加資料:'.$this->table_name.', 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
			$this->success('操作成功', 'index');
		}
		public function update(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');
			$id = $_POST['id'] ?? '';
			if($id==0 || !$id){ $this->error('資料有誤'); }
			$data = $_POST;
			unset($data['id']);
			unset($data['status']);
			D($this->table_name)->where('id='.$id)->data($data)->save();

			parent::error_log('修改資料:'.$this->table_name.', ID:'.$id.', 資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
			$this->success('操作成功', 'index');
		}
		public function delete(){
			parent::check_has_access(CONTROLLER_NAME, 'del');
			$id = I('get.id');
			if($id==0 || !$id){ $this->error('資料有誤'); }
			D($this->table_name)->where('id='.$id)->delete();

			parent::error_log('刪除資料:'.$this->table_name.', ID:'.$id);
			$this->success('操作成功', 'index');
		}
	}
?>