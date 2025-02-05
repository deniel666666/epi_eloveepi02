<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;
	
	class AccountantInController extends GlobalController {
		function _initialize($get_or_pay=0){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->table_name = 'accountant_item';

			$powercat_id = 132;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);	/*右上子選單active*/

			$this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
			$this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
		}

		public function index(){
			$parent_layer = D($this->table_name)->where($this->get_or_pay_where.' AND parent_id=0 AND status=1')->order('order_id asc, id asc')->select();
			foreach ($parent_layer as $key => $value) {
				$parent_layer[$key]['sub_layer'] = D($this->table_name)
														->where($this->get_or_pay_where.' AND parent_id='.$value['id'].' AND status=1')
														->order('order_id asc, id asc')->select();
			}
			$this->assign('parent_layer', $parent_layer);
			$this->display('AccountantIn/index');
		}

		public function add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');
			if(!$_POST['name']){ $this->error('請輸入名稱'); }
			$_POST['get_or_pay'] = $this->get_or_pay;
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
			unset($data['get_or_pay']);
			unset($data['parent_id']);
			unset($data['status']);
			D($this->table_name)->where($this->get_or_pay_where.' AND id='.$id)->data($data)->save();

			parent::error_log('修改資料:'.$this->table_name.', ID:'.$id.', 資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
			$this->success('操作成功', 'index');
		}
		public function delete(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');
			$id = $_GET['id'] ?? '';
			if($id==0 || !$id){ $this->error('資料有誤'); }
			if(in_array($id, [[1,2,3,998,999]])){ $this->error('不可操作'); }
			D($this->table_name)->where($this->get_or_pay_where.' AND id='.$id)->data(['status'=>0])->save();
			D($this->table_name)->where($this->get_or_pay_where.' AND parent_id='.$id)->data(['status'=>0])->save();

			parent::error_log('刪除資料:'.$this->table_name.', ID:'.$id);
			$this->success('操作成功', 'index');
		}
	}
?>
