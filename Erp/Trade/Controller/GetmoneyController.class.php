<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\CustoHelper;
	use Photonic\MoneyHelper;
	use Photonic\Invoice;
	use Photonic\ContractHelper;
	
	class GetmoneyController extends GlobalController{
		function _initialize($get_or_pay=0){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$num=0;
			$year=date("Y",time());
			for($j=date("m",time());$j>=1;$j--){
				$mdate[$num++]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
			}
			for($y=1;$y<=3;$y++){
				for($i=12;$i>=1;$i--){
					$mdate[$num++]=($year-$y)."".str_pad($i,2,'0',STR_PAD_LEFT);
				}
			}
			$this->assign("mdate",$mdate);

			$powercat_id = 71;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

			$this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
			$this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->assign('AlllistController', $get_or_pay==0 ? 'Alllist' : 'Alllistpay');

			$get_pay_words = ContractHelper::get_pay_words($this->get_or_pay);
			foreach ($get_pay_words as $key => $value) {
				$this->$key = $value;
				$this->assign($key, $value);
			}
		}

		/*出貨詳細內容頁*/
		public function records(){
			$get_data = $_GET;
			$this->assign('id', $get_data['id']);

			$get_data['crm_contract_id'] = $get_data['id'];
			$contracts = ContractHelper::get_contracts($get_data, $this->get_or_pay, false);
			if(count($contracts)!=1){
				$this->error('連結有誤');
			}else if($contracts[0]['flag']==0){
				$this->error('提案中無法申請款項', U('Alllist/index', ['contract_text'=>$contracts[0]['sn']]));
			}
			$this->assign("current_qh", date('Y/m'));

			parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版
			$this->display('Getmoney/records');
		}
		/*AJAX:出貨管理records資料*/
		public function ajax_records_data(){
			$id = $_GET['id'];
			$data['contract'] = D("crm_contract c")->field("c.*,e.name as ename,u.name as uname, cat.name as cate_name")
											->join("left join crm_crm e on c.cid=e.id")
											->join("left join crm_cum_cat cat on c.cate=cat.id")
											->join("left join eip_user u on u.id=c.eid")->where(" c.id=".$id)->find();
			$data['contract']['money_remained'] = MoneyHelper::count_contract_money($id)['all']['allmoney_prepaid'];

			$data['units'] = ContractHelper::get_crm_contract_unit($id)['cat_units'];
			foreach ($data['units'] as $key => $value) {
				$data['units'][$key]['used_num'] = MoneyHelper::get_ships_uesed_num($id, $value['id']);
			}

			$total_data = MoneyHelper::count_money_total($id);
			$data['total'] = $total_data['total'];
			$data['total2'] = $total_data['total2'];

			$this->ajaxReturn($data);
		}
		/*AJAX:取得請款*/
		public function ajax_get_money(){
			/*依分頁取得搜尋結果*/
			$get_data = $_GET;
			$page_count = $_GET['page_count'] ?? 0;
			$money_data = MoneyHelper::get_money($get_data, $this->get_or_pay, $page_count);
			/*計算總數量*/
			unset($_GET['p']);
			$money_data_all = MoneyHelper::get_money($get_data, $this->get_or_pay, 0);
			$money_data['count_of_items'] = count($money_data_all['crm_contract']);
			$this->ajaxReturn($money_data);
		}
		/*AJAX:取得出貨項目*/
		public function ajax_get_ships(){
			$caseid = $_GET['caseid'];
			$moneyid = $_GET['moneyid'];
			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$caseid]);
			$shipments = MoneyHelper::get_ships($caseid, $moneyid);
			$this->ajaxReturn($shipments);
		}
		/*AJAX:計算請款總金額*/
		public function aj_total(){
			$total_data = MoneyHelper::count_money_total($_POST['caseid']);

			$total_data['units'] = ContractHelper::get_crm_contract_unit($_POST['caseid'])['cat_units'];
			foreach ($total_data['units'] as $key => $value) {
				$total_data['units'][$key]['used_num'] = MoneyHelper::get_ships_uesed_num($_POST['caseid'], $value['id']);
			}
			
			$this->ajaxReturn($total_data);
		}

		/*AJAX:新增請款-一般款項、SEO預收款*/
		public function create_money(){
			$caseid = $_POST['caseid'];
			$prepaid = $_POST['prepaid'];
			$ships = $_POST['ships'];
			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$caseid]);
			MoneyHelper::create_money($caseid, $prepaid, $ships);
			$this->success('操作成功');
		}
		/*AJAX:新增出貨單項目*/
		public function add_ship_ajax(){
			// dump($_POST);exit;
			$caseid = $_POST['caseid'];
			$moneyid = $_POST['moneyid'];
			$ships = $_POST['ships'];
			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$caseid]);

			MoneyHelper::add_sales($caseid, $moneyid, $ships);
			$this->success('操作成功');
		}
		/*AJAX:刪除出貨單某項目*/
		public function del_ship_ajax(){
			$shipment_id = $_POST['shipment_id'];
			$shipment = D("crm_shipment")->where('id='.$shipment_id)->find();
			if(!$shipment){ $this->error('無此項目'); }
			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$shipment['caseid']]);

			D("crm_shipment")->where('id='.$shipment_id)->delete();
			MoneyHelper::add_sales($shipment['caseid'], $shipment['moneyid'], []);
			$this->success('操作成功');
		}
		/*AJAX:刪除請款*/
		public function del_money(){
			$caseid = $_GET['caseid'];
			$moneyid = $_GET['moneyid'];
			if($caseid && $moneyid){
				$this->check_alllist_access(CONTROLLER_NAME, 'del', [$caseid]);
				$money_table = MoneyHelper::money_table_by_contract($caseid);
				D($money_table)->where("id='".$moneyid."'")->delete();
				D("crm_shipment")->where("caseid='".$caseid."' AND moneyid='".$moneyid."'")->delete();
				D("print_txt")->where("caseid='".$caseid."' AND moneyid='".$moneyid."'")->delete();
				D('schedule_date')->where("moneyid='".$moneyid."'")->data(['moneyid' => 0,])->save();
				$this->success("刪除成功");
			}
			$this->error("刪除失敗");
		}
		/*AJAX:修改請款本期應收*/
		public function save_xqj(){
			$caseid = $_POST['caseid'];
			$moneyid = $_POST['moneyid'];
			$val = $_POST['val'];
			
			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$caseid]);

			$error_msg = MoneyHelper::set_xqj($caseid, $moneyid, $val);
			if($error_msg){
				$this->error($error_msg);
			}else{
				$this->success('操作成功');
			}			
		}
		
		/*請款單 詳細內容*/
		public function outer(){
			$contract_id=$_GET['caseid'];
			$moneyid=$_GET['moneyid'];
			$money_table = MoneyHelper::money_table_by_contract($contract_id);
			$money=D($money_table)->where("id=".$moneyid)->find();
			if(!$money){ $this->error('網址有誤'); }
			if($money['xqj']==null){ 
				D($money_table)->where("id=".$money['id'])->data(['ship_status'=>0])->save();
				$this->error('請先設定本期'.$this->應收.'金額');
			}
			$this->assign("money", $money);

			$prepaid=$money['prepaid'] ?? 0; /*預設看一般請款*/

			//取得合約
			$contract=D("crm_contract c")->field("c.*,s.id as rid")
									->join("left join crm_shipment s on c.id=s.caseid")
									->where('c.id="'.$contract_id.'"')
									->find();
			$this->assign("contract", $contract);
			switch($contract['cate']){
				case 1:
					if($prepaid==0){
						$outer_data = MoneyHelper::get_outer_money_seo($contract_id, $moneyid);
						$this->assign("datespan", $outer_data['datespan']);
						$this->assign("crm_contract_seo_upmoney",$outer_data['crm_contract_seo_upmoney']);						
					}else{
						$outer_data = MoneyHelper::get_outer_money($contract_id, $moneyid);
					}
					break;

				default:
					$outer_data = MoneyHelper::get_outer_money($contract_id, $moneyid);
					break;
			}

			// dump($outer_data);exit;
			$this->assign("all",$outer_data['all']);
			$this->assign("salelist", $outer_data['salelist']);
			
			$invoice = $contract['invoice'];	/*預設勾選發票類型*/
			if($money){
				if($money['invoice']!=''){
					$invoice=$money['invoice'];
				}
				$moneyid = $money['id'];
				$prepaid = $money['prepaid'];
			}
			switch($invoice){
				case "二聯":
					$invoice_type=1;
					break;
				case "三聯":
					$invoice_type=2;
					break;
				case "":
					$invoice_type='';
					break;
				default:
					$invoice_type=0;
			}
			$this->assign("invoice_type", $invoice_type);
			$this->assign("money", $money);
			$this->assign("moneyid", $moneyid);
			$this->assign('prepaid', $prepaid);
			$this->assign('outer_title', $prepaid==1 ? $this->預收.'款申請單' : $this->出貨.'單');

			$print_txt=D("print_txt")->where("caseid='".$contract_id."' AND moneyid='".$moneyid."'")->find();
			if(!isset($print_txt)){
				$print_txt['receive_name'] = $crm_crm['show_contacter_name'];
				$print_txt['receive_phone'] = $crm_crm['show_contacter_phone'];
				$print_txt['phone_time'] = '[]';
			}
			$this->assign("print_txt", $print_txt);

			$get_data['id'] = $contract['cid'];
			$crm_rightdata = CustoHelper::get_crm_rightdata($get_data, -1);
			$crm_crm = $crm_rightdata['newbier'];
			$crm_crm['show_addr'] = CustoHelper::get_crm_show_addr($crm_crm, $type='mail');
			if($crm_rightdata['crm_contact']){
				$crm_crm['show_contacter_name'] = $crm_rightdata['crm_contact_top']['cname'];
				$crm_crm['show_contacter_phone'] = $crm_rightdata['crm_contact_top']['mobile'];
			}else{
				$crm_crm['show_contacter_phone'] = $crm_crm['commobile'];
			}
			// dump($crm_rightdata);exit;
			$this->assign("crm_crm",$crm_crm);

			$wname=D("eip_user")->where("id='".$crm_crm['wid']."'")->find();
			$this->assign("wname",$wname);
			
			$note = M("crm_outer_note")->select();
			$this->assign("note",$note[0]['note']);

			$this->assign("fix", 1+TAX_RATE);
			parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版

			$this->display('Getmoney/outer');
		}
		/*AJAX:核可出貨單*/
		public function confirm_sale(){
			$caseid = $_POST['caseid']; /*合約ID*/
			$moneyid = $_POST['moneyid']; /*請款ID*/
			$invoice = $_POST['invoice']; /*發票類型*/
			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$caseid]);

			$confirm_sale_result = MoneyHelper::confirm_sale($caseid, $moneyid, $invoice);
			$this->ajaxReturn($confirm_sale_result);
		}
		/*AJAX:取消核可出貨單*/
		public function cancel_sale(){
			$caseid = $_POST['caseid']; /*合約ID*/
			$moneyid = $_POST['moneyid']; /*請款ID*/

			$money_table = MoneyHelper::money_table_by_contract($caseid);
			$money =  M($money_table)->where("id='".$moneyid."'")->find();
			if(!$money){ $this->error('無此項目'); }
			if($money['ship_status']==0){ $this->error('尚未確認金額，無法取消'); }

			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$caseid]);

			/*重設出貨單*/
			M($money_table)->where("id='".$moneyid."'")->data(['ship_status'=>0])->save();

			$this->success('操作成功');
		}

		/*新增請款-SEO請款(非預收)*/
		public function create_money_seo(){
			$contract_id=$_GET['caseid'];
			$qh=$_GET['qh'] ?? '';
			$qh = $qh ? $qh : date('Y/m');
			$qh_seo = date('Y/m', strtotime($qh.'/01 -1Month')); /*排名的內容為請款日的前一月*/
			// dump($qh_seo);exit;

			$this->check_alllist_access(CONTROLLER_NAME, 'new', [$contract_id]);

			$outer_data = MoneyHelper::get_outer_money_seo($contract_id, '', $qh_seo);
			// dump($outer_data);exit;
			$money = [
				'caseid' => $contract_id,
				'qh' => $qh,
				'prepaid' => 0,
				'qh_seo' => $qh_seo,
				'dqmoney' => $outer_data['all']['money'],
				'upmoney' => $outer_data['crm_contract_seo_upmoney'],
				'ship_status' => 0,
				'create_user_name' => session('userName'),
			];
			// dump($money);exit;
			/*查看有無 該合約 當月 非預付 的請款*/
			$crm_seomoney = D("crm_seomoney")->where("caseid='".$contract_id."' AND qh_seo='".$qh_seo."' AND prepaid=0")->find();
			if($crm_seomoney){/*有、編輯*/
				D("crm_seomoney")->where("id='".$crm_seomoney['id']."'")->data($money)->save();
			}else{ /*無、新增*/
				$count = 1;
				$crm_seomoney = D("crm_seomoney")->where("caseid='".$contract_id."' AND qh='".$qh."'")->order('count desc')->find();
				if($crm_seomoney){
					$count = $crm_seomoney['count'] + 1;
				}
				$money['count'] = $count;
				// dump($money);exit;
				D("crm_seomoney")->data($money)->add();
			}

			$this->redirect('/Getmoney/records?id='.$contract_id);
		}
		/*SEO排名頁*/
		public function monthput(){
			$id=$_GET['id'];
			$timesplit = explode( "/", $_GET['qh'] );
			$engines = array('yahoo(台灣)'=>'TY','google(台灣)'=>'TG','google'=>'AG','yahoo(美國)'=>'AY','google(美國)'=>'AG');

			$y = $timesplit[0];
			$m = $timesplit[1];
			//該月第一天
			//$date1=$y."-".$m."-01";
			$date1=$y."-".$m."-01";
			//該月最後一天
			$date2=strtotime($date1." +1 months -1 days");
			//取該月每一天
			for($i=1;$i<=date("d",$date2);$i++){
				$month[$i]=$y."-".$m."-".str_pad($i, 2, "0", STR_PAD_LEFT);
			}
			if($y==date("Y") && $m==date("m")){
				$mydb=D("crm_key_rank");
			}else{
				$mydb=M()->db(1,"DB_SEO_RANK")->table("seo_rank_".$y.$m.$d);
			}
			$crm_contract=D("crm_contract c")->field("c.*,s.id as rid,e.name as ename")
											->join("left join crm_crm e on c.cid=e.id")
											->join("left join crm_shipment s on c.id=s.caseid")->where("c.id=".$id)->limit(1)->select()[0];
			//關鍵字組列表
			$crm_seo_key=D("crm_seo_key")->where("caseid=".$id)->select();

			if( count( $crm_seo_key ) > 0 ) {//如果有关键字组
				$instr ='`key_Id` in(';
				$comm = '';
				foreach($crm_seo_key as $key)
				{
					foreach($month as $mkey=>$mvo){
						$box[$key['id']][$mvo]=0;
					}
					$jfcount[$key['id']]=0;
					$spanarr = array( "1" => "1~10","11" => "11~30",
					"12" => "1~20",
					"2" => "1~30",
					"3" => "1~3",
					"4" => "1~5",
					"5" => "4~10",
					"6" => "6~10",
					"7" => "4~5");
					$key['starts'] = $spanarr[$key['starts']];
					$span = explode( "~",  $key['starts'] );
					$start = $span['0'];//开始名次
					$end   = $span['1'];//结束名次
					$range[$key['id']]=range($start,$end);//产生数组

					$instr .= $comm.$key['id'];
					$comm=',';
				}
				//$instr .=') and ';
				$instr .=')  ';
				} else {
				$instr .= '`key_Id`<0';
			}
			$crm_key_rank=$mydb->field("`key_ranking`,`update`,`key_Name`,`key_Id`")
							->where($instr." and `update`>='" . $date1 . "' and `update`<='" . date('Y-m-d',$date2) . "' ")->group("key_Id,`update`")->select();
			//dump($crm_key_rank);
			foreach($crm_key_rank as $key=>$vo){
				$jfday[$vo['update']]=0;//计费个数
			}
			foreach($crm_key_rank as $key=>$vo){
				$box[$vo['key_id']][$vo['update']]=$vo['key_ranking'];
				if(in_array($vo['key_ranking'],$range[$vo['key_id']])) {
					//dump($vo);
					$jfcount[$vo['key_Id']]++;//计费天数
					$jfday[$vo['update']]++;//计费个数
				}
			}
			// dump($month);
			// dump($jfday);
			// dump($jfcount);

			// dump($crm_seo_key);
			// 整理crm_seo_key(讓他的個array只允許存最多13個字組)
			$crm_seo_key_adjust=[];
			$adjust_seo_key=[];
			foreach ($crm_seo_key as $key => $value) {
				array_push($adjust_seo_key, $value);

				if(count($adjust_seo_key)==13){
					array_push($crm_seo_key_adjust, $adjust_seo_key);
					$adjust_seo_key=[];
				}
			}
			if(count($adjust_seo_key)!=0){
				array_push($crm_seo_key_adjust, $adjust_seo_key);
			}
			// dump($crm_seo_key_adjust);
			// exit;

			$this->assign("engines",$engines);
			$this->assign("jfday",$jfday);
			$this->assign("month",$month);
			$this->assign("contract",$crm_contract);
			$this->assign("jfcount",$jfcount);
			$this->assign("seolist",$crm_seo_key);
			$this->assign("seolist_adjust",$crm_seo_key_adjust);
			$this->assign("ranks",$crm_key_rank);
			$this->assign("box",$box);
			$this->assign("fieldNum",count($crm_seo_key));
			$this->display();
		}
		/*AJAX:改SEO排名資料*/
		public function aj_chseo(){
			parent::check_has_access(CONTROLLER_NAME, 'new');

			if(!isset($_POST['data'])){ $this->error('操作錯誤'); }

			$time = strtotime($_POST['mydate']) ;
			$y = date("Y",$time);
			$m =  date("m",$time);
			$table = "CREATE TABLE seo_rank_".$y.$m." (id int(10) NOT NULL AUTO_INCREMENT,key_Id int(10),key_Name int(10),key_ranking int(10),BuildDate char(10),BuildTime varchar(8),SearchTime varchar(8),`update` date,employee_Id varchar(20),searchEngine varchar(10),customers_Name varchar(200),c_ID int(10),uptime time,url_ranking varchar(100),states int(1),PrevRank int(11),PRIMARY KEY (id))";
			try{
				M()->db(1,"DB_SEO_RANK")->execute($table);
			}catch(\Think\Exception $e){
				// dump("seo_rank_".$y.$m."資料表已存在");
				$this->error($e->getMessage());
			}

			$data['key_ranking']=$_POST['data'];
			$count=0;
			if($y==date("Y") && $m==date("m")){
				$count=D("crm_key_rank")->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->count();
				$mydb=D("crm_key_rank")->data($data)->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->save();
				if($count==0){//新增
					$crm_seo_key=D("crm_seo_key")->where("id='{$_POST['key_id']}'")->find();

					$data['key_Id']=$crm_seo_key['id'];
					$data['key_Name']=$crm_seo_key['key_name'];
					$data['BuildDate']=date("Y/m/d");
					$data['BuildTime']=date("h:i:s");
					$data['update']=$_POST['mydate'];
					$data['searchEngine']=$crm_seo_key['engine'];
					$data['c_id']=$crm_seo_key['contract_id'];
					$data['url_ranking']=$crm_seo_key['url1'];

					if($data['key_Id']!=""){
						$mydb=D("crm_key_rank")->data($data)->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->add();
					}

				}else{//修改
					$mydb=D("crm_key_rank")->data($data)->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->save();
				}
			}else{//舊資料
				$count=M()->db(1,"DB_SEO_RANK")->table("seo_rank_".$y.$m)->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->count();
				if($count==0){
					$crm_seo_key=D("crm_seo_key")->where("id='{$_POST['key_id']}'")->find();
					$data['key_Id']=$crm_seo_key['id'];
					$data['key_Name']=$crm_seo_key['key_name'];
					$data['BuildDate']=date("Y/m/d");
					$data['BuildTime']=date("h:i:s");
					$data['update']=$_POST['mydate'];
					$data['searchEngine']=$crm_seo_key['engine'];
					$data['c_id']=$crm_seo_key['contract_id'];
					$data['url_ranking']=$crm_seo_key['url1'];
					if($data['key_Id']!=""){
						$mydb=M()->db(1,"DB_SEO_RANK")->table("seo_rank_".$y.$m)->data($data)->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->add();
					}
				}else{
					$mydb=M()->db(1,"DB_SEO_RANK")->table("seo_rank_".$y.$m)->data($data)->where("`key_Id`='{$_POST['key_id']}' and `update`='{$_POST['mydate']}'")->save();
				}
			}

			if($count==0){
				parent::error_log("新增 crm_key_rank 資料 key_id= ".$_POST['key_id']." 日期:{$_POST['mydate']} 資料 {$_POST['data']}");
			}else{
				parent::error_log("修改 crm_key_rank 資料 key_id= ".$_POST['key_id']." 日期:{$_POST['mydate']} 資料 {$_POST['data']}");
			}
			$this->success('修改成功');
		}

		/*新增請款(排班人力請款)*/
		public function create_money_schedule(){
			$this->check_alllist_access(CONTROLLER_NAME, 'new', []);
      try {
        $result = MoneyHelper::create_money_schedule($_POST);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success($result);
    }
		

		/*重新對應print_txt 與 money*/
		public function set_print_txt_moneyid(){
			$print_txt = D('print_txt')->select();
			// dump($print_txt);exit;
			foreach ($print_txt as $key => $value) {
				if($value['date']){
					$date = $value['date'];
					$dates = explode('-', $date);
					$qh = $dates[0];
					$count = $dates[1];
					if($count!=0){
						$money_table = MoneyHelper::money_table($value['cate']);
						$money = D($money_table)->where('caseid="'.$value['caseid'].'" AND qh="'.$qh.'"')->find();
						if($money){
							D('print_txt')->where('id="'.$value['id'].'"')->data(['moneyid'=>$money['id']])->save();
						}
					}
				}
			}
		}

		
		//請款列表
		public function index(){
			$params = $_GET;
			// if($params['cate'] == '') $params['cate'] = 1;
			D("crm_othermoney")->where("dqmoney=0")->delete();//20170530檢查錯誤核銷資料 有問題可移除

			$result = ContractHelper::get_contract_where_sql($params, $this->get_or_pay);
			// dump($result);
			/*等級選單*/
			$this->assign('levels', $result['levels']);
			/*通用產業選單*/
			$this->assign('industr', $result['industr_all']);
			$this->assign('industr2_search', $result['industr2_search']);

			$params['ship_status'] = 1; /*只看已確認出貨的*/
			$money_data = MoneyHelper::get_money($params, $this->get_or_pay);
			$this->assign("queryflag", $money_data['queryflag']);
			$this->assign('money_table', $money_data['money_table']);
			$this->assign("crm_contract", $money_data['crm_contract']);
			$this->assign("all", $money_data['all']);
			$this->assign("show",$money_data['show']);
			
			$money_data_search_all = MoneyHelper::get_money($params, $this->get_or_pay, 0);
			$this->assign("search_all", $money_data_search_all['all']);

			parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版
			parent::index_set('eip_user',"is_job=1 and id !=". self::$top_adminid, '', false);
			$this->assign("page",$_GET['p']);
	
			$crm_cum_cat=ContractHelper::get_crm_cum_cat($this->get_or_pay);
			$this->assign("crm_cum_cat", $crm_cum_cat);

			$this->display('Getmoney/index');
		}
		public function excel(){
			// if($_GET['cate'] == '')	$_GET['cate'] = 1;
			$get_data = $_GET;
			$get_data['ship_status'] = 1; /*只看已確認出貨的*/
			$money_data = MoneyHelper::get_money($get_data, $this->get_or_pay);
			$crm_contract = $money_data['crm_contract'];

			$title=array(
				"序號",
				"期數",
				"信封",
				"公司名",
				self::$system_parameter['合約']."號",
				self::$system_parameter['合約']."總金額",
				"訂金款項",
				"非訂金款項",
				"本期申請金額",
				"款項類型",
				"本期銷".$this->預收."款",
				"本期總".$this->應收."款",
				"本期稅金",
				"本期稅前金額",
				"帳款損益",
				"發票日期",
				"發票號碼",
				"備註",
				"送查",
			);
			
			$export_data = [];
			foreach($crm_contract as $key=>$v){
				array_push($export_data, [
					"序號" => $key+1,
					"期數" => $v['qh_count'],
					"信封" => $v['envelope']=='1'?'已印':'未印',
					"公司名" => $v['name'],
					self::$system_parameter['合約']."號" => $v['sn'],
					self::$system_parameter['合約']."總金額" => $v['allmoney'],
					"訂金款項" => $v['money'],
					"非訂金款項" => $v['allmoney'] - $v['money'],
					"本期申請金額" => $v['dqmoney'],
					"款項類型" => $v['prepaid']=='1'? $this->預收.'款':'貨款',
					"本期銷".$this->預收."款" => $v['xdj'],
					"本期總".$this->應收."款" => $v['earn'],
					"本期稅金" => $v['xqj_tax'],
					"本期稅前金額" => (String)$v['earn_pretax'],
					"帳款損益" => $v['tips'],
					"發票日期" => $v['ticketdate'],
					"發票號碼" => $v['ticket'].'  '.$v['ticket_rand'],
					"備註" => $v['zkbz'],
					"送查" => $v['queryflag'] ?'已核可':'未核可',
				]);
			}
			// dump($export_data);exit;
			$file_title = $this->收款."申請";
			parent::DataDbOut($export_data,$title,$list_start="A2",$file_title);
		}

		//Api:修改請款資料
		public function aj_chcontent(){
			$data = $_POST['data'];
			$id = $_POST['id'];
			$dbname = $_POST['dbname'];
			$row = $_POST['row'];

			if(!isset($dbname) || !isset($row)){
				$this->error("操作失敗");
			}
			$this->check_getmoney_access('getmoney', 'edi', [$id], 0, $dbname);
			if(!in_array($row, [
					'envelope', 
					'tips', 
					'ticketdate', 
					'ticket', 
					'ticket_rand', 
					'zkbz',
				])
			){
				$this->error('無法修改此欄位');
			}

			$data_save[$row]=$data;
			if($row=="ticketdate"){ 
				$data_save[$row]=strtotime($data);
			}
			// dump($data_save);exit;
			D($dbname)->data($data_save)->where("id='".$id."'")->save();
			
			parent::error_log("修改".$dbname."資料列:".$id."的資料".json_encode($data_save, JSON_UNESCAPED_UNICODE));
			$this->success("更新成功");
		}
		public function aj_money_queryflag(){
			$data = $_POST['data'];
			$id = $_POST['id'];
			$dbname = $_POST['dbname'];
			if(!isset($dbname)){
				$this->error("操作失敗");
			}
			$this->check_getmoney_access('getmoney', 'del', [$id], 0, $dbname);

			$data_save['queryflag']=$data;
			if($data==1){
				$data_save['queryflag_time'] = time();
				$data_save['audit_user_name'] = session('userName');
			}else{
				$data_save['queryflag_time'] = "";
				/*串電子發票 作廢*/
				// $Return_Info = Invoice::instance()->delete_invoice($dbname, $id);
				// // dump($Return_Info);
				// if($Return_Info['RtnCode']!='1' && self::$control_ecpay_invoice=='1'){
				// 	$this->error('電子發票作廢失敗');
				// }
			}
			// dump($data_save);exit;
			D($dbname)->data($data_save)->where("id='".$id."'")->save();
			
			parent::error_log("修改".$dbname."資料列:".$id."的資料".json_encode($data_save, JSON_UNESCAPED_UNICODE));
			$this->success("更新成功");
		}

		/*檢查是否有該合約的操作權限*/
		public function check_alllist_access($acc_type, $acc_method, $ids=[], $teamid=0, $target_table='crm_contract'){
			parent::check_has_access($acc_type, $acc_method); /*檢查是否有設定此權限*/

			// dump($ids);exit;
			if($ids!=[]){ /*須依根據處理對象檢查權限*/
				foreach ($ids as $id) {
					$crm_contract = D('crm_contract')->find($id);
					if($this->get_or_pay!=$crm_contract['get_or_pay']){ $this->error('請至正確頁面操作'); }
				}
			}
		}
		/*檢查是否有該付款的操作權限*/
		public function check_getmoney_access($acc_type, $acc_method, $ids=[], $teamid=0, $target_table='crm_othermoney'){
			parent::check_has_access($acc_type, $acc_method); /*檢查是否有設定此權限*/

			if(!in_array($target_table, ['crm_othermoney', 'crm_seomoney'])){
				$this->error('不可修改此資料表');
			}
			// dump($ids);exit;
			if($ids!=[]){ /*須依根據處理對象檢查權限*/
				foreach ($ids as $id) {
					$money = D($target_table.' m')->field('c.get_or_pay')
												  ->join('crm_contract c on c.id=m.caseid', 'LEFT')
												  ->where('m.id='.$id)->find();
					if(!$money){ $this->error('無此付款'); }
					if(!is_null($money['get_or_pay'])){
						if($this->get_or_pay!=$money['get_or_pay']){ $this->error('請至正確頁面操作'); }
					}
				}
			}
		}
	}
	
?>