<?php
	namespace Trade\Controller;
	use Think\Controller;
	class ResdocController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			$this->assign('page_title_link_self', U('Resdoc/index'));
			$this->assign('page_title_active', 87);  /*右上子選單active*/
		} 
		
		public function index(){
			$resources_document=D("resources_document")->order("date desc")->select();
			
			$this->assign("resources_document",$resources_document);
			parent::index_set('eip_user');
			$this->display();
		}

		public function do_add(){
			if($_POST['title']!=""){
				$_POST['date']=time();
				$_POST['name']=session("userName");
				D("resources_document")->data($_POST)->add();
				
				
				$this->success("新增成功");
				
				
				}else{
				
				$this->error("新增失敗");
			}
		}
		
		public function view(){
			$resources_document=D("resources_document")->where("id=".$_GET['id'])->find();
			$this->assign("resources_document",$resources_document);
			$this->display();
		}
	}
	
	
?>