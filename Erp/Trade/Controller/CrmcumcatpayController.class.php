<?php
	namespace Trade\Controller;
	use Trade\Controller\CrmcumcatController;
	
	class CrmcumcatpayController extends CrmcumcatController{
		function _initialize($get_or_pay=1){
			parent::_initialize($get_or_pay);
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$powercat_id = 141;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		}
	}
?>