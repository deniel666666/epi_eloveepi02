<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\Common;
	
	class AccessController extends GlobalController {
		function _initialize(){
			parent::_initialize();
			$this->assign('company', '傳訊光商用EIP');
			$this->assign('pagetitle','權限管理');
			$this->assign('page_title_active', 5);  /*右上子選單active*/
			$this->dao=D('access');
		} 

		function index(){
			$this->assign('page_title', '權限管理');

			$group = $_GET['group'] ?? '1';
			$this->assign('group',$group);
			$access_edit = Common::get_access_by_access_id($group);
			$access_edit['km_access'] = json_decode($access_edit['km_access']); 
			$this->assign('access_edit',$access_edit);

			$glist = $this->dao->select();
			$this->assign('glist',$glist);

			$this->display();
		}
		function get_access_content(){
			$all_menu_adjust = [];
			$all_menu = Common::get_can_see_menu(parent::$top_adminid)['arranged']; //全部功能選單
			foreach ($all_menu as $key => $value) {
				$sub_menu_adjust = [];
				foreach ($value['sub_menu'] as $sub_key => $sub_menu) {
					if($sub_menu['codenamed'] || $sub_menu['link']){
						array_push($sub_menu_adjust, $sub_menu);
					}else{
						foreach ($sub_menu['sub_menu'] as $sub_sub_menu) {
							$sub_sub_menu['title'] = $sub_menu['title'].' - '.$sub_sub_menu['title'];
							array_push($sub_menu_adjust, $sub_sub_menu);
						}
					}
				}
				$value['sub_menu'] = $sub_menu_adjust;
				array_push($all_menu_adjust, $value);
			}
			// dump($all_menu_adjust);exit;
			$all_access = parent::get_my_access(parent::$top_adminid);				//全部權限
			$this->ajaxReturn([
				'all_menu' => $all_menu_adjust,
				'all_access' => $all_access,
				'poweritem' => array('new','red','edi','hid','del','all'),
			]);
		}

		function add(){
			if(isset($_POST['name']) && $_POST['name']!=''){
				$_POST['status'] = 1;
				if($this->dao->data($_POST)->add()){
					parent::error_log("新增 access 權限".$_POST['name']);
					$this->success( '新增成功!!',0,3);
				}else{
					$this->error( '新增失敗!!');
				}
			}else{
				$this->error( '沒有輸入資料!!');
			}
		}

		function delete(){
			foreach($_POST['id'] as $key => $vo){
				if($vo == '1')
					$this->error( '管理員不可刪除');
			}
			parent::_delete();
		}

		function update(){
			// dump($_POST);exit;
			$_POST['km_access'] = isset($_POST['km_access']) ? json_encode($_POST['km_access']) : '{}';
			$_POST['status'] = 1;
			if($_POST['id']=='1' && session('adminId')!=self::$top_adminid)
				$this->error('管理員權限不可變更');
			if(!isset($_POST['id'])){
				$this->error('新增失敗!!');
			}else{
				$this->dao->where('id='.$_POST['id'])->delete();
				
				if($this->dao->data($_POST)->add()){
					parent::error_log("更新 access 權限".$_POST['id']);
					$this->success( '更新成功!!',U('access/index/group/'.$_POST['id']),3);
				}else{
					$this->error( '更新失敗!!',u('access/index','group/'.$_POST['id']));
				}
			}
		}
	}
?>