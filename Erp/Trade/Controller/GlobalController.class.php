<?php
	namespace Trade\Controller;
	use Think\Controller;

	use Photonic\Common;
	use Trade\Controller\PublicController;

	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Style\Alignment;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	
	class GlobalController extends Controller 
	{
		private $apartId, $adminId, $userName, $adminAccess;
		protected $upload = 'Uploads/';
		public $configAll,$eip_apart,$capart;
		
		function _initialize(){
			header("Access-Control-Allow-Origin: *");
			$excludeActionAry = [
				'salary_salary_detail',							// 查看薪資
				'attendancerecords_saveData_staff'	// 員工自行打卡
			]; 
			$controller_action = strtolower(CONTROLLER_NAME.'_'.ACTION_NAME);
			
			if($controller_action=='index_index'){ /*僅於首頁*/
				$this->assign('manifest', true); /*使用PWA，提供安裝註冊通知功能*/
			}else{
				$this->assign('manifest', false); /*不使用PWA，以確保bfcache正常運作*/
			}

			$this->assign('isMobile', $this->isMobile());
			if(!session('adminId')){ /*如果無登入紀錄*/
				/*如果有post帳號,密碼*/
				if($_POST['username'] && $_POST['userpw']){
					$eip_user = D('eip_user')->where('`username`="'.$_POST['username'].'" && `userpw`="'.$_POST['userpw'].'"')->find();
					if($eip_user){/*有此使用者*/
						/*套用資料成登入資料*/
						PublicController::set_login_data($eip_user);
					}
					unset($_POST['username']);
					unset($_POST['userpw']);
				}
			}

			// 讀取登入資料
			$apartId = intval(session('apartId'));
			$adminId = intval(session('adminId'));
			$userName = session('userName');
			$adminAccess = session('adminAccess');

			// 檢查 user 是否已登入, 並跳至登入頁
				$this->apartId = $apartId;
				$this->adminId = $adminId;
				$this->userName = $userName;
				$this->adminAccess = $adminAccess;
				// 呼叫編譯用的類別
				$Edcode = new \Photonic\Edcode("");
				//dump($_SERVER['REQUEST_URI']);exit;
				if(empty($this->adminId)|| $this->adminAccess != C('ADMIN_ACCESS')){
					redirect( U('Public/login', array('jumpUri'=>($Edcode->safe_b64encode($_SERVER['REQUEST_URI']))), '') );
					// $this->error('請先登入', U('Public/login', array('jumpUri'=>($Edcode->safe_b64encode($_SERVER['REQUEST_URI']))), ''));
				}
			
			if(!in_array($controller_action,$excludeActionAry)){
				$this->_checkaccess();
			}
			$this->init_breadcrumb(); 

			$this->index_set('crm_cum_pri','id=1');

			//$this->old_seo();
			//D("crm_key_rank")->where("`update`<'2017-02-01'")->delete();

			$my_access = self::get_my_access();
			$this->my_access = $my_access;
			// dump($my_access);exit;
			$this->assign('my_access', $my_access);
		}

		//檢查權限 輸入檢查頁model
		protected function _checkaccess($model=0, $s="_red"){
			$daoModel = empty($model)?strtolower(CONTROLLER_NAME).$s : $model.$s;
			//權限資料庫
			$acc = self::get_my_access();
			//dump($acc);
			$this->assign('access', $acc);
			
			/*右上次選單*/
			$myparent=D('powercat')->where("`codenamed`='".CONTROLLER_NAME."'")->find()['parent_id'];
			$this->assign('myparent',$myparent);
			if($acc){
				if(isset($acc[$daoModel])){
					if($acc[$daoModel]=='0' && $acc['id']!=self::$admin_access_id){
						$this->error("目前尚無權限，請洽boss，幫您跳轉中...", U('Index/index'));
					}
				}
			}
		} 
		protected function _delete($model = 0,$jumpUri= 0,$param = 0,$field = 'id'){
			if(getMethod() == 'get'){
				$operate = trim($_GET['operate']);
				$item = intval($_GET[$field]);
				}else if(getMethod() == 'post'){
				$operate = trim($_POST['operate']);
				$item = $_POST[$field];
			}
			if($item){
				if(empty($operate) ||$operate !='delete') //self::_message('error','操作類型錯誤');
				$this->error('操作類型錯誤');
				$jumpUri = empty($jumpUri) ?__CONTROLLER__ : $jumpUri ;
				//echo $jumpUri;
				$daoModel = empty($model)?CONTROLLER_NAME : $model;
				$items = is_array($item) ?implode(',',$item) : $item;
				
				if(empty($param)){
					$dao = D($daoModel);
					$daoResult = $dao->where($field.' IN('.$items.')')->delete();
					//echo $dao->GetLastSql();exit;
					if(false !== $daoResult){
						$this->success("刪除成功");
						//$this->success("刪除成功");
						}else{
						//$this->_message('error',"刪除失敗",$jumpUri);
						$this->error("刪除失敗");
					}
					}else{
					self::_deleteWith($daoModel,$items,$param,$jumpUri,$field);
				}
				}else{
				//$this->_message('error',"未選擇要刪除的記錄",$jumpUri);
				$this->error("未選擇要刪除的記錄");
			}
		}
		//上傳圖片類 傳入上傳位置 回傳成功位置
		function uploadpic($finalPath){
			//import("ORG.Net.UploadFile");
			//import("@.ORG.UploadFile");
			$upload = new \Org\Net\UploadFile();
			$allowExts = "jpg,gif,png,jpeg";
			$upload->savePath = $finalPath;
			$upload->saveRule = 'uniqid';
			$upload->thumb = false;
			$upload->allowExts = empty($allowExts) ?explode(',',$riveraAttachSuffix) : explode(',',$allowExts);
			
			//dump($upload);exit;
			if(!$upload->upload()) {
				//echo ($upload->getErrorMsg());
				$main_image='';
				}else{
				$i = 0;
				$infoBuf = $upload->getUploadFileInfo();
				$main_image = '/'.$infoBuf[0]['savepath'].$infoBuf[0]['savename'];
			}
			return $main_image;
		}
		//上傳檔案類 傳入上傳位置 回傳成功位置
		function uploadfile($finalPath,$rule="uniqid"){
			//import("ORG.Net.UploadFile");
			//import("@.ORG.UploadFile");
			$upload = new \Org\Net\UploadFile();
			$upload->savePath = $finalPath;
			$upload->thumb = false;
			$upload->saveRule = $rule;
			
			if(!$upload->upload()) {
				//echo ($upload->getErrorMsg());
				$main_image='';
				}else{
				$i = 0;
				$infoBuf = $upload->getUploadFileInfo();
				$main_image = '/'.$infoBuf[0]['savepath'].$infoBuf[0]['savename'];
			}
			return $main_image;
		}
		//錯誤回報 傳入錯誤訊息
		function error_log($log){
			Common::error_log($log);
		}
		
		
		//建立索引 傳入資料表名
		function index_set($table,$where="true",$word="name",$rname=true,$order=NULL, $frontend_name=''){//2019/11/08  fatfat改版
			$result = Common::index_set($table, $where, $word, $rname, $order);
			if($frontend_name){
				$this->assign($frontend_name, $result);
			}else{
				$this->assign($table, $result);
			}
			return $result;
		}

		//資料匯出成excel(輸入list資料 title標題欄位)
		public function DataDbOut($list,$title="", $list_start="A2", $file_title="download"){
			$objPHPExcel = new Spreadsheet();

			$objPHPExcel->setActiveSheetIndex(0);
			$objPHPExcel->getActiveSheet()->setTitle('Simple');
			$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
			
			$objPHPExcel->getActiveSheet()->fromArray($title, null, 'A1');
			$objPHPExcel->getActiveSheet()->fromArray($list, null, $list_start);
			for($col = 'A'; $col !== 'Z'; $col++) {
				// 指定寬度
				$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
				// 水平置中
				$objPHPExcel->getActiveSheet()->getStyle($col)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
				// 垂直置中
				$objPHPExcel->getActiveSheet()->getStyle($col)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
			}

			$writer = new Xlsx($objPHPExcel, 'Excel2007');
			$objPHPExcel->setActiveSheetIndex(0);
			ob_end_clean();
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="'.$file_title.'.xlsx"');
			header('Cache-Control: max-age=0');
			ob_end_clean();
			$writer->save('php://output');
		}
		
		//設定eip_user子成員
		public function set_childeid(){
			
			$eip_user=D("eip_user")->where("`status`='1' and `is_job`=1")->select();
			$this->eip_team=D("eip_team")->where("`status`='1'")->order(" `parent_id` asc")->select();
			
			foreach($this->eip_team as $key=>$vo){
				$this->eip_team[$key]['count']=0;
				$this->capart="";
				$this->searchtree($vo['id']);
				//$this->eip_team[$key]['child']=implode(",",$this->capart);
				
				$new_team[$vo['id']]=$this->eip_team[$key];
				$new_team[$vo['id']]['child']=implode(",",$this->capart);
				D("eip_team")->where("id=".$vo['id'])->data("child_count=".count($this->capart))->save();
			}
			foreach($eip_user as $key=>$vo){
				$cid=[];
				$cid[]="'".$vo['id']."'";
				$datac['childeid']="'".$vo['id']."'";
				if($new_team[$vo['teamid']]['child']!=null){
					if($vo['id']==$newteam[$vo['teamid']]['boss_id']){
						if($new_team[$vo['teamid']]['child']==null){
							$data="'{$vo['teamid']}',{$new_team[$vo['teamid']]['child']}";
							}else{
							$data="'{$vo['teamid']}'";
						}
						}else{
						
						$data=$new_team[$vo['teamid']]['child'];
					}
					//有權限的部門人員
					$n_user=D("eip_user")->field("id,name")->where("teamid in({$data}) and status='1' and is_job='1'")->select();
					
					
					
					foreach($n_user as $keyx=>$vox){
						$cid[]="'".$vox['id']."'";
					}
					$datac['childeid']=implode(",",$cid);
				}
				//dump($vo['id'].":".$datac['childeid']);
				D("eip_user")->data($datac)->where("`id`='{$vo['id']}'")->save();
				if($vo['id']==session("adminId")){
					session('childeid',$datac['childeid']);
				}
			}
		}

		//遞迴抓部門分布
		public function searchtree($p){
			$check=false;
			foreach($this->eip_team as $key =>$vo){
				//dump($vo['parent_id'].".".$p);
				if($vo['parent_id']==$p && $vo['id']!=$p){
					array_push($this->capart, "'".$vo['id']."'");
					$this->searchtree($vo['id']);
				}
			}
		}

		public function old_seo(){
			$sql="select * from crm_key_rank where `update`='".date("Y-m-d")."'";
			$old=M()->db(1,"DB_ERP_CRM")->query($sql);
		}

		function datetime($post_year,$post_month){
			$datetime_st='';
			$datetime_end='';
			
			if($post_year!=''){
				
				if($post_month=='')
				{
					$datetime=$post_year.'-01-01';
					$datetime_st = date('Y-m-01', strtotime($datetime));
					$datetime=$post_year.'-12-01';
					$datetime_end = date('Y-m-d', strtotime(date('Y-m-01', strtotime($datetime)) . ' +1 month -1 day'));

					$where.=" and (create_time >=".strtotime($datetime_st)." and  create_time <=".strtotime($datetime_end).")";
					return $where;
				}else{

					$datetime=$post_year.'-'.$post_month.'-01';
					$datetime_st = date('Y-m-01', strtotime($datetime));
					$datetime_end = date('Y-m-d', strtotime(date('Y-m-01', strtotime($datetime)) . ' +1 month'));
					$where.=" and (create_time >=".strtotime($datetime_st)." and  create_time <=".strtotime($datetime_end).")";
					return $where;
				}
			}else{
				if($post_month!=''){
					$date_year=date("Y");
					$datetime=$date_year.'-'.$post_month.'-01';
					$datetime_st = $datetime_st = date('Y-m-01', strtotime($datetime));
					$datetime_end = date('Y-m-d', strtotime(date('Y-m-01', strtotime($datetime)) . ' +1 month '));
					$where.=" and (create_time >=".strtotime($datetime_st)." and  create_time <=".strtotime($datetime_end).")";
					return $where;
				}
			}
		}

		/*取得自己的權限*/
		static public function get_my_access($user_id=0){
			$acc = Common::get_my_access($user_id);
			return $acc;
		}
		/*依照傳入的controller, 操作方法，判斷是否有權限使用*/
		function check_has_access($acc_type, $acc_method){
			$acc_type = strtolower($acc_type);
			$acc = self::get_my_access(); /*取得我的權限*/
			if(!$acc){ $this->error('此帳號無對應權限，請重新登入'); }

			if(isset($acc[$acc_type.'_'.$acc_method])){ /*有設置權限*/
				if($acc['id']==self::$admin_access_id){ /*如果使用的是管理員權限，一率可操作*/
				}
				else if($acc[$acc_type.'_'.$acc_method]==0){
					$this->error('您沒有權限操作');
				}
			}
		}
		/**
		 * IsAdmin
		 * 依照傳入的controller 取得bool在判斷
		 * 
		 * @return bool
		 */
		function IsAdmin($acc_type, $acc_method)
		{
			$acc_type = strtolower($acc_type);
			$acc = self::get_my_access(); /*取得我的權限*/
			if(!$acc){
				return 0 ;
			}
			if($acc['id']==self::$admin_access_id){ /*如果使用的是管理員權限，一率可操作*/
				return 1 ; 
			}
			else if($acc[$acc_type.'_'.$acc_method]==0){
				return 0 ;
			}
			return 1 ;
		}

		/*之後要捨棄不用*/
			function check_all_access($name,$user_id=NULL){//檢查看全部or指定瀏覽的 2019/10/09 fatfat
				//dump($name);
				$daoModel .= empty($model)?strtolower($name)."_red," : $model."_red,";
				$daoModel .= empty($model)?strtolower($name)."_all," : $model."_all,";
				$daoModel .= empty($model)?strtolower($name)."_edi," : $model."_edi,";
				$daoModel .= empty($model)?strtolower($name)."_new," : $model."_new,";
				$daoModel .= empty($model)?strtolower($name)."_del," : $model."_del,";
				$daoModel .= empty($model)?strtolower($name)."_hid" : $model."_hid";

				$acc=D('access')->field($daoModel)->where('id='.session('accessId'))->select()[0];
				if($user_id!=NULL  && $user_id!=self::$top_adminid){
					$team_access = D("eip_team")
										->field('id,boss_id,childeid,each_customers,show_leader_customers,edit_member_customes,edit_leader_customers')
										->where("(`id`={$user_id}  and status=1 ) AND ( 
													boss_id = '".$_SESSION['adminId']."' OR 
												  	childeid like '%".$_SESSION['adminId']."%'
												 )")
										->find();	
					$acc[strtolower($name)."boss_id"] = $team_access['boss_id'];
					$acc[strtolower($name)."childeid"] = explode("、",str_replace('"','',$team_access['childeid']));
					$acc[strtolower($name)."edit_leader_customers"] = $team_access['edit_leader_customers'];
					$acc[strtolower($name)."edit_member_customes"] = $team_access['edit_member_customes'];
				}
				return $acc;
			}
			function check_edi_access($check){//檢查是否可編輯 2019/10/09 fatfat
				if($check == '0'){
					$this->error('沒有編輯權限');
				}
			}

		public function isMobile() {
		    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
		}

		public function change_order($order_table, $order_column, $new_order, $related_rows){
			$same_order = M($order_table)->where($related_rows." AND ".$order_column."=".$new_order)->select();
			if($same_order){
				$rows = M($order_table)->where($related_rows." AND ".$order_column.">=".$new_order)->select();
				foreach ($rows as $key => $row) {
					$order_data[$order_column] = (int)$row[$order_column] + 1;
					M($order_table)->where("id =".$row['id'])->data($order_data)->save();
				}
			}
		}

		/*初始化麵包屑資料(3階資料於各controller中另外調整)*/
		public function init_breadcrumb(){
			$controller = strtolower(CONTROLLER_NAME);
			$action = strtolower(ACTION_NAME);

			if($controller=='filecate'){ /*如果是共用文件，改找尋 file */
				$controller='file';
			}

			/*處理頂端選單active*/
				$parent_powercat_id = "";
				$parent_powercat = [];
				$current_powercat = D('powercat')->where('LOWER(codenamed)="'.$controller.'"')->find();
				if($current_powercat){
					$parent_powercat = D('powercat')->where('id="'.$current_powercat['parent_id'].'"')->find();
					if($parent_powercat){
						$parent_powercat_id = $parent_powercat['id'];
					}
				}
				$this->assign('parent_powercat_id', $parent_powercat_id);			

			/*次階資料*/
				$page_model_link=""; $page_model=""; 
				if($parent_powercat){
					$page_model = $parent_powercat['title'];
					$page_model_link = $parent_powercat['link'];
				}
				if($page_model_link){ $this->assign('page_model_link', $page_model_link); }
				if($page_model){ $this->assign('page_model', $page_model); }
			/*3階資料(預設連結為請求網址，個別controller可設定 page_title_link_self 來修正3階連結，並需設定3階名稱 page_title)*/
				if($current_powercat){ $this->assign('page_title', $current_powercat['title']); }
				$this->assign('page_title_link', $_SERVER['PHP_SELF']);
		}
	}
?>	
