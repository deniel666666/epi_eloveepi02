<?php
	namespace Trade\Controller;

	use Photonic\MensHelper;

	class JobsController extends GlobalController {
		
		function _initialize()
		{
			parent::_initialize();

			$powercat_id = 73;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		}
		/*頁面*/
		function index(){
			parent::check_has_access('mens', 'red');

			$cid = session('cid');
			$joblist = D()->query("select * from `eip_jobs` where `cid`=$cid and status=1 ");
			$this->assign('joblist',$joblist);

			$apartlist = MensHelper::get_eip_apart(['cid' => $cid,]);
			$this->assign('apartlist',$apartlist);

			//部門清單
			parent::index_set('eip_user');

			$this->display();
		}
		
		function apartljt()
		{	
			$this->assign('page_title_link_self', u('Jobs/apartljt'));
			$this->assign('page_title', $this->powercat_current['title'].' > 垃圾桶');
			$this->assign('action','apart');
			
			$cid = session('cid');

			$joblist = D()->query("select `id`,`name`,`sort` from `eip_jobs` where `cid`=$cid and status=0 order by `sort` asc");
			$jobdropdownlist =data_html_options($joblist);
			$this->assign('joblist',$joblist);
			$this->assign('jobdropdownlist',$jobdropdownlist);

			$apartlist = D()->query("select `id`,`name`,`sort` from `eip_apart` where `cid`=$cid and status=0 order by `sort` asc");
			$apartdropdownlist =data_html_options($apartlist);
			$this->assign('apartlist',$apartlist);
			$this->assign('apartdropdownlist',$apartdropdownlist);
			
			$this->display();
		}

		/*職稱管理 api---------------*/
		function addjob(){
			parent::check_has_access('mens', 'new');

			//echo $_GET['aname'];
			$job = M("eip_jobs")->where("name = '".$_GET['aname']."' and status = '1'")->select();
			if($job){
				$this->error('職稱不可重複');
			}
			else{
				$apartname = I('aname');
				$cid = session('cid');
				$data['name']=$apartname;
				$data['cid']=$cid;
				parent::error_log("新增職稱,資料:".json_decode($data));
				$queryed = D('eip_jobs')->data($data)->add();
				$this->success($queryed);
			}
		}
		function deljobs(){
			parent::check_has_access('mens', 'hid');

			$data['status']='0';
			foreach($_POST['jobs_id'] as $vo){
					parent::error_log("修改職稱".$vo."資料".json_decode($data));
				D("eip_jobs")->data($data)->where("`id`=".$vo)->save();
				
			}
			$this->success('操作成功，請稍候...');
		}
		function job_do_editer(){
			parent::check_has_access('mens', 'edi');

			if(M("eip_jobs")->where("name = '".$_POST['name']."' and id != '".$_POST['id']."'")->select())
				$this->error("職稱不可重複");
			if(isset($_POST['id'])){
				parent::error_log("修改職稱".$_POST['id'].",資料:".$_POST."");
				if(D("eip_jobs")->where("`id`={$_POST['id']}")->data($_POST)->save()){
					$this->success("更新成功",u("Jobs/index"));
					exit;
				}
				
			}
			$this->error("更新失敗");
		}
		function job_statusaparts()
		{
			if($_POST["st"]=="還原"){
				parent::check_has_access('mens', 'hid');

				$data['status']='1';
				foreach($_POST['id'] as $vo){
					
					parent::error_log("還原職員".$vo."資料");
					D("eip_jobs")->data($data)->where("`id`=".$vo)->save();
				}
			}else{
				parent::check_has_access('mens', 'del');
				
				foreach($_POST['id'] as $vo){
					parent::error_log("刪除職員".$vo."資料");
					D("eip_jobs")->where("`id`=".$vo)->delete();
				}
			}
			$this->success("更新成功");
		}

		/*部門管理 api---------------*/
		function get_edit_apart(){
			$data = ['eip_user'=>[], 'my_apart'=>[]];
			if(isset($_GET['id'])){
				$data['my_apart']=MensHelper::get_eip_apart(['id' => $_GET['id'],])[0];
				$data['eip_user']=D("eip_user")->where("apartmentid='{$_GET['id']}' and status=1 and is_job=1 and id!='".self::$top_adminid."'")->select();
			}
			$this->ajaxReturn($data);
		}
		function addapart(){
			parent::check_has_access('mens', 'new');

			$apart = MensHelper::get_eip_apart(['name' => $_GET['aname'],]);
			if($apart){
				$this->error('部門不可重複');
			}
			else{
				$apartname = I('aname');
				$cid = session('cid');
				$data['name']=$apartname;
				$data['cid']=$cid;
				parent::error_log("新增部門,資料:".json_decode($data));
				$queryed = D('eip_apart')->data($data)->add();
				$this->success($queryed);
			}
		}
		function delaparts(){
			parent::check_has_access('mens', 'hid');

			$data['status']='0';
			foreach($_POST['apart_id'] as $vo){
				M("eip_apart")->data($data)->where("`id`=".$vo)->save();
			}
			$this->success('操作成功，請稍候...');
		}
		function apart_do_editer(){
			parent::check_has_access('mens', 'edi');

			if(M("eip_apart")->where("name='".$_POST['name']."' and id !='".$_POST['id']."'")->select())
				$this->error("部門不可重複");
			if(isset($_POST['id'])){
				if(D("eip_apart")->where("`id`={$_POST['id']}")->data($_POST)->save()){
					parent::error_log("修改部門資料".$vo.",資料:".json_encode($_POST));
					$this->success("更新成功",u("Apart/apart"));
					exit;
				}
				
			}
			
			$this->error("更新失敗");
		}
		function apart_statusaparts(){
			if($_POST["st"]=="還原"){
				parent::check_has_access('mens', 'hid');
				
				$data['status']='1';
				foreach($_POST['id'] as $vo){
					parent::error_log("還原部門".$vo.",資料:");
					D("eip_apart")->data($data)->where("`id`=".$vo)->save();
				}
			}else{
				parent::check_has_access('mens', 'del');

				foreach($_POST['id'] as $vo){
					parent::error_log("刪除部門".$vo.",資料:");
					D("eip_apart")->where("`id`=".$vo)->delete();
				}
			}
			$this->success("更新成功");
		}
	}

?>