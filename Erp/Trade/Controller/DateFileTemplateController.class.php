<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\Common;

	class DateFileTemplateController extends GlobalController 
	{
		private $daoModel, $fileCode;

		function _initialize($powercat_id){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
			$this->assign('page_title_link_self', U(CONTROLLER_NAME.'/index'));

			$this->daoModel = strtolower(CONTROLLER_NAME);
			$this->assign("daoModel", $this->daoModel);
			$this->fileCode = $powercat['description']; // 文章編碼
			$this->assign("daoModel", $this->daoModel); // 資料庫

			$daoModel = empty($model)? $this->daoModel."_new," : $model."_new,";
			$daoModel .= empty($model)? $this->daoModel."_edi," : $model."_edi,";
			$daoModel .= empty($model)? $this->daoModel."_hid," : $model."_hid,";
			$daoModel .= empty($model)? $this->daoModel."_del," : $model."_del,";
			$daoModel .= empty($model)? $this->daoModel."_all" : $model."_all";
			$this->assign("right_new", $this->daoModel."_new");
			$this->assign("right_edi", $this->daoModel."_edi");
			$this->assign("right_hid", $this->daoModel."_hid");
			$this->assign("right_del", $this->daoModel."_del");
			$this->assign("right_all", $this->daoModel."_all");

			$acc=D('access')->field($daoModel)->where('id='.session('accessId'))->select()[0];
			// dump($_SESSION['apartId']);
			// 處理你該看的sql篩選語句
			if($_SESSION['eid'] != self::$top_adminid){
				$access = " AND (
									creater = '".$_SESSION['eid']."' OR
									access_type = 'all' OR 
									(access_type = 'on' AND apart like '%\"".$_SESSION['apartId']."\"%') OR 
									(
										( access_type = 'on' OR access_type = 'own' ) AND 
										access like '%\"".$_SESSION['eid']."\"%'
									)
								)";
			}
			else{
				$access = " AND true";
			}

			$fileall = M("file")->field("id,date,num,number,title,type,creater,file_layer,status,read_person")
								->where("status = '1' AND 
									     number = '".$this->fileCode."' AND 
									     showtime != 'stop' AND 
									     start_time <= ".time()." AND 
									     end_time >=".time()."".$access)
								->order("type desc, id desc")->select();
			foreach($fileall as $k => $v){
				$y = date("Y",$v['type']);
				$m = date("m",$v['type']);

				if( !isset($leftview[$y]['month'][$m]['file']) ){
					$leftview[$y]['month'][$m]['file'] = [];
				}

				if(!isset($leftview[$y]['need_read'])){ /*還未記錄過年月的閱讀狀態，則設定0*/
					$leftview[$y]['need_read'] = 0;
					$leftview[$y]['month'][$m]['need_read'] = 0;
				}

				// 計未讀文章
				$read_count = Common::not_read_check($v);
				$v['read_ck'] = $read_count;
				if($read_count){ /*只要有未讀文章，年月的閱讀狀態就設定成1*/
					$leftview[$y]['need_read'] += 1;
					$leftview[$y]['month'][$m]['need_read'] += 1;
				}

				array_push($leftview[$y]['month'][$m]['file'], $v); /*記錄此為某年某月的文章*/
			}
			$this->assign("acc",$acc);
			$this->assign("leftview",$leftview);
			$this->assign("file_click",$_SESSION['file_click']);

			$this->assign("ACTION_NAME", ACTION_NAME);
		}
		
		function index(){
			$_SESSION['file_click']['year'] = "0";
			$_SESSION['file_click']['month'] = "0";
			$_SESSION['file_click']['id'] = "0";
			$this->assign("file_click",$_SESSION['file_click']);
			if($_SESSION['eid'] != self::$top_adminid)
				$access = " and (access_type = 'all' or (access_type = 'on' and apart like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%'))";
			else
				$access = " and true";
			if($_GET['title'] != '')
				$where = " and ( title like '%".$_GET['title']."%' or note like '%".$_GET['title']."%')";
			else
				$where = " and true";
			$count = M("file")
			->where("status = '1' and number = '".$this->fileCode."' and showtime != 'stop' and start_time <= ".time()." and end_time >=".time()."".$where.$access)
			->count();
			$Page = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show = $Page->show();
			$file = M("file")->field("file.*,eip_user.name")
						->where("file.status = '1' and number = '".$this->fileCode."' and showtime != 'stop' and start_time <= ".time()." and end_time >=".time()."".$where.$access)
						->join("left join eip_user on file.creater = eip_user.id")
						->limit($Page->firstRow.','.$Page->listRows)->order("type desc,id desc")->select();
			foreach ($file as $key => $value) {
				$file[$key]['location'] = $this->get_file_location($value);
			}
			$this->assign("show",$show);
			$this->assign("file",$file);
			
			$this->display('Datefiletemplate/index');
		}
		function others(){
			$_SESSION['file_click']['year'] = "0";
			$_SESSION['file_click']['month'] = "0";
			$_SESSION['file_click']['id'] = "0";
			$this->assign("file_click",$_SESSION['file_click']);
			if($_SESSION['eid'] != self::$top_adminid){
				// $where = " and creater = '".$_SESSION['eid']."'";
				$where = " and (access_type = 'all' or (access_type = 'on' and apart like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%'))";
			}
			else{
				$where = " and true";
			}
			if($_GET['title'] != '')
				$where .= " and title like '%".$_GET['title']."%'";
			else
				$where .= " and true";
			$count = M("file")
			->where("status = '1' and number = '".$this->fileCode."' and (showtime = 'stop' or start_time >= ".time()." or end_time <=".time().")".$where)
			->count();
			$Page = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show = $Page->show();
			$file = M("file")->field("file.*,eip_user.name")
												->where("file.status = '1' and number = '".$this->fileCode."' and (showtime = 'stop' or start_time >= ".time()." or end_time <=".time().")".$where)
												->join("left join eip_user on file.creater = eip_user.id")
												->limit($Page->firstRow.','.$Page->listRows)->order("type desc,id desc")->select();
			foreach ($file as $key => $value) {
				$file[$key]['location'] = $this->get_file_location($value);
			}
			$this->assign("show",$show);
			$this->assign("file",$file);
			$this->display('Datefiletemplate/others');
		}
		function read(){
			if($_SESSION['eid'] != self::$top_adminid)
				$access = " and (access_type = 'all' or (access_type = 'apart' and apart like '%\"".$_SESSION['apartId']."\"%') or ((access_type = 'on' or access_type = 'own') and access like '%\"".$_SESSION['eid']."\"%'))";
			else
				$access = " and true";
			$file = M("file")->where("id = ".$_GET['id'])->select();
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

			/*紀錄讀取人員*/
			if($file[0]['start_time']<=time()){ /*小於發佈時間不更新*/
				$read[$_SESSION['eid']] = $_SESSION['eid'];
				$data['read_person'] = json_encode($read);
				M("file")->where("id = ".$_GET['id'])->data($data)->save();
			}
			
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
			->where("id > '".$_GET['id']."' and status = '1' and number = '".$this->fileCode."' and showtime != 'stop' and start_time <= ".time()." and end_time >=".time()."".$access)
			->order("id desc")->limit("0,1")->select();
			$pagedown = M('file')->field("id,type")
			->where("id < '".$_GET['id']."' and status = '1' and number = '".$this->fileCode."' and showtime != 'stop' and start_time <= ".time()." and end_time >=".time()."".$access)
			->order("id asc")->limit("0,1")->select();
			$count = M("file_message")->where("fid = ".$_GET['id'])->count();
			$this->assign("file",$file[0]);
			$this->assign("message",$message);
			$this->assign("count",$count);
			$this->assign("read_num",$read_num);
			$this->assign("read_person",$read_person);
			$this->assign("pageup",$pageup[0]);
			$this->assign("pagedown",$pagedown[0]);

			$this->display('Datefiletemplate/read');
		}

		function trash(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');

			$_SESSION['file_click']['year'] = "0";
			$_SESSION['file_click']['month'] = "0";
			$_SESSION['file_click']['id'] = "0";
			$this->assign("file_click",$_SESSION['file_click']);
			if($_GET['title'] != '')
				$where = " and title like '%".$_GET['title']."%'";
			else
				$where = " and true";
			$count = M("file")
			->where("status = '0' and number = '".$this->fileCode."' and creater = '".$_SESSION['eid']."'".$where)
			->count();
			$Page = new \Think\Page($count,20);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show = $Page->show();
			$file = M("file")->field("file.*,eip_user.name")
											->where("file.status = '0' and number = '".$this->fileCode."'".$where)
											->join("left join eip_user on file.creater = eip_user.id")
											->limit($Page->firstRow.','.$Page->listRows)->order("type desc,id desc")->select();
			foreach ($file as $key => $value) {
				$file[$key]['location'] = $this->get_file_location($value);
			}
			$this->assign("show",$show);
			$this->assign("file",$file);

			$this->display('Datefiletemplate/trash');
		}
		function trash_read(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');

			$file = M("file")->where("id = ".$_GET['id'])->select();
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
				$elseid = " and (false";
				foreach($file[0]['apart'] as $k_apart => $v_apart){
					if($v_apart != self::$top_adminid){
						$elseid .= " or apartmentid = '".$v_apart."'";
					}
				}
				foreach($file[0]['access'] as $key => $vo){
					if($v_apart != self::$top_adminid){
						$elseid .= " or id = '".$vo."'";
					}
				}
				$elseid .= ")";
				$read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->count();
				$read_person['all'] = M("eip_user")->where("is_job = '1' and status = '1'".$elseid)->select();
				$read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->getField('id', true);
			}
			
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

			$count = M("file_message")->where("fid = ".$_GET['id'])->count();
			$this->assign("file",$file[0]);
			$this->assign("message",$message);
			$this->assign("count",$count);
			$this->assign("read_num",$read_num);
			$this->assign("read_person",$read_person);
			
			$this->display('Datefiletemplate/trash_read');
		}

		function edit(){
			$_SESSION['note'] = "";
			if($_GET['id'] == ''){
				$_SESSION['file_click']['year'] = "0";
				$_SESSION['file_click']['month'] = "0";
				$_SESSION['file_click']['id'] = "0";
				$this->assign("file_click",$_SESSION['file_click']);
				$number = $this->fileCode;
				$eip_user = M("eip_user")->where("is_job = 1")->select();
				$apart = M("eip_apart")->where("status = 1")->select();
				foreach($apart as $key => $vo){
					$user[$vo['id']] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."' and apartmentid = '".$vo['id']."'")->select();
				}

				$f = M("file")->where("number='".$this->fileCode."' AND type='".strtotime(date('Y-m-d'))."'")->order('id desc')->find();
				$num = $f ? (Integer)$f['num'] + 1 : 1;
				$file_num = date('Ymd').str_pad($num,3,"0",STR_PAD_LEFT);; 
				$file[0]['apart'] = "[]";
				$file[0]['access'] = "[]";
				$file[0]['end_time'] = "9999999999";
				$this->assign("showtime",'1');
				$this->assign("access_type",'1');
				$this->assign("file",$file[0]);
				$this->assign("edit_code", '');
			}
			else{
				$file = M("file")->field("file.*,eip_user.name")
								 ->where("file.id = ".$_GET['id'])
								 ->join("left join eip_user on file.creater = eip_user.id")->select();
				$file_num = date('Ymd', $file[0]['date']).str_pad($file[0]['num'],3,"0",STR_PAD_LEFT);
				$file[0]['file'] = json_decode($file[0]['file'],true);
				$file[0]['update_person'] = json_decode($file[0]['update_person'],true);
				$file[0]['update_time'] = json_decode($file[0]['update_time'],true);
				$count = count($file[0]['update_person']);
				$number = $file[0]['number'];
				$eip_user = M("eip_user")->where("is_job = 1")->select();
				$apart = M("eip_apart")->where("status = 1")->select();
				foreach($apart as $key => $vo){
					$user[$vo['id']] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."' and apartmentid = '".$vo['id']."'")->select();
				}
				$read = json_decode($file[0]['read_person'],true);
				$file[0]['apart'] = json_decode($file[0]['apart'],true);
				$file[0]['access'] = json_decode($file[0]['access'],true);
				if($file[0]['access_type'] == 'all'){
					$read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->count();
					$read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'")->getField('id', true);
				}
				else if($file[0]['access_type'] == 'on'){
					$elseid = " and (false";
					foreach($file[0]['apart'] as $k_apart => $v_apart){
						$elseid .= " or apartmentid = '".$v_apart."'";
					}
					foreach($file[0]['access'] as $key => $vo){
						$elseid .= " or id = '".$vo."'";
					}
					$elseid .= ")";
					$read_num['all'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->count();
					$read_person['all_id'] = M("eip_user")->where("is_job = '1' and status = '1' and id != '".self::$top_adminid."'".$elseid)->getField('id', true);
				}
				
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
				$file[0]['apart'] = json_encode($file[0]['apart'],true);
				$file[0]['access'] = json_encode($file[0]['access'],true);
				$this->assign("file",$file[0]);
				$this->assign("edit_code", $file[0]['edit_code'] ?? '');
				$this->assign("read_num",$read_num);
				if($file[0]['showtime'] == 'now')
					$this->assign("showtime",'1');
				else if($file[0]['showtime'] == 'chos_time')
					$this->assign("showtime",'2');
				else
					$this->assign("showtime",'3');
				if($file[0]['access_type'] == 'own')
					$this->assign("access_type",'1');
				if($file[0]['access_type'] == 'all')
					$this->assign("access_type",'2');
				if($file[0]['access_type'] == 'on')
					$this->assign("access_type",'3');
			}

			// 前次勾選指定人員
			$pre_share = M("file")->field('id,access_type,access,apart')
								  ->where("creater=".$_SESSION['adminId'].' AND number="'.$this->fileCode.'" AND status!=0')
								  ->order('id desc')->limit(1)->find();
			if($pre_share){
				$pre_share['apart'] =  json_decode($pre_share['apart']);
				$pre_share['access'] =  json_decode($pre_share['access']);
			}
			$this->assign("pre_share", json_encode($pre_share));
			// dump($pre_share);// exit;
			$this->assign("file_num",$file_num); /*文號*/
			$this->assign("number",$number);
			$this->assign("apart",$apart);
			$this->assign("user",$user);
			$this->assign("count",$count);

			$this->display('Datefiletemplate/edit');
		}
		/*Api:文件新增/編輯*/
		function addfile(){
			$_SESSION['note'] = $_POST['note'];
			setcookie("note", rawurlencode($_POST['note']));
			if($_POST['title'] == '')
				$this->error("標題不可為空");
			if($_POST['access_type'] == 'on' && ($_POST['name'] == '' && $_POST['apart'] == ''))
				$this->error("閱讀權限不可為空");
			if($_POST['showtime'] == 'chos_time' && ($_POST['start_time'] == '' || ($_POST['end_time'] == '' && $_POST['time_length'] == '2')))
				$this->error("指定日期不可為空");

			unset($_POST['date']);
			unset($_POST['num']);
			unset($_POST['type']);

			$_POST['note'] = save_img_in_content($_POST['note']); //將內容內的base64圖片上傳到主機
			if($_POST['access_type'] == 'on'){
				$_POST['apart'] = json_encode($_POST['apart']);
				$_POST['access'] = json_encode($_POST['name']);
			}
			else if($_POST['access_type'] == 'own'){
				$_POST['access'] = json_encode([$_SESSION['eid']]);
			}
			else{
				$all = '';
				$_POST['access'] = json_encode($all);
			}
			if($_POST['showtime'] == 'chos_time'){
				$_POST['start_time'] = strtotime($_POST['start_time']);
				if($_POST['time_length'] == 2)
					$_POST['end_time'] = strtotime($_POST['end_time']);
				else
					$_POST['end_time'] = 9999999999;
			}
			else if($_POST['showtime'] == 'now'){
				$_POST['showtime'] = 'chos_time';
				$_POST['start_time'] = time();
				$_POST['end_time'] = 9999999999;
			}
			else{
				$_POST['start_time'] = 9999999999;
				$_POST['end_time'] = 9999999999;
			}

			$_POST['creater'] = $_SESSION['eid'];
			$_POST['read_person'] = null;
			$_POST['type'] = strtotime(date("Y-m-d", $_POST['start_time']));
			$f = M("file")->where("number='".$this->fileCode."' AND type='".$_POST['type']."' AND id!='".$_POST['id']."'")->order('id desc')->find();
			// dump($_POST['type']);
			$_POST['num'] = $f ? (Integer)$f['num'] + 1 : 1;
			// dump($_POST['num']);exit;
			if($_POST['id']=='' || $_POST['id']=='0'){ /*新增*/
				parent::check_has_access(CONTROLLER_NAME, 'new');

				$_POST['date'] = time();				
				foreach($_FILES['file']['name'] as $key => $vo){
					$_POST['file_name'][$key] = $vo;
					if($vo){
						$disname='Uploads/fig/';
						$file=parent::uploadfile($disname);
						$link='<a href="'.$file.'" download="'.$_POST['file_name'][$key].'"><img src="/Public/qhand/images/save.png" />'.$_POST['file_name'][$key].'</a>';
						$_POST['file'][$key] = $link;
					}
				}
				$_POST['file'] = json_encode($_POST['file'],true);
				/*if($_POST['orderid'] == '')
					$_POST['orderid'] = 0;*/
				$id = M("file")->data($_POST)->add();
				$_SESSION['file_click']['year'] = date("Y",$_POST['type']);
				$_SESSION['file_click']['month'] = date("Ym",$_POST['type']);
				$_SESSION['file_click']['id'] = $id;
				if($id){
					parent::error_log("新增文章:{$id}, 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
					$this->success("新增成功",u($this->daoModel.'/read').'?id='.$id);
				}
				else
					$this->error("新增失敗");
			}
			else{ /*編輯*/
				parent::check_has_access(CONTROLLER_NAME, 'edi');

				$id = $_POST['id'];
				$files = M("file")->field("file,type,edit_code")->where("id = ".$id)->select()[0];

				/*判斷是否可以編輯*/
        $edit_code = $_POST['edit_code'];
        if($edit_code!=$files['edit_code']){
          $this->error("有其他使用者已更新此文章，請重新整理頁面後再進行修改");
        }
        $_POST['edit_code'] = Common::geraHash(32);

				$_POST['file'] = json_decode($files['file']);
				$files_num = count($_POST['file']);
				foreach($_FILES['file']['name'] as $key => $vo){
					$_POST['file_name'][$key + $files_num] = $vo;
					if($vo){
						$disname='Uploads/fig/';
						$file=parent::uploadfile($disname);
						$link='<a href="'.$file.'" download="'.$_POST['file_name'][$key + $files_num].'"><img src="/Public/qhand/images/save.png" />'.$_POST['file_name'][$key + $files_num].'</a>';
						$_POST['file'][$key + $files_num] = $link;
					}
				}
				$_POST['file'] = json_encode($_POST['file'],true);
				$update_person = M("eip_user")->field("name")->where("id = ".$_SESSION['eid'])->select()[0];
				$update = M("file")->field("update_person,update_time")->where("id = ".$id)->select()[0];
				//$count = M("file")->where("id = ".$id)->count();
				$update['update_person'] = json_decode($update['update_person'],true);
				$update['update_time'] = json_decode($update['update_time'],true);
				$count = count($update['update_person']);
				$update['update_person'][$count + 1] = $update_person['name'];
				$update['update_time'][$count + 1] = time();
				$_POST['update_person'] = json_encode($update['update_person']);
				$_POST['update_time'] = json_encode($update['update_time']);

				unset($_POST['id']);
				unset($_POST['creater']);
				if($files['note'] == $_POST['note']){
					if($files['showtime'] == $_POST['showtime']){
						if($files['file'] == $_POST['file']){
							$_POST['read_person'] = $files['read_person'];
						}
					}
				}
				$_SESSION['file_click']['month'] = date("Ym",$files['type']);
				$_SESSION['file_click']['id'] = $id;

				//exit;
				$_POST['read_person']='{}';
				if(M("file")->where("id = ".$id)->data($_POST)->save()){
					parent::error_log("更新文章:{$id}, 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
					$this->success("更新成功",u($this->daoModel.'/read').'?id='.$id);
				}
				else{
					$this->error("更新失敗");
				}
			}
		}

		/*Api:讀取文件內容*/
		function aj_note(){
			if($_SESSION['note']){
				echo $_SESSION['note'];
			}
		}

		/*Api:儲存留言*/
		function save_message(){
			// parent::check_has_access(CONTROLLER_NAME, 'edi');

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

		/*Api:文件移至垃圾桶*/
		function file_del(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');
			$data['status'] = '0';
			if(M("file")->where("id='".$_POST['id']."'")->data($data)->save()){
				$this->success("刪除成功");
			}
			else{
				$this->error("刪除失敗");
			}
		}
		/*Api:文件刪除/還原*/
		function file_action(){
			if($_POST['action'] == 'recovery'){
				parent::check_has_access(CONTROLLER_NAME, 'hid');
				try{
					$data['status'] = '1';
					foreach($_POST['fid'] as $key => $vo){
						M("file")->where("id = ".$vo)->data($data)->save();
					}
					$this->success("還原成功");
				}
				catch(Exception $e){
					$this->error("還原失敗");
				}
				exit();
			}
			if($_POST['action'] == 'delete'){
				parent::check_has_access(CONTROLLER_NAME, 'del');
				try{
					foreach($_POST['fid'] as $key => $vo){
						//if(M("file_message")->where("fid = ".$vo)->select()[0])
						M("file_message")->where("fid = ".$vo)->delete();
						M("file")->where("id = ".$vo)->delete();
					}
					$this->success("刪除成功");
				}
				catch(Exception $e){
					$this->error("刪除失敗");
				}
			}
		}

		/*紀錄點擊的文章所屬年、月、id*/
		function file_click(){ 
			$_SESSION['file_click']['year'] = $_POST['year'];
			$_SESSION['file_click']['month'] = $_POST['month'];
			$_SESSION['file_click']['id'] = $_POST['fid'];
		}

		function get_file_location($file, $location=[]){
			$location=[
				date('Y', $file['type']).'年',
				date('m', $file['type']).'月',
				date('d', $file['type']).'日',
			];

			return implode('>', $location);
		}
	}
?>