<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\CustoHelper;
	use Trade\Controller\ImcrmController;

	class PublicController extends GlobalController{
		private $adminId; // user ID
		private $roleId; // 公司 ID
		private $lifeTime; // 登入有效時限
		
		function _initialize(){
			$eip_company = D('eip_company')->where('id', 1)->find();
			$this->assign('eip_company', $eip_company);

			$this->adminId = session('adminId');
			$this->roleId = session('roleId');
			$this->lifeTime = 3 * 24 * 60 * 60; // 登入有效時限(秒)
		}

		public function login(){
			/*$dsn = "mysql:host=192.168.11.110;dbname=photonic_erp";
			$user='crmcrm';      //数据库连接用户名
			$pass='OTw2mp47KtSlCCVV';          //对应的密码
			$db = new \PDO($dsn, $user, $pass);
			$version_server = $db->query('SELECT * from version')->fetchAll();
			$version_client = M("version")->select();
			$count = $db->query('SELECT count(*) from version');
			$count->execute();
			$version_count = $count->fetchColumn();
			if($version_server[$version_count-1]['version'] != $version_client[0]['version']){
				$this->success('有新的更新','http://192.168.11.110/update/update.php?client='.$version_client[0]['client'], 1);
				exit();
			}*/
			/*if(!$_GET['version']){
				$version_client = M("version")->select();
				echo "<script>window.location.href='http://192.168.11.110/update/update.php?client=".$version_client[0]['client']."'</script>";
				exit();
			}*/

			// 接收 GlobalController 傳來的資訊並解碼
			$debug = intval($_GET['debug']);
			//dump($debug);exit;
			cmsdebug($debug);
			/*
			$Config = M('config', '', 'DB_ERP_CRM');
			$where['id']=1;
			$list = $Config->where($where)->select();*/
			//dump($list);exit;
			$this->assign('company_logo', $list['company_logo']);
			$this->assign('company_name', $list['company_name']);
			
			// 呼叫編譯用的類別
			$Edcode = new \Photonic\Edcode("");
			$jumpUri = isset($_GET['jumpUri']) ? $Edcode->safe_b64decode($_GET['jumpUri']) : "";
			
			$this->assign('jumpUri', $jumpUri);
			$this->display();
		}
		
		public function dologin(){
			$username = trim($_POST['username']);
			$password = trim($_POST['userpw']);
			$mphone = trim($_POST['mphone']); // 其實我用電話
			$jumpUri = trim($_POST['jumpUri']) ? trim($_POST['jumpUri']) : U('Index/index'); // 跳轉網址
			$condition = array();
			$dbModel = D('eip_user');
			
			if( !empty($email) ){
				// 接收到變數 email, user 正在嘗試找回密碼
				$condition["mphone"] = $mphone;
				$record = $dbModel->where($condition)->select();
				print_r($record);
				if( $record == false ){
					$this->error('請輸入正確的帳號', U('Public/login'));
				}
			}//end if
			
			//dump($username);dump($password);exit;
			if(empty($username) || empty($password)){
				$this->error('帳號以及密碼不能是空的', U('Public/login','',''), 3);
			}
			
			$condition = array();
			$condition["username"] = $username;
			$condition["status"] = 1;
			$condition["is_job"] = 1;
			$record = $dbModel->where($condition)->select();

			/*自動把過期的起追客戶拋轉到開放*/
				$crmlist = D()->query("select * from `crm_crm` where typeid='1'");
				foreach($crmlist as $key =>$vo){
					$is_anysis = D("eip_user")->where("id='".$crmlist[$key]['wid']."'")->field('is_anysis')->find()['is_anysis'];

					if($crmlist[$key]['wid']!="0" && $crmlist[$key]['newclient_date']!='' && $is_anysis=='1' ){ //
						$user = D("eip_user_data")->where("eid='".$crmlist[$key]['wid']."'")->field('new_date')->find();
						$enddate = strtotime('+'.$user["new_date"].' day',strtotime($crmlist[$key]['newclient_date']));//起算日+期限限制天數轉成開放
						if($enddate < time()){
							// 新增轉開放客戶紀錄
							CustoHelper::add_salesrecord(
								$salesid = $crmlist[$key]['wid'],	// 業務id
								$opeid	 = self::$top_adminid,		// 操作人員(管理人員)
								$cid 	 = $crmlist[$key]['id'], 	// 客戶id
								$typeid  = '5'						// 修改的客戶類型(開放客戶)
							);

							// 自動把新進有效期限超過的客戶改為開放客戶
							$da['typeid'] = '5';
							// $da['did'] = '0';
							// $da['wid'] = '0';
							// $da['sid'] = '0';
							// $da['hid1'] = '0';
							// $da['hid2'] = '0';
							// $da['hid3'] = '0';
							D('crm_crm')->data($da)->where('id='.$crmlist[$key]['id'])->save();

							$crmlist[$key]['typeid'] = '5';
							$crmlist[$key]['type']=$crmtype[4]['name'];
						}
						
						//$user['new_date']
					}
				}

			//dump(md5($password));dump($record[0]['userpw']);exit;
			if($record == false){
				$this->error('帳號錯誤');
				}else{
				if($record[0]['userpw'] != md5($password)){
					$this->error('密碼錯誤', U('Public/login','',''), 3);
				}
				if(!preg_match("/^([-0-9A-Za-z]+)$/", $username) || !preg_match("/^([-0-9A-Za-z]+)$/", $password)){
					$this->error('該帳號目前已被鎖定', U('Public/login','',''), 3);
				}
				
				session(array('name'=>'session_id','expire'=>$this->lifeTime));
				// session_start();
				setcookie(session_name(), session_id(), time() + $this->lifeTime, '/');

				$this->set_login_data($record[0]);/*紀錄登入資料*/
				$login['time']=time();
				$login['eid']=$record[0]['id'];
				$login['name']=$record[0]['name'];
				$login['apartmentid']=$record[0]['apartmentid'];
				if(!empty($_SERVER['HTTP_CLIENT_IP'])){
					$myip = $_SERVER['HTTP_CLIENT_IP'];
					}else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
					$myip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					}else{
					$myip= $_SERVER['REMOTE_ADDR'];
				}
				$login['ip']=$myip;
				$count=D("eip_login")->where("time>".strtotime(date("Ymd"))." and eid=".$login['eid'])->count();
				if($count>0)$login['peach']=0;
				D("eip_login")->data($login)->add();

				if(parent::isMobile()){
					$this->success('登入成功! 歡迎您回來 '.$record[0]["username"], $jumpUri, 3);
					// $this->success('登入成功! 歡迎您回來 '.$record[0]["username"], U('Custo/search_custo','',''), 3);
				}else{
					$this->success('登入成功! 歡迎您回來 '.$record[0]["username"], $jumpUri, 3);
				}
			}
		}
		protected function set_login_data($record){
			session('userName', $record['name']);
			session('userAccount', $record['username']);
			session('adminId', $record['id']);
			session('eid',$record['id']);
			session('apartId', $record['apartmentid']);
			session('teamid', $record['teamid']);
			session('cid', $record['cid']);
			session('adminAccess', C('ADMIN_ACCESS'));
			session('accessId',$record['usergroupid']);
			session('childeid',$record['childeid']);
			session('is_anysis',$record['is_anysis']);
		}

		/***
			* logout
			* 退出登錄
			*
		***/
		public function logout(){
			//dump(session());exit;
			ImcrmController::clearList();
			
			if($this->adminId != NULL) {
				$this->success('登出成功! '.session('userName').' 辛苦了!', U('Public/login','',''), 3);
				session('adminId', NULL);
				// session('[destroy]');
				//cookie('tempTheme', NULL);
			}else {
				$this->success('此帳號已經登出囉!', U('Public/login','',''), 3);
			}
		}
		
		public function data_html_options($options){
			$temp = array();
			foreach($options as $key=>$value)
			{
				$temp[$value['id']]=$value['name'];
			}
			return $temp;
		}

		public function renew_coockie(){
			$adminId = session('adminId');
			$condition = array();
			$condition["id"] = $adminId;
			$record = D('eip_user')->where($condition)->find();
			if( !empty($adminId) ){
				setcookie(session_name(), session_id(), time() + $this->lifeTime, '/');

				$this->set_login_data($record);/*紀錄登入資料*/
				echo 'yes';
			}else{
				echo 'no';
			}
		}

		public function renew_session(){
			$adminId = session('adminId');
			$condition = array();
			$condition["id"] = $adminId;
			$record = D('eip_user')->where($condition)->find();
			if( !empty($record) ){
				$this->set_login_data($record);/*紀錄登入資料*/
				echo 'yes';
			}else{
				echo 'no';
			}
		}
	}
?>			