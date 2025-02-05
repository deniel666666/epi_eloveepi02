<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	class SalesettingController extends GlobalController
	{
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$powercat_id = 118;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		}

		function index(){
			$this->redirect('Salesetting/setting');
		}
		function setting(){
			$note = M("crm_outer_note")->select();
			$this->assign("note",$note[0]['note']);
			$this->display();
		}
		function setting_save(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');

			if(M("crm_outer_note")->where("id = 1")->data($_POST)->save()){
				$this->success('更新成功');
			}else{
				$this->error('更新失敗');
			}
		}
	}

?>
