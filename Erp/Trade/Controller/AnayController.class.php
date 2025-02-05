<?php
	namespace Trade\Controller;

	use Trade\Controller\GlobalController;

	use Photonic\CustoHelper;

	class AnayController extends GlobalController 
	{
		function _initialize(){
			parent::_initialize();
			if(!$_SESSION['anay_tab'])
				$_SESSION['anay_tab'] = -1;
			
			$this->assign('page_title', '週分析');
			$this->assign('page_title_link_self', u('Anay/index'));
			$this->assign('page_title_active', 51);  /*右上子選單active*/
		}

		//Api:修改週分析目標
		public function ajax(){
			$this->check_anay_access('anay', 'edi', [$_POST['ajax']], $teamid=0, $_POST['dbname']);

			if( !isset($_POST['dbname']) || !isset($_POST['data']) ){//fatfat 2019/10/03 更新
				$this->error('修改失敗');
			}

			// dump($_POST['dbname']."user_id='".$_POST['ajax']."'");
			$num=D($_POST['dbname'])->field("num")->where("user_id='".$_POST['ajax']."'")->find();

			$data['num'] = json_decode($num['num'],true);
			$data['num'][$this->getweek_fday_lday($_POST["date"])]= $_POST['data'];
			$data['num'] = json_encode($data['num'],true);

			if($_POST['data']==""){
				D($_POST['dbname'])->where("user_id='".$_POST['ajax']."'")->delete();
			}else{
				D($_POST['dbname'])->data($data)->where("user_id='".$_POST['ajax']."'")->save();
			}
			$this->success('修改成功');
		}
		
		//Api:紀錄點擊的頁籤
		public function tab(){
			$_SESSION['anay_tab'] = $_POST['data'];
		}

		// 依據傳入的日期(Y-m-d格式)回傳當週的週一日期(Y-m-d格式)
		function getweek_fday_lday($thisday){//fatfat 2019/10/03
			$weekday = date("w", strtotime($thisday." - 1 days"));//取得thisday 為禮拜幾 0-6
			
			//dump($thisday);
			
			//dump($weekday);
			
			$week_fday = date("Y-m-d", strtotime("$thisday -".$weekday." days"));//該週的第一天
			//dump($week_fday);//exit;
			//$week_lday = date("Y-m-d", strtotime("$week_fday -7 days"));//抓上周的第一天
			//回傳 日期,該日期當週的第一天,該日期當週的最後一天
			return $week_fday;
		}
		// 依據傳入的目標及查詢日期、回傳每週的目標
		function getweek_num($array, $get_date=''){//fatfat 2019/10/03
			foreach($array as $ukey =>$uvo){
				
				$anay_new_num = json_decode($uvo['num'],true);
				
				if($get_date==''){
					$getweek = $this->getweek_fday_lday(date("Y-m-d",$_GET['date']));
				}else{
					$getweek = $this->getweek_fday_lday(date("Y-m-d",$get_date));
				}
				
				//$week_fday = $anay_new_num[$getweek];
				//dump($week_fday);
				
				$array[$ukey]['num'] = $anay_new_num['num'];
				
				$n_d ='2000-01-01';
				foreach($anay_new_num as $d =>$v){
					
					if($getweek >= $d && $n_d <= $d){
						$array[$ukey]['num'] = $v;	
						$n_d =$d;
					} 
					//echo '現在時候：'.$getweek.' 比較 '.$d.' 被更動的值：'.$array[$ukey]['num'].'<br>';
					//echo '上一個：'.$n_d.'<br>';	
				}
			}
			return $array;
		}	

		// 客戶轉換數據
		public function develope(){
			$this->assign("tab",$_SESSION['anay_tab']);
			$this->assign("today",date("Y-m-d",time()));
			$this->display();
		}
		public function get_week_develope(){
			if(!isset($_GET['id']))$_GET['id']="%";
			if(!isset($_GET['qulid']))$_GET['qulid']="%";

			if(!isset($_GET['date'])){
				$_GET['date']=time();
			}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			//製作日期陣列
			for($i=0;$i<7;$i++){
				$mon[$i]=date("Y-m-d",mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+$i+1, date("Y",$_GET['date'])));
			}
			// dump($mon);

			$start=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+1, date("Y",$_GET['date']));
			$end=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+7, date("Y",$_GET['date']));
			$data = $this->get_develope_data($start, $end);
			$data['mon'] = $mon;
			// dump($start.'-'.$end);
			// dump($data['record_data']);
			// exit;
			$this->ajaxReturn( $data );
		}
		public function get_month_develope(){
			if(!isset($_GET['date'])){
				$_GET['date']=time();
				}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			$start=mktime(0, 0, 0,date("m",$_GET['date']),1, date("Y",$_GET['date']));
			$end=mktime(0, 0, 0,date("m",$_GET['date'])+1,0, date("Y",$_GET['date']));
			$get_date = $_GET['date'];
			// dump($start.'-'.$end);
			// dump($data);
			$data = $this->get_develope_data($start, $end);
			$this->ajaxReturn( $data );
		}
		public function get_develope_data($start, $end){
			date_default_timezone_set('Asia/Taipei');
			$eip_user = $this->get_user();
		
			$where = "(false";
			foreach($eip_user as $key => $vo){
				$where .= " or name = '".$eip_user[$key]['name']."'";
			}
			$where .= ")";

			$data = [];
			$date_array = (int)date('d',$start) < (int)date('d',$end) ? range( (int)date('d',$start), (int)date('d',$end) ) : range(0,6); // 判斷是月或是週

			foreach ($date_array as $k => $v) {				
				$get_date =	mktime(0, 0, 0, date("m",$start), date("d",$start)+$k, date("Y",$start));
				$get_date_end = mktime(0, 0, 0, date("m",$start), date("d",$start)+$k+1, date("Y",$start));
				$day = date("d", $get_date);

				// dump($eip_user);
				$user_data = [];
				foreach($eip_user as $ukey =>$uvo){

					//績效
					$salesrecord = [];
					foreach (['1', '2', '3','5','6'] as $ctype) {
						$sql = "SELECT `salesrecord`.`ctype`, `crm_crm`.`id` as cumid, `crm_crm`.`typeid` FROM `salesrecord`
						LEFT JOIN `crm_crm` ON `crm_crm`.`id` = `salesrecord`.`cid`
						WHERE  `salesid` = '".$uvo['id']."' and ctype='".$ctype."' and `dateline` >= ".$get_date." and `dateline` < ".$get_date_end;

						$record=D()->query($sql);
						$salesrecord['ctype_'.$ctype]['count'] = count($record);
						foreach($record as $key=>$v){
							$crm_name = CustoHelper::get_crm_show_name($v['cumid']);
							$salesrecord['ctype_'.$ctype]['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');
						}
					}

					//目標
					$aimarray = [
						['type'=>'ctype_1', 'aim_db' => 'anay_new'], /*新進*/
						['type'=>'ctype_2', 'aim_db' => 'anay_old'], /*潛在*/
						['type'=>'ctype_3', 'aim_db' => 'anay_now'], /*成交*/
						['type'=>'ctype_5', 'aim_db' => 'anay_develop'], /*開放*/
						['type'=>'ctype_6', 'aim_db' => 'anay_trash'], /*垃圾桶*/
					];
					foreach($aimarray as $key=>$v){
						$dayofweek = date('w', $get_date);
						if($dayofweek == '0' || $dayofweek == '6'){
							$salesrecord[$v['type']]['aim'] = 0;
						}else{
							$weekaim = D($v['aim_db'])->where('user_id = '.$uvo['id'])->order('name')->select();
							$weekaim = $this->getweek_num($weekaim, $get_date);
							$salesrecord[$v['type']]['aim'] =$weekaim[0]['num']/5.0;
						}
					}

					//合併
					$user_data[$uvo['id']] =$salesrecord;
				}
			    array_push($data, ['date'=>$day ,'user_data'=>$user_data]);
			}

			// dump($data);
			$return_data['record_data'] = $data;
			$return_data['eip_user'] = $eip_user;
			// exit;
			return $return_data;
		}


		// 訪談品質
		public function index(){ // tadata
			$this->assign("tab",$_SESSION['anay_tab']);
			$this->assign("today",date("Y-m-d",time()));

			parent::index_set('crm_cum_type',"true",'',false);
			$this->display('tadata');
		}
		public function get_week_tadata(){
			if(!isset($_GET['id']))$_GET['id']="%";
			if(!isset($_GET['qulid']))$_GET['qulid']="%";

			if(!isset($_GET['date'])){
				$_GET['date']=time();
			}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			//製作日期陣列
			for($i=0;$i<7;$i++){
				$mon[$i]=date("Y-m-d",mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+$i+1, date("Y",$_GET['date'])));
			}
			// dump($mon);

			$start=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+1, date("Y",$_GET['date']));
			$end=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+7, date("Y",$_GET['date']));
			$data = $this->get_tadata_data($start, $end);
			$data['mon'] = $mon;
			// dump($start.'-'.$end);
			// dump($data['record_data']);
			$this->ajaxReturn( $data );
		}
		public function get_month_tadata(){
			if(!isset($_GET['date'])){
				$_GET['date']=time();
				}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			$start=mktime(0, 0, 0,date("m",$_GET['date']),1, date("Y",$_GET['date']));
			$end=mktime(0, 0, 0,date("m",$_GET['date'])+1,0, date("Y",$_GET['date']));
			$get_date = $_GET['date'];
			// dump($start.'-'.$end);
			// dump($data);
			$data = $this->get_tadata_data($start, $end);
			$this->ajaxReturn( $data );
		}
		public function get_tadata_data($start, $end){
			date_default_timezone_set('Asia/Taipei');

			$current_typeid = isset($_GET['current_typeid']) ? $_GET['current_typeid'] : "";

			$eip_user = $this->get_user();
			$where = "(false";
			foreach($eip_user as $key => $vo){
				$where .= " or name = '".$eip_user[$key]['name']."'";
			}
			$where .= ")";

			$data = [];
			$date_array = (int)date('d',$start) < (int)date('d',$end) ? range( (int)date('d',$start), (int)date('d',$end) ) : range(0,6); // 判斷是月或是週

			foreach ($date_array as $k => $v) {
				// dump($eip_user);
				$get_date =	mktime(0, 0, 0, date("m",$start), date("d",$start)+$k, date("Y",$start));
				$get_date_end = mktime(0, 0, 0, date("m",$start), date("d",$start)+$k+1, date("Y",$start));
				$day = date("d", $get_date);

				$user_data = [];
				foreach($eip_user as $ukey =>$uvo){
					$sql = "SELECT `crm_chats`.`chattype`, `crm_chats`.`eid`, `crm_chats`.`cumid`, `crm_chatqulity`.`name` as `attitude`, 
								   `crm_crm`.`typeid`,from_unixtime(`dateline`,'%Y-%m-%d') as Contact_datetime,dateline 
							FROM `crm_chats` 
							JOIN `crm_chatqulity` ON `crm_chatqulity`.`id` = `qulid`
							JOIN `crm_crm` ON `crm_crm`.`id` = `crm_chats`.`cumid`
							WHERE `dateline` >= ".$get_date." and `dateline` < ".$get_date_end." and `eid` = '".$uvo['id']."'";
					if($current_typeid!==''){
						$sql .= ' AND current_typeid="'.$current_typeid.'"';
					}    
					$record=D()->query($sql);
					// dump($record);

					$salesrecord = [];

					//目標
					$aimarray = [
						['type'=>'面談', 'aim_db' => 'anay_out'],
						['type'=>'電訪', 'aim_db' => 'anay_phone'],
						['type'=>'不排斥', 'aim_db' => 'anay_0'],
						['type'=>'有意願', 'aim_db' => 'anay_1'],
						['type'=>'被阻擋', 'aim_db' => 'anay_2'],
						['type'=>'無意願', 'aim_db' => 'anay_3'],
						['type'=>'例行事', 'aim_db' => 'anay_4'],
						// ['type'=>'轉開放', 'aim_db' => 'anay_5'],
					];
					foreach($aimarray as $key=>$v){
						$dayofweek = date('w', $get_date);
						if($dayofweek == '0' || $dayofweek == '6'){
							$salesrecord[$v['type']]['aim'] = 0;
						}else{
							$weekaim = D($v['aim_db'])->where('user_id = '.$uvo['id'])->order('name')->select();
							$weekaim = $this->getweek_num($weekaim, $get_date);
							$salesrecord[$v['type']]['aim'] =$weekaim[0]['num']/5.0;
						}
					}

					//績效
					foreach($record as $key=>$v){

						$crm_name = CustoHelper::get_crm_show_name($v['cumid']);
						//訪談方式
						if($v['chattype'] == 0 ){
							$salesrecord['面談']['count'] += 1;
							$salesrecord['面談']['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');
						}else{
							$salesrecord['電訪']['count'] += 1;
							$salesrecord['電訪']['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');

						}

						//訪談品質
						$salesrecord[$v['attitude']]['count'] += 1;
						$salesrecord[$v['attitude']]['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');
					}

					//合併
					$user_data[$uvo['id']] =$salesrecord;
				}
			    array_push($data, ['date'=>$day ,'user_data'=>$user_data]);
			}

			$return_data['record_data'] = $data;
			$return_data['eip_user'] = $eip_user;
			// exit;
			return $return_data;
		}


		// 約訪紀錄
		public function apdata(){
			$this->assign("tab",$_SESSION['anay_tab']);
			$this->assign("today",date("Y-m-d",time()));
			$this->display();
		}
		public function get_week_apdata(){
			if(!isset($_GET['id']))$_GET['id']="%";
			if(!isset($_GET['qulid']))$_GET['qulid']="%";

			if(!isset($_GET['date'])){
				$_GET['date']=time();
			}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			//製作日期陣列
			for($i=0;$i<7;$i++){
				$mon[$i]=date("Y-m-d",mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+$i+1, date("Y",$_GET['date'])));
			}
			// dump($mon);

			$start=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+1, date("Y",$_GET['date']));
			$end=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+7, date("Y",$_GET['date']));
			$data = $this->get_apdata_data($start, $end);
			$data['mon'] = $mon;
			// dump($start.'-'.$end);
			// dump($data['record_data']);
			// exit;
			$this->ajaxReturn( $data );
		}
		public function get_month_apdata(){
			if(!isset($_GET['date'])){
				$_GET['date']=time();
				}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			$start=mktime(0, 0, 0,date("m",$_GET['date']),1, date("Y",$_GET['date']));
			$end=mktime(0, 0, 0,date("m",$_GET['date'])+1,0, date("Y",$_GET['date']));
			$get_date = $_GET['date'];
			// dump($start.'-'.$end);
			// dump($data);
			$data = $this->get_apdata_data($start, $end);
			$this->ajaxReturn( $data );
		}
		public function get_apdata_data($start, $end){
			date_default_timezone_set('Asia/Taipei');
			$eip_user = $this->get_user();

			$where = "(false";
			foreach($eip_user as $key => $vo){
				$where .= " or name = '".$eip_user[$key]['name']."'";
			}
			$where .= ")";

			$data = [];
			$date_array = (int)date('d',$start) < (int)date('d',$end) ? range( (int)date('d',$start), (int)date('d',$end) ) : range(0,6); // 判斷是月或是週

			foreach ($date_array as $k => $v) {			
				$get_date =	mktime(0, 0, 0, date("m",$start), date("d",$start)+$k, date("Y",$start));
				$get_date_end = mktime(0, 0, 0, date("m",$start), date("d",$start)+$k+1, date("Y",$start));
				$day = date("d", $get_date);

				// dump($eip_user);
				$user_data = [];
				foreach($eip_user as $ukey =>$uvo){
					$sql = "SELECT `chattype2`, `crm_chats`.`cumid`, `crm_crm`.`typeid`
							FROM `crm_chats` 
							JOIN `crm_crm` ON `crm_crm`.`id` = `crm_chats`.`cumid`
							WHERE `appmdate` >= ".$get_date." and `appmdate` < ".$get_date_end." and `eid` = '".$uvo['id']."'";       
					$record=D()->query($sql);
					// dump($record);

					$salesrecord = [];

					//目標
					$aimarray = [
						['type'=>'致電', 'aim_db' => 'anay_apdata'],
						['type'=>'面談', 'aim_db' => 'anay_apdata_out'],
					];
					foreach($aimarray as $key=>$v){
						$dayofweek = date('w', $get_date);
						if($dayofweek == '0' || $dayofweek == '6'){
							$salesrecord[$v['type']]['aim'] = 0;
						}else{
							$weekaim = D($v['aim_db'])->where('user_id = '.$uvo['id'])->order('name')->select();
							$weekaim = $this->getweek_num($weekaim, $get_date);
							$salesrecord[$v['type']]['aim'] =$weekaim[0]['num']/5.0;
						}
					}

					//績效
					foreach($record as $key=>$v){
						$crm_name = CustoHelper::get_crm_show_name($v['cumid']);
						//訪談方式
						if($v['chattype2'] == 0 ){
							$salesrecord['面談']['count'] += 1;
							$salesrecord['面談']['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');
						}else{
							$salesrecord['致電']['count'] += 1;
							$salesrecord['致電']['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');
						}
					}

					//合併
					$user_data[$uvo['id']] =$salesrecord;
				}
			    array_push($data, ['date'=>$day ,'user_data'=>$user_data]);
			}
			// dump($data);
			$return_data['record_data'] = $data;
			$return_data['eip_user'] = $eip_user;
			// exit;
			return $return_data;
		}


		// 簽約紀錄
		public function pedata(){
			$this->assign("tab",$_SESSION['anay_tab']);
			$this->assign("today",date("Y-m-d",time()));
			$this->display();
		}
		public function get_week_pedata(){
			if(!isset($_GET['id']))$_GET['id']="%";
			if(!isset($_GET['qulid']))$_GET['qulid']="%";

			if(!isset($_GET['date'])){
				$_GET['date']=time();
			}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			//製作日期陣列
			for($i=0;$i<7;$i++){
				$mon[$i]=date("Y-m-d",mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+$i+1, date("Y",$_GET['date'])));
			}
			// dump($mon);

			$start=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+1, date("Y",$_GET['date']));
			$end=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+7, date("Y",$_GET['date']));
			$data = $this->get_pedata_data($start, $end);
			$data['mon'] = $mon;
			// dump($start.'-'.$end);
			// dump($data['record_data']);
			$this->ajaxReturn( $data );
		}
		public function get_month_pedata(){
			if(!isset($_GET['date'])){
				$_GET['date']=time();
				}else{
				$_GET['date']=strtotime($_GET['date']);
			}

			$start=mktime(0, 0, 0,date("m",$_GET['date']),1, date("Y",$_GET['date']));
			$end=mktime(0, 0, 0,date("m",$_GET['date'])+1,0, date("Y",$_GET['date']));
			$get_date = $_GET['date'];
			// dump($start.'-'.$end);
			// dump($data);
			$data = $this->get_pedata_data($start, $end);
			$this->ajaxReturn( $data );
		}
		public function get_pedata_data($start, $end){
			date_default_timezone_set('Asia/Taipei');
			$eip_user = $this->get_user();
			
			$where = "(false";
			foreach($eip_user as $key => $vo){
				$where .= " or name = '".$eip_user[$key]['name']."'";
			}
			$where .= ")";

			$data = [];
			$date_array = (int)date('d',$start) < (int)date('d',$end) ? range( (int)date('d',$start), (int)date('d',$end) ) : range(0,6); // 判斷是月或是週

			foreach ($date_array as $k => $v) {
				// dump($eip_user);
				
				$get_date =	mktime(0, 0, 0, date("m",$start), date("d",$start)+$k, date("Y",$start));
				$get_date_end = mktime(0, 0, 0, date("m",$start), date("d",$start)+$k+1, date("Y",$start));
				$day = date("d", $get_date);

				$user_data = [];
				foreach($eip_user as $ukey =>$uvo){
					$sql = "SELECT c.* , `crm_crm`.`id` as cumid, `crm_crm`.`typeid` FROM `crm_contract` c 
							JOIN `crm_crm` ON `crm_crm`.`id` = `c`.`cid`
							WHERE `eid` ='{$uvo['id']}' and `cdate` >= ".$get_date." and `cdate` < ".$get_date_end." and `eid` = '".$uvo['id']."'";    
    
					$record=D()->query($sql);
					// dump($record);

					$salesrecord = [];

					//目標
					$aimarray = [
						['type'=>'簽約金額', 'aim_db' => 'anay_pedata'],
					];
					foreach($aimarray as $key=>$v){
						$dayofweek = date('w', $get_date);
						if($dayofweek == '0' || $dayofweek == '6'){
							$salesrecord[$v['type']]['aim'] = 0;
						}else{
							$weekaim = D($v['aim_db'])->where('user_id = '.$uvo['id'])->order('name')->select();
							$weekaim = $this->getweek_num($weekaim, $get_date);
							$salesrecord[$v['type']]['aim'] =$weekaim[0]['num']/5.0;
						}
					}

					//績效
					foreach($record as $key=>$v){
						$crm_name = CustoHelper::get_crm_show_name($v['cumid']);
						$salesrecord['簽約金額']['count'] += (int)$v["allmoney"];
						$salesrecord['簽約金額']['crm_name'] .= ($crm_name.':'.$v['cumid'].'@'.$v['typeid'].',');
					}

					//合併
					$user_data[$uvo['id']] =$salesrecord;
				}
			    array_push($data, ['date'=>$day ,'user_data'=>$user_data]);
			}
			// dump($data);
			$return_data['record_data'] = $data;
			$return_data['eip_user'] = $eip_user;
			// exit;
			return $return_data;
		}

		public function get_user(){
			$acc = parent::get_my_access(); // 取得自己的權限

			$teams = D("eip_team")->field("childeid")->where("status=1 and boss_id=".$_SESSION['eid'])->select();
			if(count($teams)>0){
				$team_member = [];
				foreach ($teams as $k => $v) {
					array_push($team_member, str_replace('"', '', $v['childeid']));
				}
				$team_member = str_replace("、", ",",join("、", $team_member));
				$where_team_member = " ( id in (".$team_member.") or id=".$_SESSION['eid'].")";
			}else{
				$where_team_member = "id=".$_SESSION['eid'];
			}
			if($_SESSION['teamid']== self::$top_teamid || $acc['anay_all'] == 1){
				$eip_user=D("eip_user")->field("id,name")->where("status=1 and is_job=1 and is_anysis=1")->order('name')->select();
			}else{
				$eip_user=D("eip_user")->field("id,name")->where("status=1 and is_job=1 and is_anysis=1 and ".$where_team_member)->order('name')->select();
			}

			return $eip_user;
		}

		public function check_anay_access($acc_type,  $acc_method, $ids=[], $teamid=0, $target_table='eip_user'){
			parent::check_has_access($acc_type, $acc_method); /*檢查是否有設定此權限*/

			// dump($ids);exit;
			if($ids!=[]){ /*須依根據處理對象檢查權限*/
			}
		}

	}

?>																	