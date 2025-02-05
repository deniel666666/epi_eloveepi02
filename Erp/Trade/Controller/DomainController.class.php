<?php
	namespace Trade\Controller;
	use Think\Controller;
	use Trade\Controller\ServertemplateController;
	class DomainController extends ServertemplateController 
	{
		function _initialize()
		{	
			$c_title = "域名";
			$controller = 'Domain';
			$prefix = "d";
			$cate_num = 2;
			parent::_initialize($c_title, $controller, $prefix, $cate_num);

			$this->assign('page_title_link_self', U('Domain/index'));
			$this->assign('page_title_active', 61);  /*右上子選單active*/
		}

		public function index(){
			$this->assign('provider_controller', 'Domcom');
			parent::index();
		}
	}
?>