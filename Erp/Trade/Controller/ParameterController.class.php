<?php
	namespace Trade\Controller;
	use Think\Controller;
	
	class ParameterController extends GlobalController {

		function _initialize()
		{
			parent::_initialize();

			$powercat_id = 112;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		}

		function index(){
			$this->redirect('Parameter/parameter');
		}

		function parameter(){
			$eip_company_data = D("eip_company")->select();
			$this->assign("eip_company_data", $eip_company_data);

			$this->assign('top_adminid', self::$top_adminid);
			parent::index_set('eip_user_right_type', 'id!=0');

			$system_parameter_customer_data = D('system_parameter')->order('id ASC')->find(8);
			$this->assign('system_parameter_customer_data', $system_parameter_customer_data);
			
			$this->display();
		}
		function update(){
			// dump($_POST);exit;
			$right_type = isset($_POST['right_type']) ? $_POST['right_type'] : [];
			unset($_POST['right_type']);
			$system_parameter_customer_data = isset($_POST['system_parameter_customer_data']) ? $_POST['system_parameter_customer_data'] : [];
			unset($_POST['system_parameter_customer_data']);
			
			$data = $_POST;

			$id = isset($data['id']) ? $data['id'] : "";
			unset($data['id']);
			if($id==""){ $this->error('無法編輯'); }
			if(($id=="1" || $right_type) && session('adminId')!= self::$top_adminid){ 
				$this->error('無權限編輯');
			}

			// 更新 eip_user_right_type
			// dump($right_type);exit;
			foreach ($right_type as $key => $value) {
				if($value['id']!=0){ /*不允許修改總管理*/
					D('eip_user_right_type')->data([
						'name'=>$value['name'],
						'nick'=>$value['nick'],
						'limit_num'=>$value['limit_num'],
					])->where('id='.$value['id'])->save();
				}
			}

			// 更新 system_parameter
			// dump($system_parameter_customer_data);exit;
			if($system_parameter_customer_data['id']==8 && $system_parameter_customer_data['data']){
				D('system_parameter')->data([
					'data'=>$system_parameter_customer_data['data'],
				])->where('id='.$system_parameter_customer_data['id'])->save();
			}

			// 登入畫面圖示
			if(isset($_FILES['login_logo'])){
				$file = $_FILES['login_logo'];
				if($file['name']){
					$fileName = $_SERVER['DOCUMENT_ROOT'].'/Uploads/parameter/'.$id.'/'.$file['name'];
					move_uploaded_file($file['tmp_name'], $fileName);
					$data['login_logo'] = $file['name'];
				}
			}
			// 系統logo
			if(isset($_FILES['head_logo'])){
				$file = $_FILES['head_logo'];
				if($file['name']){
					$fileName = $_SERVER['DOCUMENT_ROOT'].'/Uploads/parameter/'.$id.'/'.$file['name'];
					move_uploaded_file($file['tmp_name'], $fileName);
					$data['head_logo'] = $file['name'];
				}
			}

			D('eip_company')->where('id='.$id)->data($data)->save();

			$this->success('操作成功，請稍候...');
		}
	}
?>																																																																															