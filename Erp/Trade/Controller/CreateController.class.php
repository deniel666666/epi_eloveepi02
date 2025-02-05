<?php
	/**
		* 資料庫控制管理
	*/
	namespace Trade\Controller;
	use Think\Controller;
	
	class CreateController extends GlobalController {
		function _initialize()
		{
		} 
		function index(){
			
			$this->display();
		}
		function seomoneytoother(){
			
			$crm_seomoney=D("crm_seomoney")->join("left join crm_contract on crm_seomoney.caseid=crm_contract.id")->field("crm_seomoney.*")->where("cate!=1")->select();
			foreach($crm_seomoney as $key=>$vo){
				D("crm_othermoney")->data($vo)->add();
			}
		}
		//權限管理
		function power()
		{
			$datalist=D()->query('select * from powercat where `codenamed`!=""');
			$name="access";
			
			$sql ="create table `{$name}` (
			`id` int(10) not null,			
			`power_id` int(10) not null default '0' comment '權限id',
			`status` int(10) not null default '0' comment '狀態:1正常 0垃圾桶',
			`name` varchar(255) not null default '' comment '名稱'";
			$num=0;
			
			foreach($datalist as $key=>$v){
				/*$sql.=", `".trim($v['codenamed'])."_onl` float  default '0' comment '".$v['title']."個人'";
				$sql.=", `".trim($v['codenamed'])."_all` float  default '0' comment '".$v['title']."全部'";*/
				$sql.=", `".strtolower(trim($v['codenamed']))."_new` float  default '0' comment '".$v['title']."新增'";
				$sql.=", `".strtolower(trim($v['codenamed']))."_red` float  default '0' comment '".$v['title']."讀取'";
				$sql.=", `".strtolower(trim($v['codenamed']))."_edi` float  default '0' comment '".$v['title']."修改'";
				$sql.=", `".strtolower(trim($v['codenamed']))."_hid` float  default '0' comment '".$v['title']."假刪除'";
				$sql.=", `".strtolower(trim($v['codenamed']))."_del` float  default '0' comment '".$v['title']."真刪除'";
				$num++;
			}
			
			$sql.=")";
			
			$data=D($name)->select();
			M()->execute("DROP TABLE `{$name}`");
			M()->execute($sql);
			M()->execute("alter table `{$name}` add primary key (`id`), add unique key `id` (`id`);");
			M()->execute("alter table `{$name}` modify `id` int(10) not null auto_increment;");
			foreach($data as $vo){
				
				D($name)->data($vo)->add();
			}
			
		}
		
		//問卷分類分割
		function ques()
		{
			$name="ques";
			$sql ="create table `{$name}` (
			`id` int(10) not null,
			`name` text not null default '' comment '名稱',
			`description` text not null default '' comment '說明'";
			$num=0;
			for($i=0;$i<=10;$i++){
				
				$sql.=", `opt_".$i."_type` text null comment '".$i."類型'";
				$sql.=", `opt_".$i."_title` text null comment '".$i."標題'";
				$sql.=", `opt_".$i."_val` text null comment '".$i."內容'";
				
			}
			$sql.=")";
			
			//M()->execute("DROP TABLE `{$name}`");
			M()->execute($sql);
			M()->execute("alter table `{$name}` add primary key (`id`), add unique key `id` (`id`);");
			M()->execute("alter table `{$name}` modify `id` int(10) not null auto_increment;");
			$data->power_id="2";
			$data->name="管理員";
			
			D($name)->data($data)->add();
			
		}
		function ques_back()
		{
			$name="ques_back";
			$sql ="create table `{$name}` (
			`id` int(10) not null,
			`q_id` int(10) not null comment '問題id',
			`no` text not null default '' comment '統一編號'";
			$num=0;
			for($i=0;$i<=10;$i++){
				
				$sql.=", `opt_".$i."_val` text null comment '".$i."內容'";
				
			}
			$sql.=", `mom` text null comment '".$i."備註')";
			
			//M()->execute("DROP TABLE `{$name}`");
			M()->execute($sql);
			M()->execute("alter table `{$name}` add primary key (`id`), add unique key `id` (`id`);");
			M()->execute("alter table `{$name}` modify `id` int(10) not null auto_increment;");
		}
		
		function eve_events()
		{
			/*for($i=3;$i<=5;$i++){
				$query="ALTER TABLE `eve_events` ADD `step_id_{$i}` INT(10) NULL AFTER `user_name_".($i-1)."`,
				ADD `code_id_{$i}` INT(10) NULL AFTER `step_id_{$i}`,
				ADD `user_name_{$i}` TEXT NULL AFTER `code_id_{$i}`";
				echo $query;
				echo "<br>";
				//D()->query($query);
			}*/
			
			for($i=1;$i<=5;$i++){
				$query="ALTER TABLE `eve_events` ADD `user_id_{$i}` INT(10) NULL AFTER `code_id_{$i}`";
				echo $query."<br>";
				//@D()->query($query);
				
			}
		}
		function seo_date(){
			for($i=1;$i<=12;$i++){
				//计算本月第一天和最后一天
				$today="2014".str_pad($i,2,'0',STR_PAD_LEFT)."15";
				//这个不需要解释吧，直接把第一天设为1号
				$firstday = date('Y-m-01', strtotime($today)); 
				//这个语法以前还真没见过，php manual http://www.php.net/manual/zh/datetime.formats.relative.php
				$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); 
				$newdb="seo_rank_".substr($today,0,6);
				$olddb="crm_key_rank";
				$create="CREATE TABLE {$newdb} LIKE {$olddb}";
				$insert="INSERT {$newdb}  SELECT * FROM {$olddb} where `update`>='{$firstday}' and `update`<='{$lastday}'";
				D()->execute($create);
				D()->execute($insert);
			}
		}
		function seo_del(){
			$today=(date("Ymd",strtotime("-1 month")));
			//这个不需要解释吧，直接把第一天设为1号
			$firstday = date('Y-m-01', strtotime($today)); 
			//这个语法以前还真没见过，php manual http://www.php.net/manual/zh/datetime.formats.relative.php
			$lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day")); 
			$count=D("crm_key_rank")->where(" `update`>='{$firstday}' and `update`<='{$lastday}'")->count();
			
			if($count!=0){
				
				$newdb="seo_rank_".substr($today,0,6);
				$olddb="crm_key_rank";
				dump($newdb);
				$create="CREATE TABLE {$newdb} LIKE {$olddb}";
				M()->db(1,"DB_SEO_RANK")->query($create);
				$crm_key_rank=D("crm_key_rank")->where(" `update`>='{$firstday}' and `update`<='{$lastday}'")->select();
				
				
				//dump($crm_key_rank);
				/*
					foreach($crm_key_rank as $key=>$vo){
					unset($vo['id']);
					dump(M()->db(1,"DB_SEO_RANK")->table($newdb)->data($vo)->add());
				}*/
				D("crm_key_rank")->where(" `update`>='{$firstday}' and `update`<='{$lastday}'")->delete();
			}
			
		}
		//分析重整客戶資料
		function anay_cust(){
			
			$business_record=D("business_record")->where("customers_Name=''")->group("customers_Id")->field("customers_Id")->select();
			
			foreach($business_record as $key =>$vo){
				
				$crm_crm=D("crm_crm")->where("id={$vo['customers_id']}")->field("name")->select();
				
				$data['customers_Name']=$crm_crm[0]['name'];
				if($crm_crm[0]['name']!='')
				D("business_record")->where("customers_Id={$vo['customers_id']}")->data($data)->save();
			}
		}
		//分析重整流程資料
		function eve_steps_user_data(){
			
			$eve_steps=D("eve_steps")->where("user_name=''")->group("user_id")->field("user_id")->select();
			
			foreach($eve_steps as $key =>$vo){
				
				$eip_user=D("eip_user")->where("id='{$vo['user_id']}'")->select();
				
				$data['user_name']=$eip_user[0]['name'];
				if($eip_user[0]['name']!='')
				D("eve_steps")->where("user_id={$vo['user_id']}")->data($data)->save();
			}
		}
		//分析重整流程資料
		function eve_events_cc(){
			$frow="caseid";
			$fnull="case_name";
			$erow="sn";
			$fdb="eve_events";
			$eve_events=D($fdb)->where("{$fnull}=''")->group($frow)->field($frow)->select();
			
			foreach($eve_events as $key =>$vo){
				
				$crm_contract=D("crm_contract")->where("id='{$vo[$frow]}'")->select();
				
				$data[$fnull]=$crm_contract[0][$erow];
				if($crm_contract[0]['sn']!='')
				D($fdb)->where("{$frow}={$vo[$frow]}")->data($data)->save();
			}
		}
		//分析重整流程資料
		function eve_events_crm(){
			$frow="cum_id";
			$fnull="crm_name";
			$erow="name";
			$fdb="eve_events";
			$eve_events=D($fdb)->where("{$fnull}=''")->group($frow)->field($frow)->select();
			
			foreach($eve_events as $key =>$vo){
				
				$crm_contract=D("crm_crm")->where("id='{$vo[$frow]}'")->select();
				
				$data[$fnull]=$crm_contract[0][$erow];
				if($crm_contract[0][$erow]!='')
				D($fdb)->where("{$frow}={$vo[$frow]}")->data($data)->save();
			}
		}
		//分析重整流程資料
		function login_apart(){
			$frow="eid";
			$fnull="apartmentid";
			$erow="apartmentid";
			$fdb="eip_login";
			$eve_events=D($fdb)->where("{$fnull}='0'")->group($frow)->field($frow)->select();
			
			foreach($eve_events as $key =>$vo){
				
				$crm_contract=D("eip_user")->where("id='{$vo[$frow]}'")->select();
				
				$data[$fnull]=$crm_contract[0][$erow];
				if($crm_contract[0][$erow]!='')
				D($fdb)->where("{$frow}={$vo[$frow]}")->data($data)->save();
			}
		}
		//對齊流程資料
		function eve_events_step(){
			$frow="id";
			$fnull="step_num";
			$erow="name";
			$fdb="eve_events";
			$eve_events=D($fdb)->select();
			
			foreach($eve_events as $key =>$vo){
				
				$cou=D("eve_steps")->where("eve_id='{$vo[$frow]}'")->count();
				
				$data[$fnull]=$cou;
				if($cou==0){
					D($fdb)->where("{$frow}={$vo[$frow]}")->delete();
					
					
					}else{
					D($fdb)->where("{$frow}={$vo[$frow]}")->data($data)->save();
					
					
				}
			}
		}
		//清除流程資料
		function eve_step_clear(){
			$frow="eve_id";
			$fnull="step_num";
			$fdb="eve_steps";
			$eve_events=D($fdb)->field($frow)->distinct(true)->select();
			dump($eve_events);exit;
			foreach($eve_events as $key =>$vo){
				
				$cou=D("eve_events")->where("id='{$vo[$frow]}'")->count();
				
				if($cou==0){
					D($fdb)->where("{$frow}={$vo[$frow]}")->delete();
					
				}
			}
		}
		//人員編號重編
		function user_no(){
			$eip_user=D("eip_user")->where("`no` like 'AA%'")->select();
			foreach($eip_user as $key=>$vo){
				$data['no']="MAIN_".$vo['no'];
				D("eip_user")->where("id={$vo['id']}")->data($data)->save();
			}
			
		}
		//多檔上傳
		function upload(){
			
			$disname='Uploads/create/';
			$file=parent::uploadfile($disname,"");
			$this->redirect("Create/index",'');
		}
		
		//人員權限
		public function do_userpower()
		{parent::set_childeid();
		}
		public function men_sn(){
			$eip_user=D("eip_user")->where("status=1 and is_job=1")->order("dutday asc")->select();
			
			foreach($eip_user as $key=>$vo){
				$r[$vo[right]]++;
				$st="AAA0000";
				switch($vo[right]){
					case 0:
					$st="AAA0000";
					break;
					case 1:
					$st="正AAA".str_pad($r[$vo[right]], 4, "0", STR_PAD_LEFT);
					break;
					case 2:
					$st="臨AAA".str_pad($r[$vo[right]], 4, "0", STR_PAD_LEFT);
					break;
					case 3:
					$st="資AAA".str_pad($r[$vo[right]], 4, "0", STR_PAD_LEFT);
					break;
				}
				$data['no']=$st;
				D("eip_user")->data($data)->where("id=".$vo['id'])->save();
				
			}
			dump($r);
			foreach($r as $key=>$vo){
				
				D("eip_user_right_type")->where("id=".$key)->data("num=".$vo)->save();
			}
			//$eip_user=D("eip_user")->where("id=".self::$top_adminid)->data("no='AAA0000'")->save();
		}
		public function men_no_expe(){
			$eip_user=D("eip_user")->where("status=1 and is_job=1")->select();
			foreach($eip_user as $key=>$vo){
				$expe[0][0]=strtotime($vo['dutday']);
				$expe[0][1]=$vo['no'];
				$data['no_expe']=json_encode($expe);
				D("eip_user_data")->where("`eid`='".$vo['id']."'")->data($data)->save();
				
			}
			
		}

		function seo_reback(){
			$crm_seo_key=D("crm_seo_key")->select();
			$num=0;
			foreach($crm_seo_key as $key=>$vo){
				if(D('crm_key_rank')->where("key_Id=".$vo['id']." && crm_key_rank.`update`='".date("Y-m-d")."'")->count()==0){
					//dump("KeyDoneDate=".date("Y/m/d",strtotime("- 1 day")));
					$num++;
					D("crm_seo_key")->data("KeyDoneDate=".date("Y/m/d",strtotime("- 1 day")))->where("id=".$vo['id'])->save();
				}
				
			}
			dump($num);
			//dump($crm_seo_key);
		}
		function index_seomoney(){
			$crm_seomoney=D("print_txt")->group("caseid,qh")->field("caseid,qh")->order("qh,caseid asc")->select();
			//dump($crm_seomoney);
			foreach ($crm_seomoney as $key => $value) {
				$count=D("print_txt")->where("caseid='".$value[caseid]."' and `qh`='".$value[qh]."'")->count();
				if($count>1){

					dump($value);
					dump($count);
				}
			}
			//dump($crm_seomoney);
		}
	}
?>										