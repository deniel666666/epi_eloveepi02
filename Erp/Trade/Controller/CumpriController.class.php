<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	class CumpriController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$powercat_id = 116;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		} 

		// 協同人員名稱設定
		public function index(){
			if($_POST){ /*有post則更新資料*/
				parent::check_has_access(CONTROLLER_NAME, 'edi');
				for ($i = 1; $i <= 6; $i++){
					$data = $_POST["id_".$i];
					unset($data['id']);
					unset($data['ename']);
					unset($data['status']);
					unset($data['orders']);
					unset($data['note']);
					D('crm_cum_pri')->where('id='.$i)->data($data)->save();
				}
			}

			parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版
			$this->display('cum_pri');
		}
	}
?>