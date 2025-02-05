<?php
	namespace Trade\Controller;
	use Think\Controller;
	class DemoController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			$this->assign('page_title_active', 119);  /*右上子選單active*/
		} 
		
		public function index()
		{
			$error_log=D("error_log")->order("create_time desc")->limit(1000)->select();

			$this->assign("error_log",$error_log);
			parent::index_set('eip_user');
			$this->display();
		}
	}
	
	
?>