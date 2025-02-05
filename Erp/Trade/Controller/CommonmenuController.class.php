<?php
	namespace Trade\Controller;
	use Trade\Controller\GlobalController;

	use Photonic\Common;

	class CommonmenuController extends GlobalController {
		
		function _initialize(){
			parent::_initialize();

			$powercat_id = 113;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		}

		function index(){
			$this->display();
		}

		/*取得你的常用選單資料(帶連結)*/
		function get_common_menu_with_link($read_count=false){
			$user_id = session('adminId'); /*使用者id*/

			$my_menu = [];
			$my_menu_with_link = [];

			//自己常用選單的資料
			$result = D("common_menu")->where(["user_id" => $user_id])->find();

			if (empty($result) == false) {
				if (empty($result['data']) == false) {
					$my_menu = json_decode($result['data']);
				}
				
				$my_menu_all = Common::get_can_see_menu($user_id, $read_count)['list'];

				foreach ($my_menu as $id) {
					foreach ($my_menu_all as $k_a => $v_a) {
						if($id==$v_a['id']){
							array_push($my_menu_with_link, [
								'title' => $v_a['title'],
								'url' => $v_a['link'] ? $v_a['link'] : '/index.php/'.$v_a['codenamed'].'/index',
								'read_ck' => $v_a['read_ck'],
							]);
						}
					}
				}
			}

			return $my_menu_with_link;
		}

		/*取得你的常用選單資料*/
		function get_common_menu(){
			$user_id = session('adminId'); /*使用者id*/
			
			//自己常用選單的資料
			$my_menu = D("common_menu")->where("user_id=".$user_id)->find();
			if($my_menu){
				$my_menu = $my_menu['data'] ? json_decode($my_menu['data']) : [];
			}else{
				$my_menu = [];
			}

			$this->ajaxReturn($my_menu);
		}

		/*取得你的常用選單資料*/
		function save_common_menu(){
			$user_id = session('adminId'); /*使用者id*/
			$data = $_POST;
			$data['user_id'] = $user_id;
			
			//自己常用選單的資料
			$my_menu = D("common_menu")->where("user_id=".$user_id)->find();
			if($my_menu){ /*已經有選單紀錄*/
				D('common_menu')->data($data)->where("user_id=".$user_id)->save();
			}
			else{/*無選單紀錄*/
				D("common_menu")->data($data)->add();
			}

			$this->success('儲存成功');
		}

		/*取得你全部可見的選單*/
		function get_my_can_see_menu(){
			$user_id = session('adminId'); /*人員id*/
			$result = (Array)Common::get_can_see_menu($user_id);
			$this->ajaxReturn($result);
		}
	}
?>																																																																															