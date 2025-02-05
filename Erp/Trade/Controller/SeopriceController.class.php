<?php
	namespace Trade\Controller;
	use Think\Controller;
	class SeopriceController extends GlobalController {
		
		function _initialize()
		{
			parent::_initialize();
			parent::check_has_access(CONTROLLER_NAME, 'red');

			$this->assign('page_title_link_self', U('Seoprice/index'));
			$this->assign('page_title_active', 86);  /*右上子選單active*/

			$this->db=D("crm_seoprice");
		} 
		function index()
		{	
			if($_GET["status"]=="")$_GET["status"]="1";
			foreach($_GET as $key =>$vo){
				if($vo!="")
				if( $key!="p" && $key!="m" && $key!="update" && $key!="key_name" && $key!="todate" && $key!="r"){
					$where.=" `{$key}` like '%{$vo}%' and ";
					}else if($key=="m"){//排序
					switch($vo)
					{
						case 1:
						$where.="price<3000 and ";
						break;
						case 2:
						$where.=" price>=3000 and price<3000 and ";
						break;
						case 3:
						$where.=" price>=5000 and price<8000 and ";
						break;
						case 4:
						$where.=" price>=8000 and price<10000 and ";
						break;
						case 5:
						$where.=" price>=10000 and price<15000 and ";
						break;
						case 6:
						$where.=" price>=15000 and price<20000 and ";
						break;
						case 7:
						$where.=" price>=20000 and price<30000 and ";
						break;
						case 8:
						$where.=" price>=30000 and ";
						break;
						case 9:
						$where.=" (price is null or price='') and ";
						break;
						case 10:
						$where.=" (price is not null or price>=0) and ";
						break;
					}
				}
			}
			$crm_seoprice=D("crm_seoprice")->where($where." true ")->order("date desc")->select();
			$engine=D("crm_seoprice_engine")->field("engine")->select();
			
			//dump($crm_seoprice);
			$this->assign("crm_seoprice",$crm_seoprice);
			$this->assign("status",$_GET['status']);
			$this->assign("engine",$engine);
			$this->display();
		}
		
		
		/*Api:批次處理*/
		public function patchupdate(){
			//dump($_POST);exit;
			switch($_POST['val']){
				case 1:
					parent::check_has_access(CONTROLLER_NAME, 'edi');
					$data="project=1";
				case 2:
					parent::check_has_access(CONTROLLER_NAME, 'edi');
					$data="full=1";
				case 3:
					parent::check_has_access(CONTROLLER_NAME, 'hid');
					$data="status=0";
				
			}
			foreach($_POST['flags'] as $vo){
				$this->db->where('id='.$vo)->data($data)->save();
			}
			parent::error_log("修改SEO價格資料:".json_encode($_GET));
			$this->success('更新成功');
			
		}
		/*Api:刪除資料*/
		public function del(){
			parent::check_has_access(CONTROLLER_NAME, 'hid');
			
			$this->db->where('id='.$_GET['id'])->data("status=0")->save();
			
			parent::error_log("刪除SEO價格資料:".$_GET['id']);
			$this->success('刪除成功');
			
		}

		/*Api:新增資料*/
		public function do_add(){
			parent::check_has_access(CONTROLLER_NAME, 'new');

			$_GET['date']=time();
			if($_GET['name']!=''){
				$this->db->data($_GET)->add();
				
				parent::error_log("新增SEO價格資料:".json_encode($_GET));
				$this->success('新增成功');
				exit;
			}
			$this->error("新增失敗");
			
		}
		
		/*Api:改資料*/
		public function aj_chcontent(){
			parent::check_has_access(CONTROLLER_NAME, 'edi');

			if(!isset($_POST['dbname']) || !isset($_POST['row'])){
				$this->error('修改失敗');
			}
			$data[$_POST['row']]=$_POST['data'];
			D($_POST['dbname'])->data($data)->where("id='{$_POST['id']}'")->save();

			parent::error_log("修改".$_POST['dbname']."欄位".$_POST['row']."資料列:{$_POST['id']}的資料{$_POST['data']}");
			$this->success('修改成功');
		}
	}
	
?>