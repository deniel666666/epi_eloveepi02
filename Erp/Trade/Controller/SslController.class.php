<?php
	namespace Trade\Controller;
	use Think\Controller;
	use Trade\Controller\ServertemplateController;
	class SslController extends ServertemplateController 
	{	
		function _initialize()
		{	
			$c_title = "SSL";
			$controller = "Ssl";
			$prefix = "s";
			$cate_num = 3;
			parent::_initialize($c_title, $controller, $prefix, $cate_num);

			$this->assign('page_title_link_self', U('Ssl/index'));
			$this->assign('page_title_active', 109);  /*右上子選單active*/
		}

		public function index(){
			$this->assign('provider_controller', 'Sercom');
			parent::index();
		}
	}
?>		