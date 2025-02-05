<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;
	
	use Photonic\Common;
	use Photonic\CustoHelper;

	class ServertemplateController extends GlobalController 
	{	
		public $prefix;
		public $prefix_betime;
		public $prefix_endtime;
		public $prefix_note;
		public $prefix_user;
		public $prefix_pwd;
		public $prefix_givepaytype;
		public $prefix_company;
		public $prefix_status;
		public $cate_num;

		function _initialize($c_title, $controller, $prefix, $cate_num){
			parent::_initialize();
			parent::index_set('crm_cum_pri','id=1');

			$this->db=D('crm_contract_host');
			$this->dbname="crm_contract_host";

			$this->assign("c_title", $c_title);
			$this->assign("controller", $controller);
			$this->controller = $controller;
			$this->assign("controller_lower", strtolower($controller));
			
			$this->prefix = $prefix;
			$this->assign("prefix", $this->prefix);

			$this->prefix_betime = $this->prefix."_betime";
			$this->assign("prefix_betime", $this->prefix_betime);

			$this->prefix_endtime = $this->prefix."_endtime";
			$this->assign("prefix_endtime", $this->prefix_endtime);

			$this->prefix_user = $this->prefix."_user";
			$this->assign("prefix_user", $this->prefix_user);

			$this->prefix_pwd = $this->prefix."_pwd";
			$this->assign("prefix_pwd", $this->prefix_pwd);

			$this->prefix_givepaytype = $this->prefix."_givepaytype";
			$this->assign("prefix_givepaytype", $this->prefix_givepaytype);

			$this->prefix_note = $this->prefix."_note";
			$this->assign("prefix_note", $this->prefix_note);

			$this->prefix_company = $this->prefix."_company";
			$this->assign("prefix_company", $this->prefix_company);

			$this->prefix_status = $this->prefix."_status";
			$this->assign("prefix_status", $this->prefix_status);

			$this->cate_num = "cate".$cate_num;
			$this->assign("cate_num", $this->cate_num);

			$num=0;
			$year=date("Y",time());
			$month=date("m",time());
			$date = date_create();

			for($j=24;$j>=1;$j--){
				date_date_set ( $date , $year , $month + $j - 20, 1 );
				$mdate[$num++]=$date->format('Y')."".$date->format('m');
				
			}
			$this->assign("mdate",$mdate);
		} 

		public function index(){	
			$see_all_column = strtolower($this->controller)."_all";
			$acc=D('access')->field($see_all_column)->where('id='.session('accessId'))->select()[0];

			$where="crm_contract.cate=3 and";
			$order=$this->prefix_endtime." asc";
			if($_GET[$this->prefix_status]=="")
				$_GET[$this->prefix_status]="0";
			if(!isset($_GET['date']))$_GET['date']="1";
			foreach($_GET as $key =>$vo){
				if($vo!=""){
					if( $key!="p" && $key!="order" && $key!=$this->prefix_endtime && $key!="name" && $key!="date"){
						$where.=" `".$key."` like '%".$vo."%' and ";
					}else if($key=="order"){//排序
						$order="`".$vo."` desc";
					}else if($key==$this->prefix_endtime){//日期
						$select_date = $vo."01";
						$where.= "`".$key."` >=".strtotime($select_date)." and `".$key."` <=".strtotime(Common::getCurMonthLastDay($select_date))." and ";
					}else if($key=="name"){
						$vo_name1 = str_replace ('台','臺',$vo);
						$vo_name2 = str_replace ('臺','台',$vo);
						$where.=' (
									(`crm_crm`.`name` like "%'.$vo_name1.'%" or `crm_crm`.`nick` like "%'.$vo_name1.'%") or 
									(`crm_crm`.`name` like "%'.$vo_name2.'%" or `crm_crm`.`nick` like "%'.$vo_name2.'%") or 
									h_url like "%'.$vo.'%" or 
									'.$this->prefix_user.' like "%'.$vo.'%" or 
									sn like "%'.$vo.'%" or 
									'.$this->prefix_note.' like "%'.$vo.'%" 
								  ) and ';
					}else if($key=="date"){
						if($vo=="1"){
							//$where.=$this->prefix_endtime." >".time()." and";
						}else{
							$where.=$this->prefix_endtime." <".time()." and";
							$order=$this->prefix_endtime." desc";
						}
					}
				}
			}
			if($_SESSION['teamid']== self::$top_teamid || $acc[$see_all_column] == 1 || $_SESSION['adminid']==self::$top_adminid){ /*在總管理組 或 是有看全部權限 或是admin帳號*/
				$in=" true";
			}else{
				$in =" ( did in(".session('childeid').") or wid in(".session('childeid').") or sid in(".session('childeid').") or hid1 in(".session('childeid').")
				or hid2 in(".session('childeid').") or hid3 in(".session('childeid').") )";
			}
			// dump($where);dump($in);exit;

			$count=$this->db->join("left join crm_contract on {$this->dbname}.pid=crm_contract.id")
							->join("left join crm_crm on crm_contract.cid=crm_crm.id")
							->where('crm_contract.'.$this->cate_num.' = 1  and '.$where.$in)->count();
			$Page       = new \Think\Page($count,50);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show       = $Page->show();// 分页显示输出
			//$sql="select c.*,d.*,e.name as ename,u.name as uname,d.h_url as url,c.flag as flag,d.".$this->prefix_note." as bz,c.id as caseid,c.cid as cid,d.id as id from crm_contract c left join crm_crm_view e on c.cid=e.id left join eip_user u on c.eid=u.id left join crm_contract_host d on (c.id=d.pid $where1) where c.cate=3 and c.'.$this->cate_num.'=1 $where order by c.id desc limit ".($page-1)*PAGE_LIST.','.PAGE_LIST;
			
			$crm_contract_host=$this->db->field("*,{$this->dbname}.id as hostid, crm_contract.cid as cumid, e.name as user_name")
			->join("left join crm_contract on crm_contract_host.pid=crm_contract.id")
			->join("left join crm_crm on crm_contract.cid=crm_crm.id")
			->join("left join eip_user e on e.id=crm_contract.eid")
			//->where($where." crm_contract.cate2 != 1 and".$in)
			->where('crm_contract.'.$this->cate_num.' = 1  and '.$where.$in)
			->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
			
			foreach($crm_contract_host as $key => $vo){
				$crm_contract_host[$key]['show_name'] = CustoHelper::get_crm_show_name($vo['cumid']);

				if($vo[$this->prefix_endtime] <= (time() + 60*60*24*30) && $vo[$this->prefix_endtime] != -28800 && $vo[$this->prefix_endtime] != 0 ){ 
					//  && $vo[$this->prefix_endtime] >= time()
					$crm_contract_host[$key]['color'] = 1;
				}else{
					$crm_contract_host[$key]['color'] = 0;
				}
			}
			
			$crm_cum_level=D('crm_cum_level')->select();
			parent::index_set('eip_role_status');
			parent::index_set('eip_user');
			parent::index_set('eip_pay_type');

			if(CONTROLLER_NAME == 'Server' || CONTROLLER_NAME == 'Ssl'){
				parent::index_set('crm_hostcompany','true',"nick", true, null, 'provider');
			}else{
				parent::index_set('crm_domaincompany','true',"nick", true, null, 'provider');
			}

			$this->assign("page",$_GET['p']);
			$this->assign("data",$crm_contract_host);
			$this->assign("crm_cum_level",$crm_cum_level);
			$this->assign("show",$show);
			$this->display("Servertemplate/index");
		}
		public function view()
		{
			if(!isset($_GET['id']))$this->redirect(CONTROLLER_NAME.'/index');
			$crm_contract_host=$this->db->field('crm_contract_host.*,crm_crm.*,crm_crm.id as crm_id,crm_hostcompany.*,crm_contract.*')
										->join("left join crm_contract on {$this->dbname}.pid=crm_contract.id")
										->join("left join crm_crm on crm_contract.cid=crm_crm.id")
										->join("left join crm_hostcompany on crm_contract_host.h_company=crm_hostcompany.id")
										->where("{$this->dbname}.id={$_GET['id']}")
										->select();
			if($crm_contract_host[0]){
				$crm_contract_host[0]['show_name'] = CustoHelper::get_crm_show_name($crm_contract_host[0]['crm_id']);
			}
			//總計資料
			//dump(D()->getLastSql());

			//月份資料
			$num=0;
			$year=date("Y",time());
			for($j=date("m",time());$j>=1;$j--){
				$mdate[$num++]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
				
			}
			for($i=12;$i>=1;$i--){
				$mdate[$num++]=($year-1)."".str_pad($i,2,'0',STR_PAD_LEFT);
				
			}
			//dump($crm_domaincompany);
			parent::index_set('crm_cum_flag');
			parent::index_set('eip_user');
			parent::index_set('crm_domaincompany','true',"nick");
			parent::index_set('crm_hostcompany','true',"nick");
			parent::index_set('eip_pay_type');
			$this->assign("page",$_GET['p']);
			$this->assign("mdate",$mdate);
			//dump($crm_contract_host[0]);
			$this->assign("data",$crm_contract_host[0]);

			$crm_cum_level=D('crm_cum_level')->select();
			$this->assign("crm_cum_level",$crm_cum_level);
			$this->assign("show",$show);
			$this->display("Servertemplate/view");
		}
		public function editor()
		{
			if(!isset($_GET['id']))$this->redirect(CONTROLLER_NAME.'/index');
			$crm_contract_host=$this->db->field('crm_contract_host.*,crm_crm.*,crm_crm.id as crm_id,crm_hostcompany.*,crm_contract.*')
										->join("left join crm_contract on {$this->dbname}.pid=crm_contract.id")
										->join("left join crm_crm on crm_contract.cid=crm_crm.id")
										->join("left join crm_hostcompany on crm_contract_host.".$this->prefix."_company=crm_hostcompany.id")
										->where("{$this->dbname}.id={$_GET['id']}")
										->select();
			if($crm_contract_host[0]){
				$crm_contract_host[0]['show_name'] = CustoHelper::get_crm_show_name($crm_contract_host[0]['crm_id']);
			}
			//總計資料
			//dump($crm_contract_host);

			//月份資料
			$num=0;
			$year=date("Y",time());
			for($j=date("m",time());$j>=1;$j--){
				$mdate[$num++]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
				
			}
			for($i=12;$i>=1;$i--){
				$mdate[$num++]=($year-1)."".str_pad($i,2,'0',STR_PAD_LEFT);
				
			}
			$crm_cum_level=D('crm_cum_level')->select();
			
			parent::index_set('crm_cum_flag');
			parent::index_set('eip_user');
			parent::index_set('eip_pay_type');
			parent::index_set('crm_hostcompany','true',"nick");
			parent::index_set('crm_domaincompany','true',"nick");
			$this->assign("page",$_GET['p']);
			$this->assign("mdate",$mdate);
			$this->assign("data",$crm_contract_host[0]);
			$this->assign("crm_cum_level",$crm_cum_level);
			$this->assign("show",$show);
			$this->display("Servertemplate/editor");
		}
		
		public function dns()
		{
			$dns = M("crm_hostcompany")->where("id = '".$_POST['id']."'")->select();
			$dns = [
				'dns1' => $dns[0]['dns1'],
				'ip1' => $dns[0]['ip1'],
				'dns2' => $dns[0]['dns2'],
				'ip2' => $dns[0]['ip2'],
			];
			$this->ajaxReturn($dns);
		}
		
		function dosave(){
			$id=$_POST['id'];
			unset($_POST['id']);
			if(isset($_POST['h_betime']))$_POST['h_betime']=strtotime($_POST['h_betime']);
			if(isset($_POST['h_endtime']))$_POST['h_endtime']=strtotime($_POST['h_endtime']);
			if(isset($_POST['d_betime']))$_POST['d_betime']=strtotime($_POST['d_betime']);
			if(isset($_POST['d_endtime']))$_POST['d_endtime']=strtotime($_POST['d_endtime']);
			if(isset($_POST['s_betime']))$_POST['s_betime']=strtotime($_POST['s_betime']);
			if(isset($_POST['s_endtime']))$_POST['s_endtime']=strtotime($_POST['s_endtime']);
			if($this->db->where("id=".$id)->data($_POST)->save()){
				$this->success("更新成功",U(CONTROLLER_NAME.'/view')."?id=".$id);
			}else{
				$this->error("更新失敗");
			}
		}
		
		//批次處理
		public function patchupdate()
		{
			//dump($_POST);exit;
			foreach($_POST['sele'] as $vo){
				$this->db->where('id='.$vo)->data($_POST)->save();
			}
			$this->success('更新成功');
		}

		public function get_data(){
			$sql = "
		 		SELECT hostcompany.nick, hostcompany.url, hostcompany.dnsuser, hostcompany.dnspwd, hostcompany.id
		 		FROM crm_hostcompany AS hostcompany
		 		WHERE hostcompany.status = 1
		 	";
			
			$result = D()->query($sql);
			echo json_encode($result, JSON_UNESCAPED_UNICODE);
		}
	}
?>		