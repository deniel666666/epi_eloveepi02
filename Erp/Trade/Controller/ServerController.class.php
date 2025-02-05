<?php
	namespace Trade\Controller;
	use Think\Controller;
	use Trade\Controller\ServertemplateController;
	class ServerController extends ServertemplateController 
	{
		function _initialize()
		{	
			$c_title = "主機";
			$controller = 'Server';
			$prefix = "h";
			$cate_num = 1;
			parent::_initialize($c_title, $controller, $prefix, $cate_num);

			$this->assign('page_title_link_self', U('Server/index'));
			$this->assign('page_title_active', 60);  /*右上子選單active*/
		}

		public function index(){
			$this->assign('provider_controller', 'Sercom');
			parent::index();
		}
	}
?>		