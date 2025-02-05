<?php
	namespace Trade\Controller;
	use Think\Controller;
	class DomcomController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();
			$this->assign('page_title_link_self', U('Domcom/index'));
			$this->assign('page_title_active', 63);  /*右上子選單active*/

			$this->db=D('crm_domaincompany');
			$this->dbname="crm_domaincompany";
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
		} 
		
		public function index()
		{
			$where="status=1 and ";
			$order="";
			$this->assign('input_nick', '');
			foreach($_GET as $key =>$vo){
				if($vo!="")
				if( $key!="p" && $key!="order"){
					if($key=="nick"){
						$this->assign('input_nick', $vo);
						$vo_name1 = str_replace ('台','臺',$vo);
						$vo_name2 = str_replace ('臺','台',$vo);
						$where.="(
								  	(`nick` like '%".$vo_name1."%' OR `nick` like '%".$vo_name2."%') OR 
								  	(`user` like '%".$vo_name1."%' OR `user` like '%".$vo_name2."%')
								) AND ";
					}else{
						$where.=" `{$key}` like '%{$vo}%' and ";
					}
				}else if($key=="order"){//排序
					$order="`".$vo."` desc";
				}
				
			}
			$count = $this->db->where($where." true")->count();
			$Page = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$Page->setConfig('header',"%TOTAL_ROW% 個客戶");
			$Page->setConfig('prev',"上一頁");
			$Page->setConfig('next',"下一頁");
			$Page->setConfig('first',"第一頁");
			$Page->setConfig('last',"最後一頁 %END% ");
			$show       = $Page->show();// 分页显示输出
			//$sql="select c.*,d.*,e.name as ename,u.name as uname,d.d_url as url,c.flag as flag,d.d_note as bz,c.id as caseid,c.cid as cid,d.id as id from crm_contract c left join crm_crm_view e on c.cid=e.id left join eip_user u on c.eid=u.id left join crm_contract_host d on (c.id=d.pid $where1) where c.cate=3 and c.cate1=1 $where order by c.id desc limit ".($page-1)*PAGE_LIST.','.PAGE_LIST;
			
			$crm_contract_host=$this->db->where($where." true")->order($order)->limit($Page->firstRow.','.$Page->listRows)->select();
			
			$this->assign("page",$_GET['p']);
			//dump($crm_contract_host);
			$this->assign("data",$crm_contract_host);
			$this->assign("show",$show);
			$this->display();
		}
		public function view()
		{
			if(!isset($_GET['id']))$this->redirect(CONTROLLER_NAME.'/index');
			$crm_contract_host=$this->db
			->where("{$this->dbname}.id={$_GET['id']}")->select();
			$this->assign("data",$crm_contract_host[0]);
			$this->display();
		}
		
		public function editor()
		{
			if(isset($_GET['id'])){
				$crm_contract_host=$this->db
				->where("{$this->dbname}.id={$_GET['id']}")->select();
				$this->assign("data",$crm_contract_host[0]);
			}
			$this->display();
		}
		function dosave(){
			if(!($_POST['id']=="")){
				$id=$_POST['id'];
				unset($_POST['id']);
				if($this->db->where("id=".$id)->data($_POST)->save()){
					$this->success("更新成功",U(CONTROLLER_NAME.'/view')."?id=".$id);
					}else{
					$this->error("更新失敗");
					
				}
				}else{
				$add=$this->db->data($_POST)->add();
				if($add){
					$this->success("更新成功",U(CONTROLLER_NAME.'/view')."?id=".$add);
					}else{
					$this->error("更新失敗");
					
				}
				
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
	}
?>