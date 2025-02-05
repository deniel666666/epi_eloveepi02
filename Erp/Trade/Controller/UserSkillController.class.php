<?php
	namespace Trade\Controller;
	use Think\Controller;

	use Photonic\MensHelper;

	class UserSkillController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->table_name = 'user_skill';

			$powercat_id = 153;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/
		}

		public function index(){
			$this->display();
		}
		public function get_user_skill(){
			$user_skill = MensHelper::get_user_skills();
			$this->ajaxReturn($user_skill);
		}

		public function add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');
			if(!$_POST['name']){ $this->error('請輸入名稱'); }
			if($_POST['hour_pay']==''){ $this->error('請輸入時薪'); }
			if($_POST['hour_pay_over']==''){ $this->error('請輸入加班時薪'); }
			if($_POST['hour_price']==''){ $this->error('請輸入請款金額'); }
			if($_POST['hour_price_over']==''){ $this->error('請輸入加班請款金額'); }
			if($_POST['unit_name']==''){ $this->error('請輸入單位'); }
			D($this->table_name)->data($_POST)->add();

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
			parent::check_has_access(CONTROLLER_NAME, 'hid');
			$id = $_GET['id'] ?? '';
			if($id==0 || !$id){ $this->error('資料有誤'); }
      if($id==1){ $this->error('不可操作'); }
			D($this->table_name)->where('id='.$id)->data(['status'=>0])->save();

			parent::error_log('刪除資料:'.$this->table_name.', ID:'.$id);
			$this->success('操作成功', 'index');
		}
	}
