<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\IndustrHelper;

	class IndustrController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			
			$powercat_id = 115;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

			// $this->assign('page_title', self::$system_parameter['產業次項'].'');
		} 

		/*產業大項修改頁面*/
		public function index(){
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$industr_selected = isset($_GET['industr']) ? $_GET['industr'] : "";
			$this->assign('industr_selected',$industr_selected);

			$crm_industr = IndustrHelper::get_all_industr();
			// dump($crm_industr);exit;

			$count = count($crm_industr);
			$pagwAllA = new \Think\Page($count, 20);
			$pagwAllA->setConfig('prev',"上頁");
			$pagwAllA->setConfig('next',"下頁");
			$pagwAllA->setConfig('theme',"%UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
			$pageShowA = $pagwAllA->show();
			// dump($pagwAllA);exit;
			$this->assign('page',$pageShowA);
			
			$industr = array_slice($crm_industr, $pagwAllA->firstRow, $pagwAllA->listRows);
			foreach($industr as $key => $vo){
				$industr[$key]['count'] = M("crm_crm")->where("industr = '".$vo['industr']."'")->count();
				$industr[$key]['industr2'] = M("crm_industr")->field("id,industr,industr2")
				->where("industr = '".$vo['industr']."'")->select();
				if($industr[$key]['industr2']){
					$count_industr2 = 1;
				}
				foreach($industr[$key]['industr2'] as $k => $v){
					$industr[$key]['industr2'][$k]['count'] = M("crm_crm")->where("industr='".$v['industr']."' AND industr2 = '".$v['industr2']."'")->count();
				}
			}
			$this->assign('industr',$industr);
			$this->assign('count_industr2',$count_industr2);
			
			$this->display('industr');
		}
		
		/*Api:修改產業大項*/
		public function industr_main_change(){
			parent::check_has_access(CONTROLLER_NAME, 'eid');

			$data['industr'] = $_POST['industr'];
			$industr_ori = $_POST['industr_ori'];

			if(mb_strlen($data['industr']) > 4){
				$this->error("產業大項限4個字");
			}

			if($industr_ori){
				M("crm_crm")->where("industr = '".$industr_ori."'")->data($data)->save();	
				M("crm_industr")->where("industr = '".$industr_ori."'")->data($data)->save();
				$this->success("修改成功");
			}else{
				$this->error("修改失敗");
			}
		}
		/*Api:修改產業次項*/
		public function industr_change(){
			$data['industr2'] = $_POST['industr2'];
			$up_crm = M("crm_industr")->where("id = '".$_POST['id']."'")->find();
			
			if($data['industr2'] == ""){
				parent::check_has_access(CONTROLLER_NAME, 'del');
				if(M("crm_industr")->where("id = '".$_POST['id']."'")->delete()){
					M("crm_crm")->where("industr = '".$up_crm['industr']."' and industr2='".$up_crm['industr2']."'")->data($data)->save();
					$this->success("修改成功");
				}else{
					$this->error("修改失敗");
				}
			}
			
			if(M("crm_industr")->where("industr = '".$up_crm['industr']."' and industr2 = '".$_POST['industr2']."'")->select()){
				$this->error("已有此產業次項,無法修改");
			}

			parent::check_has_access(CONTROLLER_NAME, 'edi');
			if(M("crm_industr")->where("id = '".$_POST['id']."'")->data($data)->save()){
				M("crm_crm")->where("industr = '".$up_crm['industr']."' and industr2='".$up_crm['industr2']."'")->data($data)->save();
				$this->success("修改成功");
			}
			else{
				$this->error("修改失敗");
			}
		}
		/*Api:新增產業次項*/
		public function industr_add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');

			if(isset($_POST['industr2'])){
				if($_POST['industr']==''){
					$this->error('請選擇大項');
				}
				if($_POST['industr2'] == ''){
					$this->error('請輸入次項');
				}
				if(mb_strlen($_POST['industr2'])>4){
					$this->error('大項不可超過4字');
				}
			}else{
				if($_POST['industr']==''){
					$this->error('請輸入大項');
				}
			}
			if(mb_strlen($_POST['industr'])>4){
				$this->error('大項不可超過4字');
			}

			if(M("crm_industr")->where("industr = '".$_POST['industr']."' and industr2 = '".$_POST['industr2']."'")->select()){
				$this->error('已有此類別');
			}
			if(M("crm_industr")->data($_POST)->add()){
				$this->success('新增成功', U('Industr/index', ['industr'=>$_POST['industr']]));
			}
			else{
				$this->error('新增失敗');
			}
		}

		/*Api:依照產業大項回傳次項選項(email系統需使用)*/
		public function industr_select(){		
			$industr2=M("crm_industr")->where("industr='".$_POST['industr1']."' AND industr2!=''")->group("industr2")->select();
			echo '<option value="">'.self::$system_parameter['產業次項'].'</option>';
			foreach($industr2 as $key => $vo){
				echo '<option>'.$vo['industr2'].'</option>';
			}
		}
	}
?>