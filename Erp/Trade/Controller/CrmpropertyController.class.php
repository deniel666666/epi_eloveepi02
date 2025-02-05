<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;
	
	use Photonic\Common;
	use Photonic\CustoHelper;

	class CrmpropertyController extends GlobalController {
		
		function _initialize()
		{
			parent::_initialize();
			
			$powercat_id = 114;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

			$this->fieldsTable = "crm_property";
		}

		function index(){
			parent::check_has_access(CONTROLLER_NAME, 'red');
			$this->display();
		}

		/*取得欄位*/
		public function get_feilds(){
			$crm_property = CustoHelper::get_fields($this->fieldsTable);
			$this->ajaxReturn($crm_property);
		}
		/*取得有預設答案的欄位*/
		public function get_feilds_with_defult_ans(){
			$crm_property = CustoHelper::get_fields_with_ans('crm_property', []);
			$this->ajaxReturn($crm_property);
		}

		/*新增、修改*/
		public function fields_set_save(){
			if(!isset($_POST['id'])){$this->error('儲存失敗');}
			$id = $_POST['id'];

			if($id=='0'){ /*新增*/
				parent::check_has_access(CONTROLLER_NAME, 'new');
			}
			else{ /*編輯*/
				parent::check_has_access(CONTROLLER_NAME, 'edi');
			}

			$data = [
				'title' => isset($_POST['title']) ? $_POST['title'] : "",
				'type' => isset($_POST['type']) ? $_POST['type'] : "",
				'required' => isset($_POST['required']) ? $_POST['required'] : 1,
				'special' => isset($_POST['special']) ? $_POST['special'] : 1,
				'limit' => isset($_POST['limit']) ? $_POST['limit'] : "",
				'discription' => isset($_POST['discription']) ? $_POST['discription'] : "",
				'options' => isset($_POST['options']) ? json_encode($_POST['options'], JSON_UNESCAPED_UNICODE) : "[]",
				'online' => isset($_POST['online']) ? $_POST['online'] : 1,
			];
			if(isset($_POST['order_id'])){
				$data['order_id'] = $_POST['order_id']===null ? 0 : $_POST['order_id'];
			}

			if(!$data['type']){$this->error('請選擇資料類型');}
			if(!$data['title']){$this->error('請輸入標題');}

			if($id=='0'){ /*新增*/
				$id = D($this->fieldsTable)->data($data)->add();
			}
			else{ /*編輯*/
				D($this->fieldsTable)->data($data)->where('id="'.$id.'"')->save();
			}

			if(isset($data['order_id'])){
				// 自動更新排序
				$table = $this->fieldsTable;
				$column = 'order_id';
				$order_num = $data['order_id'];
				$primary_key = 'id';
				$primary_value = $id;
				Common::instance()->auto_change_orders($table, $column, $order_num, $primary_key, $primary_value);
			}

			$this->success('儲存成功');
		}
		/*刪除*/
		public function fields_set_delete(){
			parent::check_has_access(CONTROLLER_NAME, 'del');

			if(!isset($_POST['id'])){$this->error('刪除失敗');}
			D($this->fieldsTable)->where('id="'.$_POST['id'].'"')->delete();
			$this->success('刪除成功');
		}
	}
?>