<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\Common;
	use Photonic\CustoHelper;
	use Photonic\MensHelper;
	use Trade\Controller\FigController;
	use Trade\Controller\CommonmenuController;

	class IndexController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			$this->assign('company', '傳訊光商用EIP');
			$this->assign('ADM', session('userName'));
			$this->todnum=0;

			if(self::$system_parameter['協同人員']){
				$this->crm_in = "( crm.did in(".session('adminId').") or 
							   crm.wid in(".session('adminId').") or 
							   crm.sid in(".session('adminId').") or 
							   crm.hid1 in(".session('adminId').") or 
							   crm.hid2 in(".session('adminId').") or 
							   crm.hid3 in(".session('adminId').") or
							   crm.id = ". self::$our_company_id ."
							)";
			}
			else{
				$this->crm_in = " true ";
			}
		} 

		public function index(){
			$time_interval = [];
			$this->assign("NOTIFICATION_PUBKEY", C('NOTIFICATION_PUBKEY'));

			$y=date("Y");
			$m=date("m");
			$start = strtotime(date("Y-m")."-" . "01");//这个月的第一天
			$end = strtotime((($m<12)?($y):($y+1))."-".(($m<12)?($m+1):(1))."-" . "01 -1 days");//这个月的最后一天
			$this->assign("start", $start);
			$this->assign("end", $end);
			$this->assign("time", time());
			$this->assign("date", date('Y-m-d'));
			$this->assign("cyear", date("Y")-1911);

			/*取得月曆*/
			$time_start  = microtime(true);
			$return_data = $this->get_calendar($y, $m, $start, $end, $current_data=date("Y-m-d"));
			// dump($return_data);
			$this->assign("day", $return_data['day']);

			$this->assign("record", $return_data['record']);

			$this->assign("todlis1", $return_data['todlis1']);
			$this->assign("todlis3", $return_data['todlis3']);
			$time_end = microtime(true);
			$time_interval['get_calendar'] = $time_end - $time_start;
		
			//事件簿
			$time_start = microtime(true);
			$FigController = new FigController();
			$_GET['user'] = session('adminId');
			$events = $FigController->get_index_events();
			// dump($events);
			$this->assign("events", $events);
			$time_end = microtime(true);
			$time_interval['get_events'] = $time_end - $time_start;

			//小事件
			$time_start = microtime(true);
			$post_data_smallthings['doevt']="0";
			$post_data_smallthings['doid']="mycrm";
			$post_data_smallthings['page']='index';
			$todlis2 = CustoHelper::get_smallthings($post_data_smallthings, $countOfPage=0)['smallthings'];
			// dump($todlis2);exit;
			$this->assign("todlis2", $todlis2);
			$todlis2_group = [ "全部"=>[] ];
			$todlis2_group_names = ["全部"];
			foreach ($todlis2 as $key => $value) {
				$group_name = $value['douser_name'] ? $value['douser_name'] : "無";
				if( !isset($todlis2_group[$group_name]) ){ 
					$todlis2_group[$group_name] = [];
					array_push($todlis2_group_names, $group_name);
				}
				array_push($todlis2_group["全部"], $value);
				array_push($todlis2_group[$group_name], $value);
			}
			// dump($todlis2_group);
			// dump($todlis2_group_names);exit;
			$this->assign("todlis2_group", $todlis2_group);
			$this->assign("todlis2_group_names", $todlis2_group_names);
			$time_end = microtime(true);
			$time_interval['get_smallthings'] = $time_end - $time_start;

			//今日聯絡列表
			// $post_data_conversation['conversation_type'] = '=7';
			// $today_chats = CustoHelper::get_conversation($post_data_conversation)['cum_list'];
			// dump($today_chats);exit;
			// $this->assign("today_chats", $today_chats);

			//常用選單
			$time_start = microtime(true);
			$commonmenu = CommonmenuController::get_common_menu_with_link($read_count=true);
			$this->assign("commonmenu", $commonmenu);

			$this->assign('page_title', '首頁');
			$this->assign('page_model', self::$eip_company['eip_name']);
			$time_end = microtime(true);
			$time_interval['get_common_menu'] = $time_end - $time_start;

			// 是否需要打卡按鈕
			$time_start = microtime(true);
			$params = [
				'id'=>session('eid'),
				'pay_type' => 1,
			];
			$my_salary_is_month_pay = count(MensHelper::get_mens_with_salary_record($params)) > 0;
			$this->assign('my_salary_is_month_pay', $my_salary_is_month_pay);
			$time_end = microtime(true);
			$time_interval['get_my_salary_is_month_pay'] = $time_end - $time_start;

			$this->assign('time_interval', json_encode($time_interval, JSON_UNESCAPED_UNICODE));
			$this->display();
		}

		/*月曆換月*/
		public function month_select(){
			$date = mktime(0, 0, 0, $_POST['month']+$_POST['action'], 1, $_POST['year']);
			// dump($date);exit;
			$y = date("Y", $date);
			$m = date("m", $date);
			$start = $date;  // 这个月的第一天
			$end = strtotime((($m<12)?($y):($y+1))."-".(($m<12)?($m+1):(1))."-" . "01 -1 days");  // 这个月的最后一天
			$this->assign("start", $start);
			$this->assign("end", $end);
			$this->assign("time", $date);
			$this->assign("date", date('Y-m-d', $date));
			$this->assign("cyear", date("Y", $date)-1911);

			$return_data = $this->get_calendar($y, $m, $start, $end, $current_data=date('Y-m-d', $date));
			// dump($return_data);
			$this->assign("day", $return_data['day']);

			$this->assign("record", $return_data['record']);

			$this->assign("todlis1", $return_data['todlis1']);
			$this->assign("todlis3", $return_data['todlis3']);

			$acc = parent::check_all_access('crm'); //檢查看全部or指定瀏覽的 2019/10/16 fatfat
			
			$this->display('calendar');
		}
		/*月曆換日*/
		public function date_select(){
			$date = $_POST['action'] == 'click' ? strtotime($_POST['month']."-" . $_POST['date']) : strtotime($_POST['date']) + $_POST['action'] * 86400;
			// dump($date);exit;
			$y = date("Y", $date);
			$m = date("m", $date);
			$start = strtotime(date("Y-m",$date)."-" . "01");  // 这个月的第一天
			$end = strtotime((($m<12)?($y):($y+1))."-".(($m<12)?($m+1):(1))."-" . "01 -1 days");  // 这个月的最后一天
			$this->assign("start", $start);
			$this->assign("end", $end);
			$this->assign("time", $date);
			$this->assign("date", date('Y-m-d', $date));
			$this->assign("cyear", date("Y", $date)-1911);

			$return_data = $this->get_calendar($y, $m, $start, $end, $current_data=date('Y-m-d', $date));
			// dump($return_data);
			$this->assign("day", $return_data['day']);

			$this->assign("record", $return_data['record']);

			$this->assign("todlis1", $return_data['todlis1']);
			$this->assign("todlis3", $return_data['todlis3']);

			$acc = parent::check_all_access('crm'); //檢查看全部or指定瀏覽的 2019/10/16 fatfat
			
			$this->display('calendar');
		}
		/*產生月曆相關資料*/
		public function get_calendar($y, $m, $start, $end, $current_data){
			$acc = parent::check_all_access('crm'); //檢查看全部or指定瀏覽的 2019/10/16 fatfat
			//dump($acc);

			$todlis1 = []; // 預約客戶
			$todlis3 = [];  // 輸入活動
			
			$weekday = date('w', $start);  //星期幾

			$prestart = strtotime(date("Y-m", $start) . "-" . "01 -" . $weekday . " days"); //月曆查詢月份中 第一個星期天的日期(可計算至前月)
			$nextstart = strtotime((($m<12)?($y):($y+1))."-".(($m<12)?($m+1):(1))."-01"); //下個月的第一天

			/*查詢月份的1號前的前置空白*/
			for($i=date('d',$prestart); $i < date('d',$prestart)+$weekday; $i++){
				$word=date('Y')."-".date('m',$prestart)."-".str_pad($i, 2, "0", STR_PAD_LEFT);
				$day[$word]['day']=$i;
				$day[$word]['delay']=0;
				$day[$word]['imp']=0;
				$day[$word]['nor']=0;
				$day[$word]['class']="not_this_month";
				$day[$word]['key']=$word;
			}
			/*處理查詢月份*/
			for($i=1; $i<=date("d",$end); $i++){
				$word=date('Y',$end)."-".date('m',$end)."-".str_pad($i, 2, "0", STR_PAD_LEFT);
				$day[$word]['day']=$i;
				$day[$word]['delay']=0;
				$day[$word]['imp']=0;
				$day[$word]['nor']=0;
				$day[$word]['key']=$word;
				if( date("w",strtotime(date("Y-m-",$end).str_pad($i, 2, "0", STR_PAD_LEFT)))==0 || 
					date("w",strtotime(date("Y-m-",$end).str_pad($i, 2, "0", STR_PAD_LEFT)))==6
				){
					$day[$word]['class']="holiday";
				}
			}
			
			//客戶預約
			if($acc["crm_all"]=='1' || session("teamid") == self::$top_teamid){	
				$in=" true ";			
			}else{
				$in = $this->crm_in;
			}
			$record = D('crm_chats chats')
							->field('crm.id,
									 chats.appmdate, chats.color_class, chats.chattype2,
									 user.name as user_name')
							->join("left join crm_crm as crm on crm.id = chats.cumid")
							->join("left join eip_user as user on user.id = chats.eid")
							->where($in." and 
									`appmdate`!='0' and 
									`appmdate` >= ".strtotime(date("Y-m",$end)."-" . "01")." and `appmdate` < ".$nextstart)
							->order("chats.appmdate asc, chats.id desc")
							->select();
			foreach($record as $rKey=>$rValue){
				$appmdate_date = date('Y-m-d',$rValue['appmdate']);
				$record[$rKey]['time'] = $appmdate_date;
				$record[$rKey]['time_hi'] = date('H:i', $rValue['appmdate']);

				$day[$appmdate_date]['delay']++;
				$day[$appmdate_date]['tip']['delay'] .= "<span class='cursor_pointer ".$rValue['color_class']."' onclick='window.open(\"".U("Custo/view","id=".$rValue['id'])."\")'>";
				$day[$appmdate_date]['tip']['delay'] .= $rValue['user_name'].":";
				$day[$appmdate_date]['tip']['delay'] .= CustoHelper::get_crm_show_name($rValue['id']);
				$day[$appmdate_date]['tip']['delay'] .= $rValue['chattype2']=='0' ? '-面' : "";
				$day[$appmdate_date]['tip']['delay'] .= "</span><br>";

				if($appmdate_date == $current_data){
					$content = CustoHelper::get_crm_show_name($rValue['id']);
					$content .= $rValue['chattype2']=='0' ? '-面' : "";
					array_push($todlis1, [
						'content'	=> date('H:i', $rValue['appmdate'])." ". $content,
						'href'		=> U("Custo/view","id=".$rValue['id']),
						'class'		=> 'delay_things',
					]);
				}
			}
			$return_data['record'] = $record;


			//輸入活動
			if(session("teamid") != self::$top_teamid){
				$in =" user_id in(".session('adminId').") ";
			}else{
				$in=" true ";
			}
			$cale_normal=D("cale_normal")->where($in." and status=1 and time between ".$prestart." and ".$nextstart)
										 ->order("time asc,id desc")->select();
			//dump($cale_normal);
			foreach($cale_normal as $key=>$vo){
				$day[date("Y-m-d",$vo['time'])]['nor']++;
				$day[date("Y-m-d",$vo['time'])]['tip']['nor'].=$vo['user_name'].":".$vo['content']."<br>";
				// $day[date("Y-m-d",$vo['time'])]['tip']['nor'].=$vo['user_name']."：".date("H:i",$vo['time'])." ".$vo['content']."<br>";
				$nornum++;
				if(date("Y-m-d",$vo['time']) == $current_data){
					$push_data['content'] = date("H:i",$vo['time']) . " " . $vo['content'];
					$push_data['count'] = mb_strlen($push_data['content'],"utf-8");
					$push_data['class'] = 'nor_things';
					$push_data['id'] = $vo['id'];
					$push_data['time'] = $vo['time'];
					$push_data['mcontent'] = $vo['content'];
					$push_data['frequency'] = $vo['frequency'];
					$push_data['eid'] = $vo['eid'];
					array_push($todlis3, $push_data);

					$increase = M("cale_normal")->where($in." and status=1 and eid = '".$vo['eid']."'")->select();	
					$count_eid = count($increase);
					$add = $increase;
					$id_count = 0;
					foreach($increase as $key1 => $vo1){
						if($vo1['id'] > $vo['id'])
							$id_count++;
					}
					$add[$count_eid] = $increase[$count_eid - 1];
					unset($add[$count_eid]['id']);
					switch($increase[$count_eid -1 ]['frequency']){
						case 1:
							unset($add[$count_eid]);
							break;
						case 2:
							if($id_count == 60)
								$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400;
							else
								unset($add[$count_eid]);
							break;
						case 3:
							if($id_count == 8)
								$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 7;
							else
								unset($add[$count_eid]);
							break;
						case 4:
							if($id_count == 1){
								switch((int)date("m",$increase[$count_eid - 1]['time'])){
									case 1:
										if((int)(date("Y",$increase[$count_eid - 1]['time']) % 4 == 0)){
											if((int)(date("d",$increase[$count_eid - 1]['time'])) > 29)
												$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 60;
											else
												$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										}
										else{
											if((int)(date("d",$increase[$count_eid - 1]['time'])) > 28)
												$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 59;
											else
												$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										}
										break;
									case 2:
										if((int)(date("Y",$increase[$count_eid - 1]['time'])) % 4 == 0)
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 29;
										else
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 28;
										break;
									case 3:
										if((int)(date("d",$increase[$count_eid - 1]['time'])) >30)
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 61;
										else
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										break;
									case 4:
										$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 30;
										break;
									case 5:
										if((int)(date("d",$increase[$count_eid - 1]['time'])) >30)
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 61;
										else
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										break;
									case 6:
										$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 30;
										break;
									case 7:
										$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										break;
									case 8:
										if((int)(date("d",$increase[$count_eid - 1]['time'])) >30)
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 61;
										else
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										break;
									case 9:
										$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 30;
										break;
									case 10:
										if((int)(date("d",$increase[$count_eid - 1]['time'])) >30)
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 61;
										else
											$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										break;
									case 11:
										$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 30;
										break;
									case 12:
										$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 31;
										break;
								}
							}
							else
								unset($add[$count_eid]);
							break;
						case 5:
							if($id_count == 0){
								if((int)(date("Y",$increase[$count_eid - 1]['time']) % 4 == 0))
									$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 366;
								else
									$add[$count_eid]['time'] = $increase[$count_eid - 1]['time'] + 86400 * 365;
							}
							else
								unset($add[$count_eid]);
							break;
					}
					if($add[$count_eid ] != null){
						D("cale_normal")->data($add[$count_eid])->add();
					}
				}
			}
	
			$return_data['todlis1'] = $todlis1;  // 回傳 預約紀錄
			$return_data['todlis3'] = $todlis3;  // 回傳 輸入活動

			$return_data['day'] = $day;	 // 回傳 月曆日期(含預約、活動)
			
			return $return_data;
		}

		public function contact_list(){
			//今日聯絡列表
			$post_data['conversation_type'] = isset($_POST['conversation_type']) ? $_POST['conversation_type']: '=7';
			$today_chats = CustoHelper::get_conversation($post_data)['cum_list'];
			// dump($today_chats);
			$this->assign("today_chats", $today_chats);
			$this->display();
		}

		public function login(){
			$this->display();
		}

		/*新增/編輯輸入活動*/
		public function aj_addthing(){
			if($_POST['content']!='' && $_POST['time']!=''){
				$_POST['time'] = str_replace('T', ' ', $_POST['time']);
				// dump($_POST['time']);

				if($_POST['id']=='0'){
					$id = M("cale_normal")->field("eid")->select();
					$count = count($id);
					$_POST['eid'] = $id[$count-1]['eid']+1;
					unset($_POST['id']);
					
					$_POST['time']=strtotime($_POST['time']);
					// dump($_POST['time']);exit;
					$time = $_POST['time'];
					$_POST['user_id']=session("adminId");
					$_POST['user_name']=session("userName");
					
					switch($_POST['frequency']){
						case 1:
							$count = 0;
							if(D("cale_normal")->data($_POST)->add()){
								$this->success("更新成功");
								exit;
							}
							break;
						case 2:
							$count = 0;
							for($i = 0;$i <= 60;$i++){
								$_POST['time'] = $time + $i * 86400;
								if(D("cale_normal")->data($_POST)->add()){
									$count++;
								}
							}
							if($count == 61){
								$this->success("更新成功");
								exit;
							}
							break;
						case 3:
							$count = 0;
							for($i = 0;$i <= 8;$i++){
								$_POST['time'] = $time + $i * 86400 * 7;
								if(D("cale_normal")->data($_POST)->add()){
									$count++;
								}
							}
							if($count == 9){
								$this->success("更新成功");
								exit;
							}
							break;
						case 4:
							$count = 0;
							for($i = 0;$i <= 1;$i++){
								switch((int)date("m",$_POST['time'])){
									case 1:
										if((int)(date("Y",$_POST['time'])) % 4 == 0){
											if((int)(date("d",$_POST['time'])) > 29)
												$_POST['time'] = $time + $i * 86400 * 60;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
										}
										else{
											if((int)(date("d",$_POST['time'])) > 28)
												$_POST['time'] = $time + $i * 86400 * 59;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
										}
										break;
									case 2:
										if((int)(date("Y",$_POST['time'])) % 4 == 0)
											$_POST['time'] = $time + $i * 86400 * 29;
										else
											$_POST['time'] = $time + $i * 86400 * 28;
										break;
									case 3:
										if((int)(date("d",$_POST['time'])) >30)
											$_POST['time'] = $time + $i * 86400 * 61;
										else
											$_POST['time'] = $time + $i * 86400 * 31;
										break;
										break;
									case 4:
										$_POST['time'] = $time + $i * 86400 * 30;
										break;
									case 5:
										if((int)(date("d",$_POST['time'])) >30)
											$_POST['time'] = $time + $i * 86400 * 61;
										else
											$_POST['time'] = $time + $i * 86400 * 31;
										break;
										break;
									case 6:
										$_POST['time'] = $time + $i * 86400 * 30;
										break;
									case 7:
										$_POST['time'] = $time + $i * 86400 * 31;
										break;
									case 8:
										if((int)(date("d",$_POST['time'])) >30)
											$_POST['time'] = $time + $i * 86400 * 61;
										else
											$_POST['time'] = $time + $i * 86400 * 31;
										break;
									case 9:
										$_POST['time'] = $time + $i * 86400 * 30;
										break;
									case 10:
										if((int)(date("d",$_POST['time'])) >30)
											$_POST['time'] = $time + $i * 86400 * 61;
										else
											$_POST['time'] = $time + $i * 86400 * 31;
										break;
									case 11:
										$_POST['time'] = $time + $i * 86400 * 30;
										break;
									case 12:
										$_POST['time'] = $time + $i * 86400 * 31;
										break;
								}
								if(D("cale_normal")->data($_POST)->add()){
									$count++;
								}
							}
							if($count == 2){
								$this->success("更新成功");
								exit;
							}
							break;
						case 5:
							$count = 0;
							/*if((int)(date("Y",$_POST['time'])) % 4 == 0)
								$_POST['time'] += 86400 * 366;
							else
								$_POST['time'] += 86400 * 365;*/
							if(D("cale_normal")->data($_POST)->add()){
								$this->success("更新成功");
								exit;
							}
							break;
					}
				}else{
					if($_POST['eid'] == 0){
						$id = $_POST['id'];
						unset($_POST['id']);
						$_POST['time']=strtotime($_POST['time']);
						$time = $_POST['time'];
						switch($_POST['frequency']){
							case 1:
								$count = 0;
								if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
									$this->success("更新成功");
									exit;
								}
								break;
							case 2:
								$count = 0;
								for($i = 0;$i <= 60;$i++){
									$_POST['time'] = $time + $i * 86400;
									if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
										$count++;
									}
								}
								if($count == 61){
									$this->success("更新成功");
									exit;
								}
								break;
							case 3:
								$count = 0;
								for($i = 0;$i <= 8;$i++){
									$_POST['time'] = $time + $i * 86400 * 7;
									if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
										$count++;
									}
								}
								if($count == 9){
									$this->success("更新成功");
									exit;
								}
								break;
							case 4:
								$count = 0;
								for($i = 0;$i <= 1;$i++){
									switch((int)date("m",$_POST['time'])){
										case 1:
											if((int)(date("Y",$_POST['time'])) % 4 == 0){
												if((int)(date("d",$_POST['time'])) > 29)
													$_POST['time'] = $time + $i * 86400 * 60;
												else
													$_POST['time'] = $time + $i * 86400 * 31;
											}
											else{
												if((int)(date("d",$_POST['time'])) > 28)
													$_POST['time'] = $time + $i * 86400 * 59;
												else
													$_POST['time'] = $time + $i * 86400 * 31;
											}
											break;
										case 2:
											if((int)(date("Y",$_POST['time'])) % 4 == 0)
												$_POST['time'] = $time + $i * 86400 * 29;
											else
												$_POST['time'] = $time + $i * 86400 * 28;
											break;
										case 3:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 4:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 5:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 6:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 7:
											$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 8:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 9:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 10:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 11:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 12:
											$_POST['time'] = $time + $i * 86400 * 31;
											break;
									}
									if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
										$count++;
									}
								}
								if($count == 2){
									$this->success("更新成功");
									exit;
								}
								break;
							case 5:
								/*if((int)(date("Y",$_POST['time'])) % 4 == 0)
									$_POST['time'] += 86400 * 366;
								else
									$_POST['time'] += 86400 * 365;*/
								$count = 0;
								if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
									$this->success("更新成功");
									exit;
								}
							break;
						}
					}
					else{
						$eid_count = 0;
						$_POST['time']=strtotime($_POST['time']);
						$time = $_POST['time'];
						$cale_normal = M("cale_normal")->where("eid = '".$_POST['eid']."'")->select();
						foreach($cale_normal as $key => $vo){
							if($cale_normal[$key]['time'] >= $_POST['time']){
								$id = $cale_normal[$key]['id'];
								break;
							}
						}
						foreach($cale_normal as $key => $vo){
							if($cale_normal[$key]['time'] >= $_POST['time']){
								$eid_count++;
							}
						}
						$id_plus = $id;
						unset($_POST['id']);
						unset($_POST['frequency']);
						
						switch($cale_normal[0]['frequency']){
							case 1:
								$count = 0;
								if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
									$eid_count = 0;
									$this->success("更新成功");
									exit;
								}
								break;
							case 2:
								$count = 0;
								for($i = 0;$i < $eid_count;$i++){
									$_POST['time'] = $time + $i * 86400;
									$id = $id_plus + $i;
									if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
										$count++;
									}
								}
								if($count == $eid_count){
									$eid_count = 0;
									$this->success("更新成功");
									exit;
								}
								break;
							case 3:
								$count = 0;
								for($i = 0;$i < $eid_count;$i++){
									$_POST['time'] = $time + $i * 86400 * 7;
									$id = $id_plus + $i;
									if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
										$count++;
									}
								}
								if($count == $eid_count){
									$eid_count = 0;
									$this->success("更新成功");
									exit;
								}
								break;
							case 4:
								$count = 0;
								for($i = 0;$i < $eid_count;$i++){
									switch((int)date("m",$_POST['time'])){
										case 1:
											if((int)(date("Y",$_POST['time'])) % 4 == 0){
												if((int)(date("d",$_POST['time'])) > 29)
													$_POST['time'] = $time + $i * 86400 * 60;
												else
													$_POST['time'] = $time + $i * 86400 * 31;
											}
											else{
												if((int)(date("d",$_POST['time'])) > 28)
													$_POST['time'] = $time + $i * 86400 * 59;
												else
													$_POST['time'] = $time + $i * 86400 * 31;
											}
											break;
										case 2:
											if((int)(date("Y",$_POST['time'])) % 4 == 0)
												$_POST['time'] = $time + $i * 86400 * 29;
											else
												$_POST['time'] = $time + $i * 86400 * 28;
											break;
										case 3:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 4:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 5:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 6:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 7:
											$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 8:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 9:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 10:
											if((int)(date("d",$_POST['time'])) >30)
												$_POST['time'] = $time + $i * 86400 * 61;
											else
												$_POST['time'] = $time + $i * 86400 * 31;
											break;
										case 11:
											$_POST['time'] = $time + $i * 86400 * 30;
											break;
										case 12:
											$_POST['time'] = $time + $i * 86400 * 31;
											break;
									}
									$id = $id_plus + $i;
									if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
										$count++;
									}
								}
								if($count == $eid_count){
									$eid_count = 0;
									$this->success("更新成功");
									exit;
								}
								break;
							case 5:
								$count = 0;
								/*if((int)(date("Y",$_POST['time'])) % 4 == 0)
									$_POST['time'] += 86400 * 366;
								else
									$_POST['time'] += 86400 * 365;*/
								if(D("cale_normal")->data($_POST)->where("id=".$id)->save()){
									$eid_count = 0;
									$this->success("更新成功");
									exit;
								}
							break;
						}
					}
				}
			}
			$this->error("新增失敗");
		}
		/*刪除輸入活動*/
		public function aj_delcale(){
			if($_POST['id']!=''){
				$data['status']=0;
				D("cale_normal")->where("id=".$_POST['id'])->data($data)->save();

				$this->success("刪除成功");
			}
			$this->error("請選擇刪除對象");
		}


		/*設定訂閱推播通知*/
		public function subscripe(){
			$subscription = json_decode($_POST['newSub'], true);
			// dump($subscription);

			if (!isset($subscription['endpoint'])) {
			    echo 'Error: not a subscription';
			    return;
			}

			$method = $_SERVER['REQUEST_METHOD'];

			$user_id = session('adminId') ? session('adminId') : 0;
			$data = [
				'user_id'			=> $user_id,
	    		'endpoint'			=> $subscription['endpoint'],
	    		'expirationTime'	=> $subscription['expirationTime'],
	    		'auth'				=> $subscription['keys']['auth'],
	    		'p256dh'			=> $subscription['keys']['p256dh'],
	    	];
			switch ($method) {
			    case 'POST' :
			    	$subscription = D('subscription')->where('endpoint="'.$data['endpoint'].'"')->select();
			    	if($subscription){
			    		// update the key and token of subscription corresponding to the endpoint
				    	if($user_id==0) unset($data['user_id']);
				    	D('subscription')->where('endpoint="'.$data['endpoint'].'"')->data($data)->save();
			    	}else{
			        	// create a new subscription entry in your database (endpoint is unique)
			    		$result = D('subscription')->data($data)->add();
						$this->success('訂閱操作成功');
			    	}
			        break;
			    case 'DELETE':
			        // delete the subscription corresponding to the endpoint
					$this->success('訂閱刪除成功');
			        break;
			    default:
					$this->error('Error: method not handled');
			        return;
			}
		}
		/*測試推播通知*/
		public function test_web_push(){
			$payload = [
	            'title' => '推播測試',
	            'msg' => "測試內容測試內容",
	            'open_url' => 'https://'.$_SERVER['HTTP_HOST'].'/index.php',
	        ];
			$result = Common::send_notification_to_user(146, $payload);
			dump($result);
		}
	}
?>		