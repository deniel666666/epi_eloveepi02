<?php
	namespace Trade\Controller;
	use Think\Controller;
	class SeotoController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			parent::index_set('crm_cum_pri','id=1');
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
			$this->assign("gcs",$gcsele);

			$this->assign('page_title', 'SEO分析');
			$this->assign('page_title_link_self', U('Seoto/index'));
			$this->assign('page_title_active', 66);  /*右上子選單active*/
		} 
		
		public function index()
		{	
			$order = ' key_ranking asc, searchEngine asc, id asc';
			$now=true;
			$where="";
			$date=date("Y-m-d");
			$db="crm_key_rank";
			$month=date("Y-m-");
			//dump($_GET);
			foreach($_GET as $key =>$vo){
				if($vo!="")
				if( $key!="p" && $key!="gcsele" && $key!="update" && $key!="key_name" && $key!="todate" && $key!="r"){
					$where.=" `{$key}` like '%{$vo}%' and ";
					}else if($key=="gcsele"){//排序
					$where1.=" `{$key}` like '%{$vo}%' and ";
					}else if($key=="update"){//日期
					
					if($vo!=date("Ym")){
						$now=false;
						$db="seo_rank_".$vo;
					}
					$date=date("Y-m-d",strtotime($vo."01"));
					$month=date("Y-m-",strtotime($vo."01"));
					}else if($key=="key_name"){
					$where.="( customers_name like '%{$vo}%' or `key_name` like '%{$vo}%') and";
					
					}else if($key=="todate"){
					if(substr($vo,0,7)!=date("Y-m")){
						$now=false;
						$db="seo_rank_".str_replace("-","",substr($vo,0,7));
					}
					$date=$vo;
					$month=substr($vo,0,8);
				}
				
			}
			//dump($where);
			//挑出合約進行中
			$acc = parent::check_all_access(CONTROLLER_NAME); //檢查看全部or指定瀏覽的
			if($_SESSION['teamid']== self::$top_teamid || $acc['seoto_all'] == 1)
				$in=" and true";
			else
				$in =" and eid = ".$_SESSION['eid'];
			$crm_seo_key=D("crm_seo_key c")
			->field("c.*,t.*,c.id as id,c.starts as starts")
			->join("left join crm_contract t on c.caseid = t.id")
			->where("$where1 flag2=1".$in)->select();
			foreach($crm_seo_key as $key=>$vo){
				$pkey.=','.$vo['id'] ;
				$crm_seo[$vo['id']]=$vo['starts'];
				//對齊資料
				$newcont[$vo['id']]=$vo;
			}
			//做索引用
			$sqla="select * from {$db} where  $where key_id in(''$pkey) order by {$order}";
			$sqlt="select * from {$db} where  $where key_ranking <>'1001' and `update`='$date' and  key_id in(''$pkey) order by {$order}";
			//dump($sqlt);
			if($now){
				$keylist=D()->query($sqla);
				$res=D()->query($sqlt);
				
				}else{
				
				$m=M();
				$keylist=$m->db(1,"DB_SEO_RANK")->query($sqla);
				$res=$m->db(1,"DB_SEO_RANK")->query($sqlt);
				
			}
			//dump($sqlt);
			$total_num=count($res);
			$this->assign("total_num",$total_num);
			
			$todaygg=0;
			$todaydc=0;
			$today_sun=0;
			//dump($keylist);
			foreach( $res as $k => $v ) {
				switch( $crm_seo[$v['key_id']]) {
					case 1:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 10 ) ) {unset($res[$k]);} break;
					case 12:	if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 20 ) ) {unset($res[$k]);} break;
					case 2:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 30 ) ) {unset($res[$k]);} break;
					case 3:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 3 ) )  {unset($res[$k]);} break;
					case 4:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 5 ) )  {unset($res[$k]);} break;
					case 7:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 5 ) )  {unset($res[$k]);} break;
					case 5:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 10 ) ) {unset($res[$k]);} break;
					case 6:		if( !( $v['key_ranking'] >= 6 && $v['key_ranking'] <= 10 ) ) {unset($res[$k]);} break;
				}
			} 
			
			$yesterday_date = date("Y-m-d", strtotime($date." -1 day")) ;
			//dump($res);
			foreach($res as $k => $v) {
				$in_v2 .= ','.$v['key_id'] ;
			}
			//dump($in_v2);
			$sql="select * from {$db} where  $where `update`='$yesterday_date' and `key_id` in(''$in_v2) order by {$order}";
			
			if($now){
				$keylisty=D()->query($sql);
			}else{
				$m=M();
				$keylisty=$m->db(1,"DB_SEO_RANK")->query($sql);
			}
			$res_v2=$keylisty;
			//dump($res_v2);
			//======================= START 計算今天上榜開始 ====================================================================  
			
			foreach( $res_v2 as $k => $v ) {
				switch( $crm_seo[$v['key_id']] ) {
					case 1:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 10 ) )  break; {unset($res_v2[$k]);}
					case 12:	if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 20 ) )  break; {unset($res_v2[$k]);}
					case 2:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 30 ) )  break; {unset($res_v2[$k]);}
					case 3:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 3  ) )  break; {unset($res_v2[$k]);}
					case 4:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 5  ) )  break; {unset($res_v2[$k]);}
					case 7:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 5  ) )  break; {unset($res_v2[$k]);}
					case 5:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 10 ) )  break; {unset($res_v2[$k]);}
					case 6:		if( !( $v['key_ranking'] >= 6 && $v['key_ranking'] <= 10 ) )  break; {unset($res_v2[$k]);}
				}
			}
			//======================= START 計算今天掉落 ====================================================================
			
			$sql="select * from {$db} where $where `update`='$yesterday_date' and `key_id` in(''$pkey) order by {$order}";
			if($now){
				$res_v3=D()->query($sql);
				
				}else{
				
				$m=M();
				$res_v3=$m->db(1,"DB_SEO_RANK")->query($sql);
			}
			//dump($res_v3);
			foreach( $res_v3 as $k => $v ) {
				switch( $crm_seo[$v['key_id']]  ) {
					case 1:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 10 ) ) {unset($res_v3[$k]);} break; 
					case 12:	if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 20 ) ) {unset($res_v3[$k]);} break; 
					case 2:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 30 ) ) {unset($res_v3[$k]);} break; 
					case 3:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 3  ) ) {unset($res_v3[$k]);} break; 
					case 4:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 5  ) ) {unset($res_v3[$k]);} break; 
					case 7:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 5  ) ) {unset($res_v3[$k]);} break; 
					case 5:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 10 ) ) {unset($res_v3[$k]);} break; 
					case 6:		if( !( $v['key_ranking'] >= 6 && $v['key_ranking'] <= 10 ) ) {unset($res_v3[$k]);} break; 
				}
			}
			foreach($res_v3 as $k => $v){
				$in_v4 .= ','.$v['key_id'] ;
			}    
			
			
			$sql="select * from {$db} where  $where `update`='$date' and `key_id` in(''$in_v4) order by {$order}";
			
			if($now){
				$res_v4=D()->query($sql);
				
				}else{
				
				$m=M();
				$res_v4=$m->db(1,"DB_SEO_RANK")->query($sql);
			}
			//dump(count($res_v4));
			foreach( $res_v4 as $k => $v ) {
				switch( $crm_seo[$v['key_id']]  ) {
					case 1:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 10 ) )  break; {unset($res_v4[$k]);}
					case 12:	if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 20 ) )  break; {unset($res_v4[$k]);}
					case 2:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 30 ) )  break; {unset($res_v4[$k]);}
					case 3:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 3  ) )  break; {unset($res_v4[$k]);}
					case 4:		if( !( $v['key_ranking'] >= 1 && $v['key_ranking'] <= 5  ) )  break; {unset($res_v4[$k]);}
					case 7:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 5  ) )  break; {unset($res_v4[$k]);}
					case 5:		if( !( $v['key_ranking'] >= 4 && $v['key_ranking'] <= 10 ) )  break; {unset($res_v4[$k]);}
					case 6:		if( !( $v['key_ranking'] >= 6 && $v['key_ranking'] <= 10 ) )  break; {unset($res_v4[$k]);}
				}
			}
			//dump(count($res_v4));
			//======================= START 計算今天操作達成開始 rankstate =1 ======================================================  
			
			//挑出合約進行中
			$crm_seo_key=D("crm_seo_key c")->field("c.*")->join("left join crm_contract t on c.caseid = t.id")->where("$where1 flag<>3 and c.rankstate =1")->select();
			foreach($crm_seo_key as $key=>$vo){
				$rankstate.=','.$vo['id'] ;
			}
			
			$sql="select * from {$db} where  $where `update`='$date' and  key_id in(''$rankstate) order by {$order}";
			if($now){
				$res_v5=D()->query($sql);
			}else{
				
				$m=M();
				$res_v5=$m->db(1,"DB_SEO_RANK")->query($sql);
			}
			//dump($res_v5);
			foreach( $res_v5 as $k => $v ) {
				// dump($crm_seo[$v['key_id']]);
				switch($crm_seo[$v['key_id']]) {
					case 1:		if( !( (int)$v['key_ranking'] >= 1 && (int)$v['key_ranking'] <= 10 ) ) {unset($res_v5[$k]);} break;
					case 12:	if( !( (int)$v['key_ranking'] >= 1 && (int)$v['key_ranking'] <= 20 ) ) {unset($res_v5[$k]);} break;
					case 2:		if( !( (int)$v['key_ranking'] >= 1 && (int)$v['key_ranking'] <= 30 ) ) {unset($res_v5[$k]);} break;
					case 3:		if( !( (int)$v['key_ranking'] >= 1 && (int)$v['key_ranking'] <= 3 ) ) {unset($res_v5[$k]);} break;
					case 4:		if( !( (int)$v['key_ranking'] >= 1 && (int)$v['key_ranking'] <= 5 ) ) {unset($res_v5[$k]);} break;
					case 7:		if( !( (int)$v['key_ranking'] >= 4 && (int)$v['key_ranking'] <= 5 ) ) {unset($res_v5[$k]);} break;
					case 5:		if( !( (int)$v['key_ranking'] >= 4 && (int)$v['key_ranking'] <= 10 ) ) {unset($res_v5[$k]);} break;
					case 6:		if( !( (int)$v['key_ranking'] >= 6 && (int)$v['key_ranking'] <= 10 ) ) {unset($res_v5[$k]);} break;
					default:	unset($res_v5[$k]);break;

				}
			} 
			
			for($i=1;$i<=31;$i++)$dis[$i]=$month.str_pad($i, 2, "0", STR_PAD_LEFT);
			
			$sk=0;
			//統計月字組
			
			foreach($keylist as $key =>$vo){
				foreach($dis as $keyd=>$vod){
					
					if($vod==$vo['update']){
						if($monnum[$vod]==null)$monnum[$vod]=0;
						$monnum[$vod]=$monnum[$vod]+1;
						break;
					}
					
				}
			}
			//對齊未完成
			$gcs=["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
			$starts=array(1=>'1~10',11=>'11~30',12=>'1~20',2=>'1~30',3=>'1~3',4=>'1~5',5=>'4~10',6=>'6~10',7=>'4~5');
			$num=1;
			foreach($gcs as $key=>$vo){
				for($i=1;$i<=3;$i++){
					$gcsele[$num++]=$vo.$i;
				}
				}/*
				dump($keylistt[99]);
			dump($newcont[2961]);*/
			
			
			switch($_GET['r']){
				case 1:
				$keylistt=$res_v4;
				break;
				case 2:
				$keylistt=$res_v2;
				break;
				case 3:
				$keylistt=$res_v5;
				break;
				default:
				$sqlt="select * from {$db} where 
						$where key_ranking <>'1001' and `update`='$date' and  key_id in(''$pkey)
						order by {$order}";
				if($now){
					$keylistt=D()->query($sqlt);
					
					}else{
					
					$m=M();
					$keylistt=$m->db(1,"DB_SEO_RANK")->query($sqlt);
					
				}
				
				
				
			}
			//dump($sqlt);
			
			
			
			
			parent::index_set('eip_user');
			$this->assign("newcont",$newcont);
			$this->assign("starts",$starts);
			$this->assign("gcsele",$gcsele);
			$this->assign("list",$keylistt);
			$this->assign("dis",$dis);
			$this->assign("monnum",$monnum);
			$this->assign("today_sun",count($res_v5));
			$this->assign("todaygg",count($res_v4));
			$this->assign("todaydc",count($res_v2));
			$this->assign("date",$date);
			$this->assign("today",date("Y",strtotime($date))."年".date("m",strtotime($date))."月");
			$this->display();
		}
		public function data(){
			
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
					$where.=" url1 like '%{$vo}%' or `key_name` like '%{$vo}%' or `crm_crm`.`name` like '%{$vo}%' and";
					
				}
				
			}
			$count=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")->where($where." true")->count();
			
			$Page =new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show       = $Page->show();// 分页显示输出
			
			$keylist=D("crm_seo_key")->field("crm_seo_key.id ,crm_seo_key.*,crm_crm.name ")
			->join("crm_crm on crm_seo_key.customers_id=crm_crm.id")->where($where." true")
			->limit($Page->firstRow.','.$Page->listRows)->select();
			
			foreach($keylist as $k=>$v)
			{
				if($v['id']!="")
				{
					$sql="select r.key_ranking,r.update from {$db} r 
					where r.key_id=".$v['id']." $where1 ";
					//dump($sql);
					
					if($now){
						$keylist[$k]['rank']=D()->query($sql);
						
						}else{
						
						$m=M();
						$keylist[$k]['rank']=$m->db(1,"DB_SEO_RANK")->query($sql);
					}
					if(empty($keylist[$k]['rank']))
					{
						unset($keylist[$k]);
					}
					else
					{
						foreach($keylist[$k]['rank'] as $key=>$vo){
							$keylist[$k]['rank'][$vo['update']]=$vo['key_ranking'];
							
						}
					}
				}
			}
			//本月資料
			$num=0;
			$year=date("Y-m-",strtotime($lastday));
			$sql="select r.key_ranking,r.update,r.customers_Name from crm_key_rank r 
			where r.key_id=".$v['id']." $where1 order by r.update asc";
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
	}
	
	
?>																																																																																					