<?php
	namespace Trade\Controller;
	use Think\Controller;
	class SeoController extends GlobalController 
	{
		function _initialize(){
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$this->db=D('crm_contract_host');
			$this->dbname="crm_contract_host";
			//月份資料
			$num=0;
			$year=date("Y",time());
			for($j=date("m",time());$j>=1;$j--){
				$mdate[$num++]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
				
			}
			for($i=12;$i>=1;$i--){
				$mdate[$num++]=($year-1)."".str_pad($i,2,'0',STR_PAD_LEFT);
				
			}
			$this->assign("mdate",$mdate);
			//分類
			$gcs=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
			$num=1;
			foreach($gcs as $key=>$vo){
				for($i=1;$i<=3;$i++){
					$gcsele[$num++]=$vo.$i;
				}
			}
			$lastday = date("Ymd",strtotime(date("Ym")."01 -1 day")); 
			
			//这个不需要解释吧，直接把第一天设为1号
			$firstday=date("Ymd",strtotime(date("Ym",strtotime($lastday))."01")) ; 
			//这个语法以前还真没见过，php manual http://www.php.net/manual/zh/datetime.formats.relative.php

			$count=D("crm_key_rank")->where(" `update`>='{$firstday}' and `update`<='{$lastday}'")->count();
			//dump($count);
			if($count!=0){
				
				$newdb="seo_rank_".substr($firstday,0,6);
				$olddb="crm_key_rank";
				$create="CREATE TABLE {$newdb} LIKE {$olddb}";
				M()->db(1,"DB_SEO_RANK")->query($create);
				$crm_key_rank=D("crm_key_rank")->where(" `update`>='{$firstday}' and `update`<='{$lastday}'")->select();
				
				
				//dump($crm_key_rank);
				
				foreach($crm_key_rank as $key=>$vo){
					unset($vo['id']);
					M()->db(1,"DB_SEO_RANK")->table($newdb)->data($vo)->add();
					M()->db(1,"DB_SEO_RANK")->table("crm_key_copy")->data($vo)->add();
				}
				D("crm_key_rank")->where(" `update`>='{$firstday}' and `update`<='{$lastday}'")->delete();
			}
			$this->assign("gcs",$gcsele);

			$this->assign('page_title', 'SEO總表');
			$this->assign('page_title_link_self', U('Seo/index'));
			$this->assign('page_title_active', 65);  /*右上子選單active*/
		} 
		
		public function index(){
			$acc = parent::get_my_access();

			$firstday = date('Y-m-01', time()); 
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); 
			$db="crm_key_rank";
			$where="";
			$where1=" and r.`update`>='".date("Y-m-d",strtotime("-".date("d",time())." day"))."' and r.`update`<='".date("Y-m-d")."'";
			$now=true;
			foreach($_GET as $key =>$vo){
				if($vo!="")
				if( $key!="p" && $key!="order" && $key!="update" && $key!="key_name" && $key!="gcsele"){
					$where.=" `{$key}` like '%{$vo}%' and ";
					}else if($key=="gcsele"){//等級
					$where.=" `{$key}` = '{$vo}' and ";
					}else if($key=="order"){//排序
					$order="`".$vo."` desc";
					}else if($key=="update"){//日期
					
					if($vo!=date("Ym")){
						$now=false;
						$db="seo_rank_".$vo;
					}
					$firstday = date('Y-m-01', strtotime($vo."15")); 
					$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); 
					
					$where1=" and r.`update`>='".$firstday."' and r.`update`<='".$lastday."'";
					
					}else if($key=="key_name"){
						if(false !==(strpos($vo,'台'))||false !==(strpos($vo,'臺'))){
								$vo_name1 = str_replace ('台','臺',$vo);
								$vo_name2 = str_replace ('臺','台',$vo);
								$where.=" crm_seo_key.url1 like '%{$vo}%' or 
								(`key_name` like '%".$vo_name1."%' or `key_name` like '%".$vo_name2."%') or 
								(`crm_crm`.`name` like '%".$vo_name1."%' or `crm_crm`.`nick` like '%".$vo_name1."%') or 
								(`crm_crm`.`name` like '%".$vo_name2."%' or `crm_crm`.`nick` like '%".$vo_name2."%') and";
							}else{
								$where.=" (crm_seo_key.url1 like '%{$vo}%' or `key_name` like '%{$vo}%' or
								 `crm_crm`.`name` like '%{$vo}%' or
								 `crm_crm`.`nick` like '%{$vo}%'
								) and";
							}					
				}
				
			}
			if($_SESSION['teamid']== self::$top_teamid || $acc['seo_all'] == 1){
				$in=" true";
			}else{
				$in =" ( did in(".session('childeid').") or wid in(".session('childeid').") or sid in(".session('childeid').") or hid1 in(".session('childeid').")
				or hid2 in(".session('childeid').") or hid3 in(".session('childeid').") )";
			}
			$count=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")->where($where.$in)->count();

			
			$Page =new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			
			$Page->setConfig('last',"最後一頁 %END% ");
			$show = $Page->show();// 分页显示输出
			
			$total_num=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")->where($where.$in)->count();
			$this->assign("total_num",$total_num);

			$keylist=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")
			// ->join("crm_contract on crm_seo_key.contract_id=crm_contract.id")
			->where($where.$in)
			->limit($Page->firstRow.','.$Page->listRows)
			->order('crm_seo_key.contract_id desc, crm_seo_key.engine asc, crm_seo_key.id asc')->select();
			//dump($keylist);

			foreach($keylist as $k=>$v)
			{
				if($v['id']!="")
				{
					$sql="select r.key_ranking,r.update from {$db} r 
					where r.key_Id=".$v['id']."  ";
					//dump($sql);
					
					if($now){
						$keylist[$k]['rank']=D()->query($sql);
						
						}else{
						
						$m=M();
						$keylist[$k]['rank']=$m->db(1,"DB_SEO_RANK")->query($sql);
					}

					foreach($keylist[$k]['rank'] as $key=>$vo){
						$keylist[$k]['rank'][$vo['update']]=$vo['key_ranking'];
					}	
				}
			}
			//本月資料
			$num=0;
			$year=date("Y-m-",strtotime($lastday));
			$sql="select r.key_ranking,r.update,r.customers_Name from crm_key_rank r 
			where r.key_Id=".$v['id']." $where1 order by r.update asc";
			//D("crm_key_rank")->where("`update`<'2016-06-01'")->delete();
			
			for($j=1;$j<=date("d",strtotime($lastday));$j++){
				
				$ddate[$j]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
				
			}
			$this->assign("page",$_GET['p']);
			$this->assign("data",$keylist);
			$this->assign("show",$show);
			$this->assign("ddate",$ddate);
			$this->display();
		}
		public function data(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');

			$firstday = date('Y-m-01', time()); 
			//这个语法以前还真没见过，php manual http://www.php.net/manual/zh/datetime.formats.relative.php
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); 
			$db="crm_key_rank";
			$where="";
			$where1=" and r.`update`>='".date("Y-m-d",strtotime("-".date("d",time())." day"))."' and r.`update`<='".date("Y-m-d")."'";
			$now=true;
			foreach($_POST as $key =>$vo){
				if($vo!="")
				if( $key!="p" && $key!="order" && $key!="update" && $key!="key_name"){
					$where.=" `{$key}` like '%{$vo}%' and ";
					}else if($key=="order"){//排序
					$order="`".$vo."` desc";
					}else if($key=="update"){//日期
					
					if($vo!=date("Ym")){
						$now=false;
						$db="seo_rank_".$vo;
					}
					$firstday = date('Y-m-01', strtotime($vo."15")); 
					$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); 
					
					$where1=" and r.`update`>='".$firstday."' and r.`update`<='".$lastday."'";
					
					}else if($key=="key_name"){
					$where.=" crm_seo_key.url1 like '%{$vo}%' or `key_name` like '%{$vo}%' or `crm_crm`.`name` like '%{$vo}%' and";
					
				}
				
			}
			
			$count=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")->where($where." true")->count();

			
			$Page =new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('myurl','/Seo/index/');
			$Page->setConfig('first',"第一頁");
			
			$Page->setConfig('last',"最後一頁 %END% ");
			$show       = $Page->show();// 分页显示输出
			
			$keylist=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")->where($where." true")
			->limit($Page->firstRow.','.$Page->listRows)->select();
			//dump($keylist);
			foreach($keylist as $k=>$v)
			{
				if($v['id']!="")
				{
					$sql="select r.key_ranking,r.update from {$db} r 
					where r.key_Id=".$v['id']." $where1 ";
					//dump($sql);
					
					if($now){
						$keylist[$k]['rank']=D()->query($sql);
						
						}else{
						
						$m=M();
						$keylist[$k]['rank']=$m->db(1,"DB_SEO_RANK")->query($sql);
					}

						foreach($keylist[$k]['rank'] as $key=>$vo){
							$keylist[$k]['rank'][$vo['update']]=$vo['key_ranking'];
							
						}
						
				}
			}
			//本月資料
			$num=0;
			$year=date("Y-m-",strtotime($lastday));
			$sql="select r.key_ranking,r.update,r.customers_Name from crm_key_rank r 
			where r.key_Id=".$v['id']." $where1 order by r.update asc";
			//D("crm_key_rank")->where("`update`<'2016-06-01'")->delete();
			
			for($j=1;$j<=date("d",strtotime($lastday));$j++){
				
				$ddate[$j]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
				
			}
			$this->assign("page",$_GET['p']);
			$this->assign("data",$keylist);
			$this->assign("show",$show);
			$this->assign("ddate",$ddate);
			$this->display();
			
		}

		//改資料ajax
		public function aj_chcontent(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');

			if(isset($_POST['dbname']) && isset($_POST['row'])){
				$data[$_POST['row']]=$_POST['data'];
				D($_POST['dbname'])->data($data)->where("id='{$_POST['id']}'")->save();
				parent::error_log("修改".$_POST['dbname']."欄位".$_POST['row']."資料列:{$_POST['id']}的資料{$_POST['data']}");
			}
		}

		/*Api:取得關鍵字清單(google、yahoo查排名用)*/
		public function get_rank_data(){
			$web_type = $_GET['web_type'];
			// $web_type = 'yahoo(台灣)';
			// $web_type = 'google(台灣)';

			if($web_type != 'yahoo(台灣)' and $web_type !='google(台灣)'){
				echo 'not allowed';
				return ;
			}

			$sql = "
					SELECT DISTINCT contract.id, 
					seokey.url1, seokey.key_name, seokey.engine, 
					crm.name 
					FROM crm_contract_seo AS seo 
					    INNER JOIN crm_contract AS contract 
					            ON seo.pid = contract.id 
					            AND contract.flag <> 3 
					            AND contract.flag <> 5 
					            AND contract.flag2 <> 3 
					    INNER JOIN crm_seo_key AS seokey 
					            ON contract.id = seokey.contract_id 
					    INNER JOIN crm_crm AS crm 
					            ON contract.cid = crm.id 
					WHERE seokey.engine = '".$web_type."' 
					ORDER BY contract.id ";

			$result = D()->query($sql);
			echo json_encode($result);

			// $this->ajaxReturn(  );

		}
	}
?>																				
