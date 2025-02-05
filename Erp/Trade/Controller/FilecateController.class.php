<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\Common;

	class FilecateController extends GlobalController 
	{
		function _initialize(){
			parent::_initialize();
			parent::check_has_access('file', 'red');

			$km_type = D('km_types')->where('id="1"')->find();
			$powercat_id = $km_type['parent_id'];
			$km_powercat = D('powercat')->where('id='.$powercat_id)->find();
			$this->assign("page_model_link", $km_powercat['link']);
			$this->assign("page_model", $km_powercat['title']);

			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign("myparent", $powercat_id); // 右上選單子階層
			$this->assign('page_title', '共用文件');
			$this->assign('page_title_link_self', U('Filecate/index'));
			$this->assign('page_title_active', 'km_'.$km_type['id']);  /*右上子選單active*/
			
			if($_SESSION['eid'] != self::$top_adminid){
				$access = " AND (
									access_type = 'all' OR 
									(
										access_type = 'on' AND 
										apart  like '%\"".$_SESSION['apartId']."\"%'
									) OR (
										(
											access_type = 'on' OR 
											access_type = 'own'
										) AND 
										access like '%\"".$_SESSION['eid']."\"%'
									)
								) AND 
							creater != '".$_SESSION['eid']."'";
			}
			else{
				$access = " AND true";
			}


			$share_file_where = "file.file_layer = 0 AND
								 file.showtime != 'stop' AND 
								 file.number = 'FI' AND 
								 file.status = '1' AND 
								 file.start_time <= ".time()." AND 
								 file.end_time >=".time()."".$access;

			/*照時間分類*/
				$type = M("file")->field("type")
								->where($share_file_where)
								->order("type desc")->select();
				$fileall = M("file")->field("file.id,file.date,file.num,file.number,file.title,file.type,file.file_layer,file.status,file.read_person,eip_user.name as eip_user_name")
									->join('eip_user on file.creater=eip_user.id')
									->where($share_file_where)
									->order("file.type desc,file.start_time desc,file.id desc")->select();
				foreach($type as $key => $vo){
					$y = date("Y",$vo['type']);
					$m = date("m",$vo['type']);
					foreach($fileall as $k => $v){
						if($vo['type'] == $v['type']){
							$time[$y][$m][$k] = $v;
							$time[$y][$m][$k] = $v;
							if($time[$y][$m][$k]['read_ck']==''){
								$time[$y][$m][$k]['read_ck'] = Common::not_read_check($v);
							}
						}
					}
				}
				$this->assign("time",$time);
			
			$un_where = "true";
			/*已分類*/
				$sort = M("file_sort")->where("number = 'FI' and eid = '".$_SESSION['eid']."'")->select()[0];
				$sort['sort'] = json_decode($sort['sort'],true);
				$is_sort = [];
				foreach($sort['sort'] as $key => $vo){
					if( !isset($is_sort[$key]) ){ $is_sort[$key] = []; }			
					foreach($vo as $k => $v){
						$file = M("file")->field("file.id,file.date,file.num,file.number,file.title,file.type,file.file_layer,file.status,file.read_person,eip_user.name as eip_user_name")
										->join('eip_user on file.creater=eip_user.id')
										->where($share_file_where." AND file.id = ".$v)
										->order("file.type desc,file.id desc")->find();
						if($file){
							$is_sort[$key][$k] = $file;
							$is_sort[$key][$k]['read_ck'] =  Common::not_read_check($file);
						}
						$un_where .= " AND file.id != ".$v;
					}
				}
			
			/*未分類*/
			$un_sort = M("file")->field("file.id,file.date,file.num,file.number,file.title,file.type,file.file_layer,file.status,file.read_person,eip_user.name as eip_user_name")
								->join('eip_user on file.creater=eip_user.id')
								->where($share_file_where." AND ".$un_where)
								->order("file.type desc,file.id desc")->select();				
			foreach($un_sort as $k => $v){
				if($un_sort[$k]['read_ck']==''){
					$un_sort[$k]['read_ck'] =  Common::not_read_check($v);
				}
			}
			$this->assign("is_sort",$is_sort);
			$this->assign("un_sort",$un_sort);
		}
		function index(){
			$this->display('template');
		}
		function search(){
			if($_SESSION['eid'] != self::$top_adminid)
				$access = " and (access_type = 'all' or (access_type = 'on' and apart  like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%')) and creater != '".$_SESSION['eid']."'";
			else
				$access = " and true";
			if($_GET['title'] != ''){
				$_GET['title'] = trim($_GET['title']);
				$where .= " and ( title like '%".$_GET['title']."%' or note like '%".$_GET['title']."%')";
			}else{
				$where .= " and true";
			}
			$count = M("file")
			->where("showtime != 'stop' and number = 'FI' and status = '1' and start_time <= ".time()." and end_time >=".time()."".$access)
			->count();
			$Page = new \Think\Page($count,20);
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show = $Page->show();
			$file = M("file")->field("file.*,eip_user.name")
			->where("showtime != 'stop' and number = 'FI' and file.status = '1' and start_time <= ".time()." and end_time >=".time()."".$where.$access)
			->join("left join eip_user on file.creater = eip_user.id")
			->limit($Page->firstRow.','.$Page->listRows)->order("type,id")->select();
			$this->assign("show",$show);
			$this->assign("file",$file);
			$this->display();
		}
		function read(){
			if($_SESSION['eid'] != self::$top_adminid)
				$access = " and (access_type = 'all' or (access_type = 'on' and apart  like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%')) and creater != '".$_SESSION['eid']."'";
			else
				$access = " and true";
			
			$file = M("file")->field('file.*, eip_user.name user_name')
					->join('left join eip_user on eip_user.id=file.creater')
					->where("file.id = ".$_GET['id'])
					->select();
			/*
			if(M("file_sort")->where("number = 'FI' and eid = '".$_SESSION['eid']."' and sort like '%\"".$_GET['id']."\"%'")->find())
				$file[0]['is_sort'] = true;
			*/
			$file[0]['file'] = json_decode($file[0]['file'],true);
			$message = M("file_message")->where("fid = ".$_GET['id'])
			->join("left join eip_user on file_message.user_id = eip_user.id")
			->order("time DESC")->select();
			$read = json_decode($file[0]['read_person'],true);
			$file[0]['apart'] = json_decode($file[0]['apart'],true);
			$file[0]['access'] = json_decode($file[0]['access'],true);
			if($file[0]['access_type'] == 'all'){
				$read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->count();
				$read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->select();
				$read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->getField('id', true);
			}
			else if($file[0]['access_type'] == 'on'){
				$user_apart = M("eip_user")->field("apartmentid")->where("id = ".$_SESSION['eid'])->select();
				$elseid = " and (false";
				foreach($file[0]['apart'] as $k_apart => $v_apart){
					$elseid .= " or apartmentid = '".$v_apart."'";
					if($v_apart == $user_apart[0]['apartmentid']){
						$creater_apart = 1;
					}
				}
				foreach($file[0]['access'] as $key => $vo){
					$elseid .= " or id = '".$vo."'";
					if($vo == $_SESSION['eid']){
						$creater = 1;
					}
				}
				$elseid .= ")";
				$read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->count();
				$read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->select();
				$read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->getField('id', true);
			}
			else if($file[0]['access_type'] == 'own'){
				$read_num['all'] = 1;
				$read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id = '".$file[0]['creater']."'")->select();
			}
			
			/*紀錄讀取人員*/
			// if($_SESSION['eid'] != self::$top_adminid){
				$read[$_SESSION['eid']] = $_SESSION['eid'];
				$data['read_person'] = json_encode($read);
				M("file")->where("id = ".$_GET['id'])->data($data)->save();
			// }

			$read_real= [];
				foreach($read as $k => $v){
					if(in_array($k,$read_person['all_id'])){
						// array_push($read_real, $k);
						$read_real[$k] = $v;
					}
				}	
			$read = $read_real;
			
			$read_num['read'] = count($read);
			$read_num['unread'] = $read_num['all'] - $read_num['read'];
			$read_num['rate'] = round($read_num['read'] / $read_num['all'] * 100,2);
			$read_where = "and (false";
			foreach($read as $k => $v){
				$read_where .= " or id = '".$v."'";
			}
			$read_where .= ")";
			$unread_where = "and (false";
			foreach($read_person['all'] as $key => $vo){
				foreach($read as $k => $v){
					$read_where .= " or id = '".$v."'";
					if($v == $vo['id']){
						$count[$key] = 1;
					}
				}
				if($count[$key] != 1)
					$unread_where .= " or id = '".$vo['id']."'";
			}
			$unread_where .= ")";
			$read_person['read'] = M("eip_user")->field("name")->where("is_job = '1' and status = '1'".$read_where)->select();
			$read_person['unread'] = M("eip_user")->field("name")->where("is_job = '1' and status = '1'".$unread_where)->select();
			
			$pageup = M('file')->field("id,type")
			->where("id < '".$_GET['id']."' and showtime != 'stop' and number = 'FI' and status = '1' and start_time <= ".time()." and end_time >=".time()."".$access)
			->order("id desc")->limit("0,1")->select();
			$pagedown = M('file')->field("id,type")
			->where("id > '".$_GET['id']."' and showtime != 'stop' and number = 'FI' and status = '1' and start_time <= ".time()." and end_time >=".time()."".$access)
			->order("id asc")->limit("0,1")->select();
			$count = M("file_message")->where("fid = ".$_GET['id'])->count();
			$this->assign("file",$file[0]);
			$this->assign("message",$message);
			$this->assign("count",$count);
			$this->assign("read_num",$read_num);
			$this->assign("read_person",$read_person);
			$this->assign("pageup",$pageup[0]);
			$this->assign("pagedown",$pagedown[0]);
			$this->display();
		}

		function save_message(){
			// parent::check_has_access('file', 'edi');

			$data = $_POST;
			$data['user_id'] = $_SESSION['eid'];
			$data['time'] = time();
			if(M("file_message")->data($data)->add()){
				$user = M("eip_user")->field("name,img")->where("id = ".$_SESSION['eid'])->select();
				$this->ajaxReturn([
					'status' => 1,
					'info' => "留言成功",
					'name' => $user[0]['name'],
					'message' => $_POST['message'],
					'time' => date("Y-m-d H:i",time()),
				]);
			}else{
				$this->error("操作失敗");
			}
		}

		/*文章分類頁*/
		function sort_select(){
			$this->display();
		}
		/*添加分類頁*/
		function sort_add(){
			$sort = M("file_sort")->field("sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->select()[0];
			$sort['sort'] = json_decode($sort['sort'],true);
			$this->assign("sort",$sort['sort']);
			$this->display();
		}
		/*添加分類*/
		function add_sort(){
			if(!$_POST['sort']){
				$this->error("欄位不可為空");
			}

			$sort = M("file_sort")->field("sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->select()[0];
			$sort['sort'] = json_decode($sort['sort'],true);
			if( isset($sort['sort'][$_POST['sort']]) ){
				$this->error("欄位不可重複");
			}

			$sort['sort'][$_POST['sort']] = [];
			$sort['sort'] = json_encode($sort['sort'],true);
			$sort['number'] = 'FI';
			if(!M("file_sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->select()[0]){
				$sort['eid'] = $_SESSION['eid'];
				if(M("file_sort")->data($sort)->add()){
					$this->success("新增成功");
				}
				else{
					$this->error("新增失敗");
				}
			}
			else{
				if(M("file_sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->data($sort)->save()){
					$this->success("新增成功");
				}
				else{
					$this->error("新增失敗");
				}
			}
		}
		/*刪除分類*/
		function del_file_sort(){
			if(!$_GET['key']){
				$this->error("請選擇要刪除的分類");
			}

			$sort = M("file_sort")->field("sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->select()[0];
			$sort['sort'] = json_decode($sort['sort'],true);
			if( !isset($sort['sort'][$_GET["key"]]) ){
				$this->error("無此分類");
			}

			unset($sort['sort'][$_GET["key"]]);
			$sort['sort'] = json_encode($sort['sort'],true);

			if(M("file_sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->data($sort)->save()){
				$this->success("刪除成功",u('Filecate/read',"id=".$_GET['id']));
			}else{
				$this->error("刪除失敗");
			}
		}
		function mul_select_sort(){
			$seles = $_POST['sele'];
			if($seles){
				foreach ($seles as $key => $value) {
					$_POST['fid'] = $value;
					$redirect = false;
					$this->select_sort($redirect);
				}
				$this->success("分類成功");
			}else{
				$this->error("請選擇文章");
			}
		}
		function select_sort($redirect=true){
			if(!$_POST['sort'])
				$this->error("分類不可為空");
			$sort = M("file_sort")->field("sort")->where("number = 'FI'  and eid = ".$_SESSION['eid'])->select()[0];
			$sort['sort'] = json_decode($sort['sort'],true);
			
			foreach($sort['sort'] as $key => $value){
				foreach($value as $key2 => $value2){
					if($value2 == $_POST['fid']){
						unset($sort['sort'][$key][$key2]);
						if(count($sort['sort'][$key]) == 0){
							unset($sort['sort'][$key]);	
						}
						break;
					}
				}
			}

			$sort['sort'][$_POST['sort']][] = $_POST['fid'];
			//array_push($a,"blue","yellow");
			$sort['sort'] = json_encode($sort['sort'],true);
			$sort['number'] = 'FI';

			if(!M("file_sort")->where("number = 'FI'  and eid = ".$_SESSION['eid'])->select()[0]){
				$sort['eid'] = $_SESSION['eid'];
				if(M("file_sort")->data($sort)->add()){
					if($redirect){
						$this->success("分類成功",u('Filecate/read',"id=".$_POST['fid']));
					}
				}else{
					$this->error("分類失敗");
				}
			}
			else{
				if(M("file_sort")->where("number = 'FI' and eid = ".$_SESSION['eid'])->data($sort)->save()){
					if($redirect){
						$this->success("分類成功",u('Filecate/read',"id=".$_POST['fid']));
					}
				}else{
					$this->error("分類失敗");
				}
			}
		}
	}
?>


















