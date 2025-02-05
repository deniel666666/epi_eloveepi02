<?php
	namespace Trade\Controller;
	use Think\Controller;
	class OrgController extends GlobalController {
		
		function _initialize()
		{
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');
			
			$this->assign('page_title', '分組設定');
			$this->assign('page_title_link_self', U('Org/index'));
			$this->assign('page_title_active', 58);  /*右上子選單active*/
		} 
		function index(){	
			$acc = parent::get_my_access();
			if($acc['org_all'] == 0){	
				$where =" and (childeid like '%".$_SESSION['adminId']."%' or boss_id ='".$_SESSION['adminId']."' )";
			}
			
			//dump($acc);
			$cid = session('cid');
			$count=D('eip_team t')->where("status='1' {$where}")->count();
			$Page = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show = $Page->show();// 分页显示输出
			$this->assign("show",$show);

			$apartlist = D()->query("select * from `eip_team`  where status=1 {$where} order by id asc limit {$Page->firstRow},{$Page->listRows}");
			$eipList =  D()->query("SELECT id,name FROM `eip_user` where is_job = '1' and id !='".self::$top_adminid."'");
			$eipList_array =  [];
			foreach($eipList  as $k => $v){//建立eip人員資料	
				$eipList_array[$v['id']] = $v['name'];
			}
			
			foreach($apartlist as $k => $v){
				$apartlist[$k]['boss_name'] = $eipList_array[$v['boss_id']];
				$str_exp = explode("、",str_replace('"','',$v['childeid']));
				foreach($str_exp as $key => $eip_Id){
					$apartlist[$k]['childeid'] = str_replace($eip_Id,$eipList_array[$eip_Id] ,$apartlist[$k]['childeid']);
				}
			}
			$this->assign('apartlist',$apartlist);

			//部門清單
			parent::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and( no like '%正%' or no like '%臨%')",'',false,'');
			parent::index_set('eip_apart',"true",'',false,'');
			parent::index_set('eip_jobs',"true",'',false,'');
			
			$this->display("index");
		}
		
		/*垃圾桶頁面*/
		function orgljt(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');
			$this->assign('action','apart');
			
			$cid = session('cid');
			$apartlist = D()->query("select `id`,`name`,`sort` from `eip_team` where `cid`=$cid and status=0 order by `parent_id` ");
			$apartdropdownlist =data_html_options($apartlist);
			
			$jobs = D()->query("select `id`,`name`,`sort` from `eip_jobs` where `cid`=$cid and status=0 order by `sort` asc");
			$teams = D()->query("select t.`id`,t.`name` as aid from `eip_team` t where t.`cid`=$cid  and t.status=0 order by `parent_id` ");

			$gyteams = array();			
			foreach($teams as $k=>$v){
				$gyteams[$v['aid']][] = $v;				
				$gyteams[$v['aid']]['name'] = $v['aname'];
			}
			
			$this->assign('apartlist',$apartlist);
			$this->assign('dropdownlist',$apartdropdownlist);
			$this->assign('jobs',$jobs);
			$this->assign('teams',$gyteams);
			$this->display();
		}
		
		/*Api:新增組別*/
		function add_new_team(){
			parent::check_has_access(CONTROLLER_NAME, 'new');

			$data['name'] = trim(I('name'));
			if($data['name']==''){
				$this->error('操作失敗，請稍候...',U('org/index'),1);// 更新失敗
				exit;
			}
			try{	
				$data['cid'] = session('cid');
				$data['boss_id'] = I('boss_id');
				D('eip_team')->data($data)->add();
				$this->success('操作成功，請稍候...',U('org/index'),1);
			}catch(\Think\Exception $e){
				$this->error('操作失敗，請稍候...',U('org/index'),1);// 更新失敗
			};
		}

		/*Api:組別丟置垃圾桶*/
		function trash(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');

			$data['status']='0';
			foreach($_POST['id'] as $vo){
				if($vo != self::$top_teamid){
					D("eip_team")->data($data)->where("`id`=".$vo)->save();
				}else{
					$this->error('最高權限組不可刪除');
				}
			}
			$this->success('刪除成功');
		}
					
		/*編輯畫面*/
		function editer(){
			// parent::check_has_access(CONTROLLER_NAME, 'edi');

			if(isset($_GET['id'])){
				$my_apart=D("eip_team")->where("id={$_GET['id']}")->select()[0];
				$apartlist = D()->query("select * from `eip_team`  where id={$_GET['id']} and status=1 ");
				
				$team_member_IN='';
				foreach($apartlist as $k => $v){
					$each_customers 		= $v['each_customers'];
					$show_leader_customers	= $v['show_leader_customers'];
					$edit_member_customes	= $v['edit_member_customes'];
					$edit_leader_customers	= $v['edit_leader_customers'];

					$team_leader = D()->query("SELECT id,name,no,apartmentid,jobid FROM `eip_user` where is_job = '1' and id='".$v['boss_id']."' and id !='".self::$top_adminid."' ");

					if(!empty($apartlist[0]['childeid'])){
						$str_exp = explode("、",str_replace('"','',$v['childeid']));
						$team_member_IN = 'and id IN (';
						foreach($str_exp as $key => $eip_Id){
							$team_member_IN .= $eip_Id;
							if($str_exp[$key+1]){ $team_member_IN .= ','; }
						}
						$team_member_IN .= ')';
					}
				}

				
				if($team_member_IN != ''){
					$eipList =  D()->query("SELECT id,name,no,apartmentid,jobid FROM `eip_user` where is_job = '1' {$team_member_IN} ORDER BY `eip_user`.`no` ASC ");
					
					$team_member =  [];
					foreach($eipList  as $k => $v){//建立eip人員資料
						$team_member[$v['id']] = $v;
					}
				}
			}else{
				$this->redirect('org/index');
			}

			parent::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and( no like '%正%' or no like '%臨%')",'',false,'');
			parent::index_set('eip_apart',"true",'name',true,'');
			parent::index_set('eip_jobs',"true",'name',true,'');
			$this->assign("team_leader",$team_leader);
			$this->assign("team_member",$team_member);
			$this->assign('each_customers',$each_customers);
			$this->assign('show_leader_customers',$show_leader_customers);
			$this->assign('edit_member_customes',$edit_member_customes);
			$this->assign('edit_leader_customers',$edit_leader_customers);
			
			$this->assign("eip_team",$eip_team);
			$this->assign("my_apart",$my_apart);
			$this->display();
		}
		/*Api:編輯組別內容*/
		function do_editer(){ //2019/12/03 fatfat
			parent::check_has_access(CONTROLLER_NAME, 'edi');
			//dump($_POST);

			if($_POST['team_id']){
				//parent::error_log("修改組織".$_POST['id'].",資料:".$_POST."");
				switch ($_POST['type']) {
				  case "leader":
					$old_leader = D("eip_team")->where("`id`={$_POST['team_id']}")->field('boss_id')->find()["boss_id"];//原本隊長
					$old_team = D("eip_team")->where("`id`={$_POST['team_id']}  and status=1 ")->field('childeid')->find()["childeid"];
					$data['childeid'] = str_replace('"'.$_POST['id'].'"','"'.$old_leader.'"',$old_team);
					$data['boss_id'] = $_POST['id'];
					break;
					
				  case "add_member":
					if($_POST['no'] != '' && $_POST['id']== ''){
						$add_user = D("eip_user")->where("`no`='{$_POST['no']}'  and status=1 ")->field('id')->find()["id"];
					}else{
						$add_user = $_POST['id'];
						if(!is_numeric ($add_user)){
							$this->error("錯誤輸入");
						}	
					}
					
					$old_team = D("eip_team")->where("`id`={$_POST['team_id']}  and status=1 and boss_id !='{$add_user}' and( childeid NOT like '%{$add_user}%' or childeid IS NULL) ")->field('childeid')->find();
					//dump(D()->getLastSql());

					if(empty($old_team)){
						$this->error("人員衝突");
					}else{
						if(empty($old_team['childeid']))
							$data['childeid'] = '"'.$add_user.'"';
						else
							$data['childeid'] = $old_team['childeid'].'、"'.$add_user.'"';
					}
					break;
					
				  case "delete":
					$old_team = D("eip_team")->where("`id`={$_POST['team_id']}  and status=1 ")->field('childeid')->find()["childeid"];
					$str_exp = explode("、",str_replace('"','',$old_team));
					$data['childeid'] = '';
					
					foreach($str_exp as $key => $eip_Id){
						if($eip_Id != $_POST['id'])
							$data['childeid'] .= '"'.$eip_Id.'"、';
					}
					$data['childeid'] = mb_substr($data['childeid'],0,-1);
					break;
					
				  case "team_name":
					$_POST['id'] = trim($_POST['id']);
					if($_POST['id'] == ''){
						$this->error("名稱不能空白");
					}
					$data['name'] = $_POST['id'];
					break;
					
					
				  case "team_access":
					if($_POST['no']==1)
						$data[$_POST['id']] = 0;
					else
						$data[$_POST['id']] = 1;
					break;

				  case "is_kpi":
					$data[$_POST['type']] = $_POST['id'];
					break;	
				}
				
				if(D("eip_team")->where("`id`={$_POST['team_id']}")->data($data)->save()){
					$this->success("修改成功");
				}else{
					$this->error("無內容需修改");
				}				
			}
			$this->error("操作失敗");
		}
		/*Api:編輯組別狀態*/
		function statusaparts(){
			if($_POST["st"]=="還原"){
				parent::check_has_access(CONTROLLER_NAME, 'hid');			
				$data['status']='1';
				foreach($_POST['id'] as $vo){
					parent::error_log("還原組織".$vo.",資料:");
					D("eip_team")->data($data)->where("`id`=".$vo)->save();
				}
			}
			else{
				parent::check_has_access(CONTROLLER_NAME, 'del');			
				foreach($_POST['id'] as $vo){
					
					parent::error_log("刪除組織".$vo.",資料:");
					D("eip_team")->where("`id`=".$vo)->delete();
				}
			}
			$this->success("更新成功");
		}
	}
	
?>