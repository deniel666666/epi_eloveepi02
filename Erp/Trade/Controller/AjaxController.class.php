<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\Common;
	use Photonic\Invoice;
	use Photonic\MoneyHelper;
	use Photonic\ProductHelper;
	use Photonic\MensHelper;

	/*用於讀取資料，且不受「指定瀏覽」控制*/
	class AjaxController extends GlobalController{
		function _initialize(){
			parent::_initialize();
		}

		/*AJAX:依部門、職稱 回傳人員select清單*/
		public function aj_getmean(){
			MensHelper::aj_getmean();
		}

		/*AJAX:商品管理 取得執行項目*/
		public function get_cat_unit_ajax($status=1, $get_or_pay=0){
			$result = ProductHelper::get_cat_unit($status, $get_or_pay);
			if(count($result['cat_units'])){
				$this->ajaxReturn($result);
			}else{
				unset($_POST['cond']['crm_id']); /*清除依客戶關聯再搜尋一次*/
				$result = ProductHelper::get_cat_unit($status, $get_or_pay);
				$this->ajaxReturn($result);
			}
		}
		/*AJAX:商品管理 取得執行項目-分類*/
		public function get_cat_unit_category_ajax($status=1, $get_or_pay=0){
			$this->ajaxReturn(ProductHelper::get_cat_unit_category($status, $get_or_pay));
		}

		/*AJAX:技能管理 取得執行項目*/
		public function get_user_skill_ajax(){
			$this->ajaxReturn(MensHelper::get_user_skills());
		}
	}
?>