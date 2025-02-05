<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;
	
	use Photonic\ProductHelper;

	class ProductController extends GlobalController{
		function _initialize($get_or_pay=0){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			
			$powercat_id = 121;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

			$this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
			$this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->assign('access_controller', strtolower(CONTROLLER_NAME));
		}

		/*商品管理編輯 畫面*/
		public function index(){
			$this->assign('search_status', ['status'=>1]);
			$this->assign('current_position', '列表');
			$this->display('Product/index');
		}
		/*商品管理垃圾桶 畫面*/
		public function trash(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');

			$this->assign('page_title', $this->powercat_current['title'].'>垃圾桶');
			$this->assign('page_title_link_self', u('Product/trash'));

			$this->assign('search_status', ['status'=>0]);
			$this->assign('current_position', '垃圾桶');
			$this->display('Product/index');
		}

		/*AJAX:商品管理 取得執行項目*/
		public function get_cat_unit_ajax($status=1){
			$this->ajaxReturn(ProductHelper::get_cat_unit($status, $this->get_or_pay));
		}
		/*AJAX:商品管理 取得執行項目-分類*/
		public function get_cat_unit_category_ajax($status=1){
			$this->ajaxReturn(ProductHelper::get_cat_unit_category($status, $this->get_or_pay));
		}

		/*商品管理 更新執行項目*/
		public function update_cum_cat_unit(){
			if( isset($_POST['id']) ){
				if( isset($_POST['delete']) ){ // 刪除
					parent::check_has_access(CONTROLLER_NAME, 'hid');
					D('crm_cum_cat_unit')->where($this->get_or_pay_where.' AND id='.$_POST['id'])->save(['status'=>0]);
					parent::error_log('移到垃圾桶crm_cum_cat_unit, ID:'.$_POST['id']);
					$this->success('移到垃圾桶成功');
				}else{ // 編輯
					parent::check_has_access(CONTROLLER_NAME, 'edi');
					if($_POST['column']=='name' && !$_POST['value']){ $this->error('請輸入品名'); }

					$data = [
						$_POST['column'] => $_POST['value'],
					];
					D('crm_cum_cat_unit')->where($this->get_or_pay_where.' AND id='.$_POST['id'])->save($data);
					parent::error_log('修改crm_cum_cat_unit, ID:'.$_POST['id'].', 資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
					$this->success('修改成功');
				}
			}else{ // 新增
				parent::check_has_access(CONTROLLER_NAME, 'new');
				if(empty($_POST['name'])){ $this->error('請輸入品名'); }

				$_POST['get_or_pay'] = $this->get_or_pay;
				$cate_id = D('crm_cum_cat_unit')->data($_POST)->add();
				parent::error_log('新增crm_cum_cat_unit, 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
				$this->success('新增成功');
			}
		}
		/*商品管理 批次操作*/
		public function patchupdate(){
			// dump($_POST);exit;
			parent::check_has_access(CONTROLLER_NAME, 'hid');

			if($_POST['action'] == '還原'){
				$data['status'] = 1;
			}else if($_POST['action'] == '移到垃圾桶'){
				$data['status'] = 0;
			}else{
				$this->error('無此操作');
			}

			try{
				foreach($_POST['ids'] as $key => $vo){
					M("crm_cum_cat_unit")->where($this->get_or_pay_where.' AND id="'.$vo.'"')->data($data)->save();
				}
				parent::error_log('批次修改crm_cum_cat_unit, 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
				$this->success($_POST['action']."成功");
			}
			catch(Exception $e){
				$this->error($_POST['action']."失敗");
			}
		}


		/*商品分類管理 更新分類*/
		public function update_cum_cat_unit_category(){
			if( isset($_POST['id']) ){
				if( isset($_POST['delete']) ){ // 刪除
					parent::check_has_access(CONTROLLER_NAME, 'hid');
					D('crm_cum_cat_unit_category')->where($this->get_or_pay_where.' AND id='.$_POST['id'])->save(['status'=>0]);
					parent::error_log('移到垃圾桶crm_cum_cat_unit_category, ID:'.$_POST['id']);
					$this->success('移到垃圾桶成功');
				}else{ // 編輯
					parent::check_has_access(CONTROLLER_NAME, 'edi');
					if($_POST['column']=='name' && !$_POST['value']){ $this->error('請輸入名稱'); }
					$data = [
						$_POST['column'] => $_POST['value'],
					];
					D('crm_cum_cat_unit_category')->where($this->get_or_pay_where.' AND id='.$_POST['id'])->save($data);
					parent::error_log('修改crm_cum_cat_unit_category, ID:'.$_POST['id'].', 資料:'.json_encode($data, JSON_UNESCAPED_UNICODE));
					$this->success('修改成功');
				}
			}else{ // 新增
				parent::check_has_access(CONTROLLER_NAME, 'new');
				if(empty($_POST['name'])){ $this->error('請輸入名稱'); }

				$_POST['get_or_pay'] = $this->get_or_pay;
				$cate_id = D('crm_cum_cat_unit_category')->data($_POST)->add();
				parent::error_log('新增crm_cum_cat_unit_category, 資料:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
				$this->success('新增成功');
			}
		}
	}
?>