<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\CustoHelper;
	use Photonic\MoneyHelper;
	use Photonic\ContractHelper;

	class CrecordsController extends GlobalController{
		function _initialize($get_or_pay=0){
			parent::_initialize();
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

			$powercat_id = 72;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

			$this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
			$this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
			$this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
			$this->assign('AlllistController', $get_or_pay==0 ? 'Alllist' : 'Alllistpay');
			$this->assign('GetmoneyController', $get_or_pay==0 ? 'Getmoney' : 'Getmoneypay');

			$get_pay_words = ContractHelper::get_pay_words($this->get_or_pay);
			foreach ($get_pay_words as $key => $value) {
				$this->$key = $value;
				$this->assign($key, $value);
			}
		}
		
		//客户管理 進度選日期
		function index(){
			$params = $_GET;
			// if($params['cate'] == '')	$params['cate'] = 1;
			$result = ContractHelper::get_contract_where_sql($params, $this->get_or_pay);
			// dump($result);
			/*等級選單*/
			$this->assign('levels', $result['levels']);
			/*通用產業選單*/
			$this->assign('industr', $result['industr_all']);
			$this->assign('industr2_search', $result['industr2_search']);
			$where_query = $result['where_query'];

			$params['ship_status'] = 1; /*只看已確認出貨的*/
			$params['queryflag'] = 1; /*只看已核可的請款*/
			$money_data = MoneyHelper::get_money($params, $this->get_or_pay);
			$this->assign("getedflag", $money_data['getedflag']);
			$this->assign('money_table', $money_data['money_table']);
			$this->assign("crm_contract", $money_data['crm_contract']);
			$this->assign("all", $money_data['all']);
			$this->assign("show",$money_data['show']);

			$money_data_search_all = MoneyHelper::get_money($params, $this->get_or_pay, 0);
			$this->assign("search_all", $money_data_search_all['all']);

			$this->assign("limit", strtotime(date("Ymd",time()))); // ???

			parent::index_set('crm_cum_pri',"true",'',false);//2019/11/08  fatfat改版
			parent::index_set('eip_user',"is_job=1 and id !=". self::$top_adminid, '', false);
			$this->assign("page",$_GET['p']);

			$crm_cum_cat=ContractHelper::get_crm_cum_cat($this->get_or_pay);
			$this->assign("crm_cum_cat", $crm_cum_cat);

			$this->display('Crecords/index');
		}
		//匯出excel
		public function excel(){
			// if($_GET['cate']=="")$_GET['cate']=1;
			$get_data = $_GET;
			$get_data['ship_status'] = 1; /*只看已確認出貨的*/
			$get_data['queryflag'] = 1; /*只看已核可的請款*/
			$money_data = MoneyHelper::get_money($get_data, $this->get_or_pay);
			$crm_contract = $money_data['crm_contract'];

			$title=array(
				"序號",
				"期數",
				"公司名",
				self::$system_parameter['合約']."號",
				"本期申請金額",
				"款項類型",
				"本期銷".$this->預收."款",
				"本期總".$this->應收."款",
				"本期稅金",
				"本期稅前金額",
				$this->收款."損益",
				$this->收款."方式",
				"支票日期",
				"支票號碼",
				"備註",
				"到付日期",
				$this->收訖,
			);
			
			$export_data = [];
			foreach($crm_contract as $key=>$v){
				array_push($export_data, [
					"序號" => $key+1,
					"期數" => $v['qh_count'],
					"公司名" => $v['name'],
					self::$system_parameter['合約']."號" => $v['sn'],
					"本期申請金額" => $v['dqmoney'],
					"款項類型" => $v['prepaid']=='1'?'預收款':'貨款',
					"本期銷".$this->預收."款" => $v['xdj'],
					"本期總".$this->應收."款" => $v['earn'],
					"本期稅金" => $v['xqj_tax'],
					"本期稅前金額" => (String)$v['earn_pretax'],
					$this->收款."損益" => $v['tips1'],
					$this->收款."方式" => $v['payment'],
					"支票日期" => date("Y/m/d",$v['c_ticketdate']),
					"支票號碼" => $v['c_ticket'],
					"備註" => $v['zkbz'],
					"到付日期" => date("Y/m/d",$v['exptime']),
					$this->收訖 => $v['getedflag']=='1'?$this->收訖:$this->未收,
				]);
			}
			// dump($export_data);exit;
			$file_title = $this->收款."紀錄";
			parent::DataDbOut($export_data,$title,$list_start="A2",$file_title);
		}

		//Api:改資料ajax
		public function aj_chcontent(){
			$data = $_POST['data'];
			$id = $_POST['id'];
			$dbname = $_POST['dbname'];
			$row = $_POST['row'];

			if(!isset($dbname) || !isset($row)){
				$this->error("操作失敗");
			}
			$this->check_crecords_access(CONTROLLER_NAME, 'edi', [$id], 0, $dbname);
			if(!in_array($row, [
					'getedflag',
					'exptime',
					'tips1',
					'c_ticketdate',
					'c_ticket',
					'skbz',
					'paymenttype',
				])
			){
				$this->error('無法修改此欄位');
			}

			$data_save[$row] = $data;
			if(in_array($row, ["exptime", "c_ticketdate"])){
				$data_save[$row] = strtotime($data);
			}else if($row=='getedflag'){
				if($data==1){
					$data_save['dateline'] = time();
				}else{
					$data_save['dateline'] = 0;
				}
			}
			// dump($data_save);exit;
			D($dbname)->data($data_save)->where("id='".$id."'")->save();
			
			parent::error_log("修改".$dbname."資料列:".$id."的資料".json_encode($data_save, JSON_UNESCAPED_UNICODE));
			$this->success('修改成功');
		}
		/*Api:判斷時間限制*/
		public function limit(){
			$date=date_create();
			$d = getdate();

			date_date_set($date,$d['year'],$d['mon'],$d['mday']-7);
			$limit = strtotime(date_format($date,"Ymd"));
			// $limit = strtotime(date("Ymd",time()));

			if($limit > strtotime($_POST['date']) && $_POST['date'] != ''){
				// $this->error('到付日期不能填7天以前');
			}
			else{
				$this->success('修改成功');
			}
		}

		/*檢查是否有該付款的操作權限*/
		public function check_crecords_access($acc_type, $acc_method, $ids=[], $teamid=0, $target_table='crm_othermoney'){
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