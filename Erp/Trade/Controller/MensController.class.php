<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\MensHelper;
	use Photonic\AttendanceHelper;
	use Photonic\ScheduleDetailHelper;
	use Photonic\Common;
	use Photonic\ProductHelper;

	class MensController extends GlobalController {
		
		function _initialize(){
			parent::_initialize();
			$this->assign('company', '傳訊光商用EIP');
			$this->assign('ADM', session('userName'));

			$this->assign("action",ACTION_NAME);	

			$this->assign('page_title_active', 2);  /*右上子選單active*/

			/*全部在職人員(有用於職務代理人選擇)*/
			$eip_user_options = MensHelper::get_mens_working();
			$this->assign('eip_user_options', $eip_user_options);
			/*部審核人員*/
			$eip_user_apart_options = MensHelper::get_mens_working([
				'apartmentid="'.session('apartId').'"'
			]);
			$this->assign('eip_user_apart_options', $eip_user_apart_options);
			/*公司審核人員*/
			$eip_top_examiner_options = MensHelper::get_mens_rest_top_examiner();
			$this->assign('eip_top_examiner_options', $eip_top_examiner_options);

			parent::index_set('rest_type'); /*假種*/

			parent::check_has_access(CONTROLLER_NAME, 'red');
		}

		function index(){
			$this->redirect('Mens/emlist', ['is_job'=>'1']);
		}		
		function emlist(){
			if($_GET['is_job']=="1"){
				$title=" > 現有員工";
			}elseif($_GET['is_job']=="0"){
				$title=" > 離職員工";
			}elseif($_GET['is_job']=="2"){
				$title=" > 留職停薪員工";
			}elseif($_GET['no']=="資" || $_GET['right']=="3"){
				$title=" > 資料區";
			}elseif($_GET['status']=="0"){
				$title=" > 垃圾桶";
			}else{
				$title="";
			}
			$this->assign('page_title', '人事管理'.$title);

			$params = [];
			foreach($_POST as $key=>$vo){
				$params[$key] = I($key);
				$this->assign($key.'_input', $params[$key]);
			}
			$ser = '';
			foreach($_GET as $key=>$vo){
				$params[$key] = $vo;
				$ser="{$key}={$vo}";
				$this->assign($key, $vo);
			}
			$this->assign('ser', $ser);
			$emlist = MensHelper::get_mens([], $params);
			
			$this->assign('emlist',$emlist);

			//部門清單
			parent::index_set('eip_apart');
			//職稱清單
			parent::index_set('eip_jobs');
			
			parent::index_set('access', 'true');
			parent::index_set('eip_user_right_type', 'id!=0');
			$this->assign('cid', session('cid'));

			$this->display();
		}
		function export(){
			if($_GET['is_job']=="1"){
				$title="現有員工";
			}elseif($_GET['is_job']=="0"){
				$title="離職員工";
			}elseif($_GET['is_job']=="2"){
				$title="留職停薪員工";
			}elseif($_GET['no']=="資"){
				$title="資料區";
			}elseif($_GET['status']=="0"){
				$title="垃圾桶";
			}else{
				$title="";
			}

			$params = [];
			foreach($_POST as $key=>$vo){
				$params[$key] = I($key);
			}
			$ser = '';
			foreach($_GET as $key=>$vo){
				$params[$key] = $vo;
			}
			$emlist = MensHelper::get_mens([], $params);
			// dump($emlist);exit;

			//部門清單
			$eip_apart = parent::index_set('eip_apart');
			//職稱清單
			$eip_jobs = parent::index_set('eip_jobs');

			$access = parent::index_set('access', 'true');

			$export_title=array(
				"編號",
				"部門",
				"職稱",
				"權限管理",
				"姓名",
				"別稱",
				"電話",
				"手機",
				"系統通知mail",
				"公司mail",
				"生日",
				"到職日",
				"現居地",
				"戶籍地",
				"身份証",
				"公司分機",
				"備註",
				"戶名",
				"銀行+代碼",
				"分行",
				"帳號",
				"聯絡人",
				"聯絡人關係",
				"聯絡人手機",
				"聯絡人備註",
			);
			$export_data = [];
			foreach($emlist as $key=>$v){
				array_push($export_data, [
					"編號" => $v['no'],
					"部門" => $eip_apart[$v['usergroupid']]['name'],
					"職稱" => $eip_jobs[$v['usergroupid']]['name'],
					"權限管理" => $access[$v['usergroupid']]['name'],
					"姓名" => $v['name'],
					"別稱" => $v['ename'],
					"電話" => $v['phone'],
					"手機" => $v['mphone'],
					"系統通知mail" => $v['email'],
					"公司mail" => $v['email2'],
					"生日" => $v['birthday'],
					"到職日" => $v['dutday'],
					"現居地" => $v['addr'],
					"戶籍地" => $v['resaddr'],
					"身份証" => Common::encrypt($v['idno'], 'D'),
					"公司分機" => $v['extension'],
					"備註" => $v['mom'],
					"戶名" => $v['bank_account_name'],
					"銀行+代碼" => $v['bank'].$v['代碼'],
					"分行" => $v['bank_branch_name'],
					"帳號" => $v['bank_account'],
					"聯絡人" => $v['econtact'],
					"聯絡人關係" => $v['relationship'],
					"聯絡人手機" => $v['emphone'],
					"聯絡人備註" => $v['ememo'],
				]);
			}
			// dump($export_data);exit;
			$file_title = '人事管理_'.$title.'_'.date('Ymd');
			parent::DataDbOut($export_data,$export_title,$list_start="A2",$file_title);
		}
		/*批次修改*/
		function emops(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');

			if($_POST['ids']['0'] == self::$top_adminid){
				$this->error('總管理帳號狀態不可更改');
			}
			$is_job = I('is_job');
			$select = I('ops');
						
			$ids = $_POST['ids'];
			if( !empty( $ids ) ) {
				/*檢查上限*/
				$eip_user_right_type = D('eip_user_right_type')->select();
				foreach($ids as $k=>$id){
					if($select==3 || ($select==1 && $is_job^1==1)){ /*還原 或 復職*/
						$uesr = D('eip_user')->where('id="'.$id.'"')->find();
						if($uesr){
							if(!isset($eip_user_right_type[$uesr['right']]['count'])){
								$eip_user_right_type[$uesr['right']]['count'] = 0;
							}
							$eip_user_right_type[$uesr['right']]['count'] += 1;
						}
					}
				}
				foreach ($eip_user_right_type as $key => $value) {
					if($value['limit_num']!=-1){
						$count = isset($value['count']) ? $value['count'] : 0;
						$eip_user_count = D('eip_user')->where('`right`="'.$value['id'].'" AND is_job=1 AND status=1')->count();
						if($eip_user_count+$count>$value['limit_num']){
							$this->error('超出員工類型上限');
						}
					}
				}

				foreach($ids as $k=>$id){
					if($select==1){ /*離職&復職*/	
						if ($is_job == 2) { // 留停復職
							D('eip_user')->data(['is_job'=>1])->where('id='.$id)->save();
						} else {
							$is_job_temp = $is_job^1==1;
							$data['is_job']=$is_job_temp;
							D('eip_user')->data($data)->where('id='.$id)->save();
						}			
						/*將客戶有設定此員工為協同人員的全部改為無*/
						// $crm_cum_pri = D("crm_cum_pri")->where("status=1")->order("orders")->select();
						// foreach($crm_cum_pri as $key=>$vo){
						// 	D('crm_crm')->data([$vo['ename']=>0])->where($vo['ename'].'='.$id)->save();
						// }
					}
					elseif($select==2){ /*垃圾桶*/
						D('eip_user')->data(['status'=>0])->where('id='.$id)->save();
					}
					elseif($select==3){ /*還原*/
						D('eip_user')->data(['status'=>1])->where('id='.$id)->save();
					}
					elseif($select==4){ /*刪除*/
						D('eip_user')->where('id='.$id)->delete();
					}
					elseif($select==5){ /*留職停薪*/
						D('eip_user')->data(['is_job'=>2])->where('id='.$id)->save();
					}
				}
			}
			parent::error_log('批次修改員工:'.json_encode($ids).', 操作選項:'.$select);
			$this->success('操作成功');
		}

		function emshow(){
			$this->assign('page_title', '人事管理');

			$acc = parent::get_my_access();
			$this->assign('acc',$acc);
			
			$id = $_GET['id'];
				
			if($acc[strtolower(CONTROLLER_NAME).'_all']==0 && $id!=session('adminId')){
				$this->error('沒有編輯權限');
			}

			$edu=array("國小","國中","高中","專科","大學","國小","研究所","博士班");
			$emdetails = D()->query('select eip_user.*,access.name accname,a.name as aname,t.name as tname,j.name as jname,d.* 
			from eip_user left join access on eip_user.usergroupid=access.id 
			left join eip_apart a  on  eip_user.apartmentid=a.id 
			left join eip_team t on eip_user.teamid=t.id 
			left join eip_jobs j on eip_user.jobid=j.id 
			left join eip_user_data d on d.eid=eip_user.id where eip_user.id='.$id)[0];
			
			$assess_positive_sum = 0; $assess_negative_sum = 0;
			$emdetails['assess_ori']=json_decode($emdetails['assess']);
			$emdetails['work_expe']=json_decode($emdetails['work_expe']);
			$emdetails['educ_expe']=json_decode($emdetails['educ_expe']);

			$emdetails['leave']=json_decode($emdetails['leave']);
			//$emdetails['leave'] = get_object_vars($emdetails['leave']);

			$emdetails['other']=json_decode($emdetails['other']);
			$emdetails['other_time']=json_decode($emdetails['other_time']);
			$other_count = count($emdetails['other_time']);

			$assess = 6;
			$emdetails['assess'] = [];
			if(count($emdetails['assess_ori'])>$assess){
				for($i=0;$i<count($emdetails['assess_ori']);$i=$i+$assess){
					for($j=0;$j<$assess;$j++){
						$emdetails['assess'][($i/$assess)][$j]=$emdetails['assess_ori'][$i+$j];
						if($j==3){ /*正評分*/
							$num = explode('+', $emdetails['assess_ori'][$i+$j]);
							if( count($num)>1 ){ $assess_positive_sum += (Int)end($num); }
						}
						if($j==4){ /*負評分*/
							$num = explode('-', $emdetails['assess_ori'][$i+$j]);
							if( count($num)>1 ){ $assess_negative_sum += (Int)end($num); }
						}
					}
				}
			}
			$this->assign('assess_positive_sum', $assess_positive_sum);
			$this->assign('assess_negative_sum', $assess_negative_sum);

			if(count($emdetails['work_expe'])>6){
				for($i=0;$i<count($emdetails['work_expe']);$i=$i+6){
					for($j=0;$j<6;$j++){
						$emdetails['work_exp'][($i/6)][$j]=$emdetails['work_expe'][$i+$j];
					}
				}
			}
			
			if(count($emdetails['educ_expe'])>5){
				for($i=0;$i<count($emdetails['educ_expe']);$i=$i+5){
					for($j=0;$j<5;$j++){
						$emdetails['educ_exp'][($i/5)][$j]=$emdetails['educ_expe'][$i+$j];
					}
				}
			}
			$emdetails['no_expe']=json_decode($emdetails['no_expe'],true);

			if (is_null($emdetails['idno']) === false)
				$emdetails['idno'] = Common::encrypt($emdetails['idno'], 'D');

			parent::index_set('eip_user');
			$this->assign('time',time());
			$this->assign('em',$emdetails);
			$this->assign('other_count',$other_count - 1);

			$kpimodel =  M("kpimodel_association as ka")
						->field("k.id, k.name, ka.time")
						->join('kpimodel as k on k.id=ka.kpimodel_id', 'left')
						->where("ka.user_id = '".$_GET['id']."'")
						->order('ka.id asc')
						->select();
			$this->assign('kpimodel',$kpimodel);

			// 取得時薪付薪紀錄
			$pay_pages = ScheduleDetailHelper::get_schedule_pay_page([
				'user_id' => $_GET['id'] ?? '0',
				'comfirm_status' => 1,
			], true);
			// dump($pay_pages);exit;
			$this->assign('pay_pages',$pay_pages); 

			// 取得薪資紀錄
			$salary = MensHelper::get_user_salary($_GET['id'] ?? '0', [
				'salary_date_s' => '',
				'salary_date_e' => '',
			]);
			$this->assign('salary_list',$salary) ; 

			$work_times = AttendanceHelper::get_work_time_options(true);
			$this->assign('work_times', $work_times);

			$this->display();
		}
		/*AJAX:取得特休累積紀錄*/
		public function get_special_rests(){
			$user_id = $_GET['user_id'] ?? 0;
			
			/*到職日*/
			$eip_user = D('eip_user')->find($user_id);
			$dutday = $eip_user ? $eip_user['dutday'] : '';
			$return_data['dutday'] = $dutday;

			/*算年資*/
			$count_seniority = MensHelper::count_seniority($dutday);
			$return_data['dut_month'] = $count_seniority['dut_month'];
			$return_data['dut_years'] = $count_seniority['dut_years'];

			/*特休累積紀錄*/
			$return_data['special_rest_accumulation'] = D('special_rest_accumulation')->where('user_id='.$user_id)->order('id desc')->select();
			
			/*算剩餘特休*/
			$special_rests_remained_hours = MensHelper::count_special_rest_remained($user_id);
			$return_data['special_rests_remained_hours'] = $special_rests_remained_hours;
			$return_data['special_rests_remained'] = round($special_rests_remained_hours / 8, 2);

			// dump($return_data);exit;
			$this->ajaxReturn($return_data);
		}

		/*AJAX:取得薪資紀錄*/
		public function get_salary_records(){
			if(parent::get_my_access()['salary_edi']==0){ /*沒匯薪列表權限*/
				$_GET['user_id'] = session('adminId'); /*只能看自己的約定薪資*/
			}
			$user_id = $_GET['user_id'] ?? 0;
			$return_data['bonus_types'] = MensHelper::get_bonus_type();
			$return_data['user_skills'] = MensHelper::get_user_skills();
			$cat_unit_data = ProductHelper::get_cat_unit(1, 1);
			$return_data['accountant_item_out'] = $cat_unit_data['layer_sub'];

			$salary_records = D('salary_records')->where('user_id='.$user_id)->order('day_s desc')->select();
			foreach ($salary_records as $key => $value) {
				$salary_records[$key]['pay_hour_format'] = number_format($value['pay_hour'], 2);
				$salary_records[$key]['pay_month_format'] = number_format($value['pay_month']);
				
				$salary_records[$key]['insurance_personal_pay_format'] = number_format($value['insurance_personal_pay']);
				$salary_records[$key]['insurance_company_pay_format'] = number_format($value['insurance_company_pay']);
				$salary_records[$key]['insurance'] = $value['insurance'] ? json_decode($value['insurance']) : [];

				$bonus = $value['bonus'] ? json_decode($value['bonus'], true) : [];
				$salary_records[$key]['bonus'] = $bonus;
				
				$skills = [];
				foreach ($return_data['user_skills'] as $skill) {
					$salary_records_skill = D('salary_records_skill')
																		->where('salary_records_id="'.$value['id'].'" AND user_skill_id="'.$skill['id'].'"')
																		->find();
					if($salary_records_skill){
						$skills[$skill['id']] = [
							'hour_pay' => $salary_records_skill['hour_pay'],
							'hour_pay_over' => $salary_records_skill['hour_pay_over'],
						];
					}
				}
				$salary_records[$key]['skills'] = (Object)$skills;
				
				$pay_month_all = $value['pay_month'];
				foreach ($bonus as $bonus_k => $bonus_v) {
					$salary_records[$key]['bonus'][$bonus_k]['num_format'] = number_format($bonus_v['num']);
					$pay_month_all += $bonus_v['num'];
				}
				$salary_records[$key]['pay_month_all'] = $pay_month_all;
				$salary_records[$key]['pay_month_all_format'] = number_format($pay_month_all);
			}
			$return_data['salary_records'] = $salary_records;

			$this->ajaxReturn($return_data);
		}
		/*AJAX:添加薪資紀錄*/
		public function add_salary_records(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');
			parent::check_has_access('salary', 'edi');

			$data = $_POST;
			// dump($data);exit;
			unset($data['id']);
			if(!$data['user_id']){ $this->error('請提供人員'); }
			if(!$data['day_s']){ $this->error('請設定開始日期'); }

			$day_s_first = substr($data['day_s'], 0, 8).'01';
			$day_s_last = substr($data['day_s'], 0, 8).'31';
			$same_month =D('salary_records')->where('user_id='.$data['user_id'].' AND day_s>="'.$day_s_first.'" AND day_s<="'.$day_s_last.'"')->find();
			if($same_month){
				$this->error('同月份已有設定薪資');
			}

			if($data['pay_type']==1){ /*新增月薪*/
				$skills = [];
				$data['pay_hour'] = 0;
				if(!is_numeric($data['pay_month'])){ $this->error('請設定月薪'); }
			}else if($data['pay_type']==0){ /*新增時薪*/
				$skills = $data['skills'];
				foreach ($skills as $key => $value) {
					$hour_pay = (float)$value['hour_pay'];
					if($hour_pay){ $data['pay_hour'] = $hour_pay; break; }
				}
				if(!is_numeric($data['pay_hour'])){ $this->error('請設定一個時薪'); }
			}
			if(isset($data['bonus'])){
				$data['bonus'] = json_encode($data['bonus'], JSON_UNESCAPED_UNICODE);
			}

			$salary_records_id = D('salary_records')->data($data)->add();
			/*設定薪資細節(工種時薪)*/
			foreach ($skills as $key => $value) {
				if($value['hour_pay']!==''){
					$skill_data = [
						'salary_records_id' => $salary_records_id,
						'user_skill_id' => $value['id'],
						'hour_pay' => $value['hour_pay'],
						'hour_pay_over' => $value['hour_pay_over'] ? $value['hour_pay_over'] : $value['hour_pay'],
					];
					D('salary_records_skill')->data($skill_data)->add();
				}
			}
			/*設定打卡日期*/
			if($data['pay_type']==1){ /*設定月薪*/
				$ym = date('Ym', strtotime($data['day_s']));
				$dates = array_map(function($item){ return $item['date']; }, AttendanceHelper::getDataList($ym));
				$todo_data = AttendanceHelper::get_todo_data($ym, $dates, $men_filter=['eip_user.id'=>$data['user_id']]);

				$todo_data = AttendanceHelper::get_todo_data($ym, $dates, $men_filter=['eip_user.id'=>$data['user_id']]);
				// dump($todo_data);exit;
				// 新增資料
				AttendanceHelper::addRows($ym, $todo_data['insertable_data']);
				// 更新資料
				AttendanceHelper::updateRows($ym, $todo_data['need_update_to_need_show']);
			}

			parent::error_log('新增薪資資訊:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
			$this->success('操作成功');
		}
		/*AJAX:刪除薪資紀錄*/
		public function delete_salary_records(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');
			parent::check_has_access('salary', 'edi');

			$id = $_POST['id'] ?? '';
			$salary_records = D('salary_records')->where('id='.$id)->find();
			if(!$salary_records){ $this->error('請提刪除對象'); }

			D('salary_records')->where('id='.$id)->delete();
			D('salary_records_skill')->where('salary_records_id='.$id)->delete();
			parent::error_log('刪除薪資資訊:'.$id.', 資料:'.json_encode($salary_records, JSON_UNESCAPED_UNICODE));
			$this->success('操作成功');
		}

		function edit(){
			$this->assign('page_title', '人事管理 > 編輯人事');
			parent::index_set('eip_user_right_type', 'id!=0');

			$cid = $_GET['id'];
			$this->assign('cid',$cid);
			
			$eip_user_right_type=D("eip_user_right_type")->where("status=1 and id!=0")->select();
			foreach($eip_user_right_type as $key=>$vo){
					if($vo['num'] == 999){
						$vo['num'] = 0;
						$code_num = ord($vo["code"][1]);
						if($code_num == 90){
							$vo["code"][1] = 'A';
							$vo["code"][0] = chr(ord($vo["code"][0])+1);
						}else{
							$vo["code"][1] = chr(ord($vo["code"][1])+1);
						}							
						$vo["code"] = $vo["code"][0].$vo["code"][1];
					}

				$cou[$vo['id']]=$vo['nick'].$vo['code'].str_pad($vo['num']+1, 4, "0", STR_PAD_LEFT);
			}
			
			if(isset($_GET['id'])){
				$id = I('id');
				
				$emdetails = D()->query("select * from `eip_user`,`eip_user_data` where `eip_user`.`id`=$cid and `eip_user`.`id`=`eip_user_data`.eid")[0];
				//dump($emdetails);
				$ap_user=D("eip_user")->where("apartmentid='".$emdetails['apartmentid']."' and status=1 and is_job=1 and id !=".self::$top_adminid)->select();
				$this->assign('ap_user',$ap_user);
				$emdetails['apartmentid'] = $emdetails['apartmentid']?$emdetails['apartmentid']:0;
				
				$emdetails['assess_ori']=json_decode($emdetails['assess']);
				$emdetails['work_expe']=json_decode($emdetails['work_expe']);
				$emdetails['educ_expe']=json_decode($emdetails['educ_expe']);
				$emdetails['other']=json_decode($emdetails['other']);
				$emdetails['other_time']=json_decode($emdetails['other_time']);

				$assess = 6;
				$emdetails['assess'] = [];
				if(count($emdetails['assess_ori'])>$assess){
					for($i=0;$i<count($emdetails['assess_ori']);$i=$i+$assess){
						for($j=0;$j<$assess;$j++){
							$emdetails['assess'][($i/$assess)][$j]=$emdetails['assess_ori'][$i+$j];
						}
					}
				}
				
				if(count($emdetails['work_expe'])>6){
					for($i=0;$i<count($emdetails['work_expe']);$i=$i+6){
						for($j=0;$j<6;$j++){
							$emdetails['work_exp'][($i/6)][$j]=$emdetails['work_expe'][$i+$j];
						}
					}
				}
				
				if(count($emdetails['educ_expe'])>5){
					for($i=0;$i<count($emdetails['educ_expe']);$i=$i+5){
						for($j=0;$j<5;$j++){
							$emdetails['educ_exp'][($i/5)][$j]=$emdetails['educ_expe'][$i+$j];
						}
					}
				}

				$myid=$emdetails['no'];

				if (is_null($emdetails['idno']) === false) 
					$emdetails['idno'] = Common::encrypt($emdetails['idno'], 'D');
			}else{
				$emdetails['right']=1;
				$emdetails['no']=$cou[1];
				$emdetails['dutday']=date('Y-m-d');
			}
			$eip_team=D("eip_team")->where("status=1")->select();
			$this->assign('eip_team',$eip_team);

			$apartlist = D()->query("select `id`,`name` from `eip_apart` where status=1");
			$this->assign('apartlist',$apartlist);

			$eip_jobs = D("eip_jobs")->where("status=1")->select();
			$this->assign('eip_jobs',$eip_jobs);

			$al_user=D("eip_user")->where("status=1 and is_job=1 and id !=".self::$top_adminid)->select();
			$this->assign('al_user',$al_user);
			
			$glist_op=D('access')->field('id,name')->select();
			$this->assign('glist_op',$glist_op);
			
			$edu=array("國小","國中","高中","專科","大學","研究所","博士班");
			$this->assign('edu',$edu);

			$user =  M("eip_user")->field("name")->where("id = ".$_SESSION['eid'])->select();
			$this->assign('user',$user[0]['name']);

			$work_times = AttendanceHelper::get_work_time_options(true);
			$this->assign('work_times', $work_times);

			$this->assign('myid',$myid);
			$this->assign('cou',$cou);
			//dump($emdetails);
			
			$this->assign('eip_user_right_type',$eip_user_right_type);
			
			$this->assign('action','emlist');
			
			$this->assign('em',$emdetails);
			$this->assign('time',time());

			parent::index_set('kpimodel', '`status`=1', 'name', false, 'id asc'); // 績效模組
			$current_kpimodel =  M("kpimodel_association as ka")
									->field("k.id, k.name, ka.time")
									->join('kpimodel as k on k.id=ka.kpimodel_id', 'left')
									->where("ka.user_id = '".$_GET['id']."'")
									->order('ka.id desc')
									->select();
			$current_kpimodel = empty($current_kpimodel) ? 0  : $current_kpimodel[0]['id'];
			$this->assign('current_kpimodel',$current_kpimodel); // 當前使用績效模組
			$this->display();
		}
		function update(){
			// dump($_POST);exit;

			// 登入帳號與密碼限輸入英文(大小寫)跟數字
			$msg = "";
			$key = array('username' => '登入帳戶', 'userpw' => '登入密碼');
			foreach ($key as $k => $v){
				if (trim(I($k)) != "" && ! preg_match("/^([0-9A-Za-z]+)$/", I($k))) {
					$msg = $v . "限輸入英文(大小寫)跟數字";
					break;
				}
			}
			if ($msg)
				$this->error($msg);

			$id = isset($_GET['id']) ? $_GET['id'] : 0;
			$id = $id ? $id : 0;
			$getname = D()->query("select `username` from eip_user where `username`='".$_POST['username'] ."' AND id!='".$id."'");
			if( $getname ) {
				$msg = "帳號：" . $getname[0]['username'] . "已經存在";
				$this->error($msg);
			}

			$cid = session('cid');
			if(!file_exists(ROOT_PATH.'user/'.$cid)){
				@mkdir(ROOT_PATH.'user/'.$cid);
			}
			if($_FILES['pics']['name']){
				$disname='Uploads/user/';
				$imgname=parent::uploadpic($disname);
			}
			$data['img']=$imgname;

			foreach($_POST as $key=>$val){
				if($key=='userpw'){
					if($_POST['userpw']){ $data[$key]=md5(I($key)); }
				}elseif( in_array($key, ['work_expe','educ_expe', 'assess', 'international_options', 'samecompany_options' ,'project_options']) ){
					$data[$key]=json_encode($val, JSON_UNESCAPED_UNICODE);
				}else if($key == 'other'){
					$data[$key]=json_encode($val);
					foreach($_POST['other'] as $k => $v){
						$_POST['other_time'][$k] = time();
						if($v == ''){ unset($_POST['other_time'][$k]); }
					}
					$data['other_time']=json_encode($_POST['other_time']);
				}elseif($key=='user_tag'){
					$data[$key]=implode("<br>",$val);
				}elseif($key=='userpw' && $val!=""){
					$data[$key]=$val;
				}elseif($key == 'idno' && $val != ""){
					$data[$key] = Common::encrypt(strtoupper($val), 'E');
				}else{
					$data[$key]=$val;
				}
			}

			$right = D('eip_user_right_type')->where('id="'.$_POST['right'].'"')->find();
			if(!$right){ $this->error('請選擇員工類型'); }
			$eip_user_count = D('eip_user')->where('`right`="'.$right['id'].'" AND is_job=1 AND status=1')->count();

			// dump($_POST);exit;
			if($id!=0 && $id!=''){ // 編輯
				parent::check_has_access(CONTROLLER_NAME, 'edi');

				$ori_user=D('eip_user')->where('id='.$id)->find();
				
				if($right['limit_num']!=-1 && $right['id']!=$ori_user['right']){
					if($eip_user_count+1>$right['limit_num']){
						$this->error('超出員工類型上限');
					}
				}
				// dump($data);exit;
				$queryed=D('eip_user')->data($data)->where('id='.$id)->save();
				if($_POST['kpimodel']!='0'){
					$kpi_data=[
						'kpimodel_id' => $_POST['kpimodel'],
						'user_id' => $id,
						'time' => strtotime(date("Y-m-d", time()).' 00:00:00')
					];
					$last_asso = M("kpimodel_association")->where('user_id = '.$id)->order('id desc')->select()[0];
					if($last_asso['kpimodel_id'] != $kpi_data['kpimodel_id']){
						// 最新的模組與本次要新增的模組不同才建立
						$ch_association = M("kpimodel_association")->data($kpi_data)->add();
					}else{
						$ch_association = 0;
					}
				}

				if($ori_user['no'] != $data['no']){ /*員工編號不一樣時*/
					$no_expe = D('eip_user_data')->where('eid='.$id)->find()['no_expe'];
					$no_expe = json_decode($no_expe);
					array_push($no_expe, [ time(), $data['no']]);
					$data['no_expe'] = json_encode($no_expe, JSON_UNESCAPED_UNICODE);
					$this->change_right_type($data['right']); /*更新員工編號統計*/
				}

				$detaed=D('eip_user_data')->data($data)->where('eid='.$id)->save();
				
				if($queryed || $detaed || $ch_association){
					parent::set_childeid();
					parent::error_log("修改 eip_user,ID: ".$id.", 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
					$this->success('操作成功，請稍候...',u("mens/emshow")."?id=".$id);
				}else{
					$this->error('沒有修改任何資料');
				}
			}else{ // 新增
				parent::check_has_access(CONTROLLER_NAME, 'new');

				if($right['limit_num']!=-1){
					if($eip_user_count+1>$right['limit_num']){
						$this->error('超出員工類型上限');
					}
				}

				//檢查空值
				$msg="";
				$key=array('name'=>'姓名','no'=>'員工號','username'=>'登入帳戶','userpw'=>'登入密碼','phone'=>'電話','mphone'=>'手機','addr'=>'聯絡地址');
				foreach($key as $k=>$v){
					if(trim(I($k))==""){
						$msg=$v."不能為空";
						break;
					}
				}
				if($msg){
					$this->error($msg);	
				}else{
					$expe[0][0]=time();
					$expe[0][1]=$data['no'];						
					$data['no_expe']=json_encode($expe, JSON_UNESCAPED_UNICODE);
					$data['cid']=$cid;
					$data['childeid'] = isset($data['childeid']) ? $data['childeid']: '' ;
					$queryed = D('eip_user')->data($data)->add();
					$data['eid']=$queryed;
					$detaed=D('eip_user_data')->data($data)->add();
					
					$this->change_right_type($data['right']);
					
					if($queryed || $detaed){
						$anay['name'] = $_POST['name'];
						$anay['num'] = '{"num":"0"}';
						$anay['user_id'] = $queryed;
						M("anay_0")->data($anay)->add();
						M("anay_1")->data($anay)->add();
						M("anay_2")->data($anay)->add();
						M("anay_3")->data($anay)->add();
						M("anay_4")->data($anay)->add();
						M("anay_5")->data($anay)->add();
						M("anay_new")->data($anay)->add();
						M("anay_now")->data($anay)->add();
						M("anay_old")->data($anay)->add();
						M("anay_develop")->data($anay)->add();
						M("anay_trash")->data($anay)->add();
						M("anay_out")->data($anay)->add();
						M("anay_phone")->data($anay)->add();
						M("anay_apdata")->data($anay)->add();
						M("anay_apdata_out")->data($anay)->add();
						M("anay_pedata")->data($anay)->add();
						$custo['id'] = $queryed;
						$custo['name'] = $_POST['name'];
						parent::set_childeid();
						
						if($_POST['kpimodel']!='0'){
							$kpi_data=[
								'kpimodel_id' => $_POST['kpimodel'],
								'user_id' => $queryed,
								'time' => strtotime(date("Y-m-d", time()).' 23:59:59')
							];
							M("kpimodel_association")->data($kpi_data)->add();
						}

						parent::error_log("新增 eip_user 資料:".json_encode($_POST, JSON_UNESCAPED_UNICODE));
						$this->success('操作成功，請稍候...',u("mens/emshow")."?id=".$queryed);
						exit;
						
					}else{
						$this->error('修改失敗...');
						
					}
					
				}
			}
		}
		// 更新員工種類數量
		function change_right_type($right){
			$oldnum = D("eip_user_right_type")->field('num,code')->where("id=".$right)->select();
					
			if($oldnum[0]["num"] == 999){
				$oldnum[0]["num"] = 0;
				
				$code_num = ord($oldnum[0]["code"][1]);
				if($code_num == 90){
					$oldnum[0]["code"][1] = 'A';
					$oldnum[0]["code"][0] = chr(ord($oldnum[0]["code"][0])+1);
					
				}else{
					$oldnum[0]["code"][1] = chr(ord($oldnum[0]["code"][1])+1);
				}							
				
				$oldnum[0]["code"] = $oldnum[0]["code"][0].$oldnum[0]["code"][1];
			}

			$qq = $oldnum[0]["num"] + 1;

			D()->execute("update eip_user_right_type set `num`='".$qq."' where id='".$right."'");
			D()->execute("update eip_user_right_type set `code`= '".$oldnum[0]["code"]."' where id='".$right."'");
		}
	}
?>