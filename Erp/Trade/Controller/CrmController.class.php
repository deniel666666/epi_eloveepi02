<?php
	namespace Trade\Controller;
	use Think\Controller;
	class CrmController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			parent::index_set('crm_cum_pri','id=1');
		} 
		
		public function index()
		{	
			echo "	<script>
						localStorage.setItem('Custo.crmId','".self::$our_company_id."');
						location.href='".u('Custo/view')."';
					</script>";
			// $this->redirect("Custo/view");
		}
		
		public function get_type_crm()
		{	
			$typeid = $_GET['typeid'];
			$crm = M('crm_crm')->field("id, name, no, url1, addr, bossname, bossphone, bossmobile, bossmail, zbe, hzrq, mom")->where('typeid = '.$typeid)->select();
			// dump($crm);
			$this->ajaxReturn($crm,'JSON');
		}

		public function update_crm()
		{	
			$id = $_POST['id'];
			unset($_POST['id']);
			try{
				// $crm_data = M("crm_crm")->where("id = ".$id)->find();
				// $_POST['mom'] = $crm_data['mom'] + ' ' + $_POST['mom'];
				M("crm_crm")->where("id = ".$id)->data($_POST)->save();
				echo "1";
			}catch(\Exception $e){
				echo "0";
			}
		}
	}
?>