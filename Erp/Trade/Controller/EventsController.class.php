<?php
	namespace Trade\Controller;
	use Think\Controller;
	class EventsController extends GlobalController 
	{
		function _initialize()
		{
			parent::_initialize();

			$this->assign('page_title', '事件簿設定');
			$this->assign('page_title_link_self', U('Events/index'));
			$this->assign('page_title_active', 68);  /*右上子選單active*/
		} 
		
		public function index(){
			D('eve_processes')->where("`name`=''")->delete();
			$processes = $this->get_eve_processes();
			$this->assign('processes',$processes);
			$this->assign("events_Btn_on","navBtn_on");

			$model_builder=D('eip_user e')
				->field('e.id, e.name')
				->join('eve_processes ep on ep.eid=e.id','right')
				->where("e.status=1 && e.is_job=1 && e.id!=".self::$top_adminid)
				->group('e.id')
				->select();
			$this->assign('model_builder',$model_builder);

			$this->assign('search_name', trim($_GET['name']) );
			$this->assign('search_userid', $_GET['user_id']);
			$this->display();
		}
		public function get_eve_processes(){
			$user_where = $_GET['user_id'] ? ' AND ep.eid ='.$_GET['user_id'] : '';
			$name_where = $_GET['name'] ? ' AND ep.name like "%'.trim($_GET['name']).'%" ' : '';

			$processes=D('eve_processes ep')
				->field('ep.*, e.name as user_name')
				->join('eip_user e on ep.eid=e.id', 'left')
				->where(' ep.status=1 '.$user_where.$name_where)
				->select();

			if($_GET['ajax'] ){
				$this->ajaxReturn($processes);
			}else{
				return $processes;
			}
		}
		public function copy(){
			$id = isset($_GET['id']) ? $_GET['id'] : '';
			$processes = D('eve_processes')->where('id="'.$id.'"')->find();
			if(!$processes){ $this->error('無此複製對象'); }
			unset($processes['id']);
			$processes['name'] .= '-複製';
			if(D('eve_processes')->data($processes)->add()){
				$this->success("複製成功!!",U("Events/index"));
			}else{
				$this->error("複製失敗!!");	
			}
		}
		
		public function editevents(){
			$acc = parent::check_all_access(CONTROLLER_NAME); //檢查看全部or指定瀏覽的 2019/11/06 fatfat
		
			parent::check_edi_access($acc[strtolower(CONTROLLER_NAME)."_edi"]); //檢查是否可編輯 2019/11/06 fatfat
			
			if(isset($_GET['id'])){		
				$processes=D('eve_processes')->where('status=1 && id='.$_GET['id'])->select()[0];
				$this->assign('processes',$processes);
			}
			parent::index_set('eip_apart');
			parent::index_set('eve_role_steps');
			parent::index_set('eip_user','is_job=1',"apartmentid");

			$this->assign("date",time());
			$this->display();
		}
		function do_add(){
			// dump($_POST);exit;
			$box[0]=(array)$_POST['steps'];			// 腳色
			$box[1]=(array)$_POST['code'];			// 人員
			$box[2]=(array)$_POST['work'];			// 行文紀錄
			$box[3]=(array)$_POST['sdate'];			// 開始時間
			$box[4]=(array)$_POST['edate'];			// 結束時時間
			$box[5]=(array)$_POST['price'];			// 績效
			$box[6]=(array)$_POST['count_type'];	// 內外單
			$box[7]=(array)$_POST['estimated_time'];// 預估工時
			$box[8]=(array)$_POST['exact_time'];	// 實際工時
			// dump($_POST['count_type']);
			$_POST['schedule']=json_encode($box, JSON_UNESCAPED_UNICODE);
			$_POST['html'] = save_img_in_content($_POST['html']);
			// dump($_POST);exit;
			if(!isset($_POST['id'])){
				if(D('eve_processes')->data($_POST)->add()){
					$this->success("新增成功!!",U("Events/index"));
				}else{
					$this->error("新增失敗!!");
				}
			}else{
				$id=$_POST['id'];
				unset($_POST['id']);
				if(D('eve_processes')->data($_POST)->where("id={$id}")->save()){
					$this->success("更新成功!!", U("Events/index"));
				}else{
					$this->error("無資料需更新!!");
				}
			}
		}

		function delevents(){
			$acc = parent::check_all_access(CONTROLLER_NAME); //檢查看全部or指定瀏覽的 2019/11/06 fatfat
		
			parent::check_edi_access($acc[strtolower(CONTROLLER_NAME)."_del"]); //檢查是否刪除 2019/11/06 fatfat

			foreach($_POST['ids'] as $vo){
				D('eve_processes')->where("id={$vo}")->delete();	
			}
			$this->success("刪除成功!!",u('Events/index'));
		}
		
		public function output(){
			header("Content-type: application/text");
			header("Content-Disposition: attachment; filename=output.mic");
			if(isset($_GET['id'])){
				$schedule=D('eve_processes')->where('status=1 && id='.$_GET['id'])->select()[0]["schedule"];
				echo $schedule;
			}	
		}
		public function input(){
			$_POST['file_name']=$_FILES['file']['name'];
			
			if($_FILES['file']['name']){
				$disname='Uploads/Events/';
				$upfile=parent::uploadfile($disname);
				$file = fopen(substr($upfile,1,strlen($upfile)), "r") or exit("Unable to open file!");
				$box="";
				//輸出文本中所有的行，直到文件結束為止。
				while(! feof($file))
				{
					$box.=fgets($file);
				}
				fclose($file);
				$data['schedule']=$box;
				$id=D('eve_processes')->data($data)->add();
				if($id && $box!=""){
					
					redirect(u("Events/editevents")."?id=".$id);
				}
				
			}
			$this->error("錯誤");
		}
	}
?>