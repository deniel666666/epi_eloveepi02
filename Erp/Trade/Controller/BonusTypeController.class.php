<?php
	namespace Trade\Controller;
	use Think\Controller;

	use Photonic\MensHelper;

	class BonusTypeController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->table_name = 'bonus_type';

			$powercat_id = 139;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/
		}

		public function index(){
			$bonus_type = MensHelper::get_bonus_type();
			$this->assign('bonus_type', $bonus_type);

			$this->display();
		}

		public function add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');
			if(!$_POST['name']){ $this->error('請輸入名稱'); }
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
			D($this->table_name)->where('id='.$id)->data(['status'=>0])->save();

			parent::error_log('刪除資料:'.$this->table_name.', ID:'.$id);
			$this->success('操作成功', 'index');
		}
	}
?>