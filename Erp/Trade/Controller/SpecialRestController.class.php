<?php
	namespace Trade\Controller;
	use Think\Controller;

	class SpecialRestController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->table_name = 'special_rest';

			$powercat_id = 136;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/
		}

		public function index(){
			$special_rest = D($this->table_name)->order('seniority asc')->select();
			$this->assign('special_rest', $special_rest);

			$special_rest_adjust = [];
			$index=0; $count=10;
			while ($index<count($special_rest)) {
				array_push($special_rest_adjust, array_slice($special_rest, $index, $count));
				$index += $count;
			}
			$this->assign('special_rest_adjust', $special_rest_adjust);


			$this->display();
		}

		public function add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');
			if(!$_POST['seniority']){ $this->error('請輸入年資'); }
			if(!$_POST['rest_day']){ $this->error('請輸入給假天數'); }
			D($this->table_name)->data($_POST)->add();

			parent::error_log('添加資料:'.$this->table_name.', 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
			$this->success('操作成功', 'index');
		}
		// public function update(){
		// 	parent::check_has_access(CONTROLLER_NAME, 'edi');
		// 	$id = $_POST['id'] ?? '';
		// 	if($id==0 || !$id){ $this->error('資料有誤'); }
		// 	$data = $_POST;
		// 	unset($data['id']);
		// 	D($this->table_name)->where('id='.$id)->data($data)->save();

		// 	parent::error_log('修改資料:'.$this->table_name.', ID:'.$id.', 資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
		// 	$this->success('操作成功', 'index');
		// }
		public function delete(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');
			$id = $_GET['id'] ?? '';
			if($id==0 || !$id){ $this->error('資料有誤'); }
			D($this->table_name)->where('id='.$id)->delete();

			parent::error_log('刪除資料:'.$this->table_name.', ID:'.$id);
			$this->success('操作成功', 'index');
		}
	}
?>