<?php
	namespace Trade\Controller;
	use Think\Controller;
	class KmsettingController extends GlobalController 
	{	
		public $km_where = 'km_types.id!=1';

		function _initialize()
		{
			parent::_initialize();

			$powercat_id = 120;
			$powercat = D('powercat')->find($powercat_id);
			$this->powercat_current = $powercat;
			$this->assign('page_title_active', $powercat_id);  /*右上子選單active*/
		}

		public function index(){
			parent::check_has_access(CONTROLLER_NAME, 'red');

			if($_POST){ /*修改or新增*/
				$km_typess = $_POST['km_types'] ? $_POST['km_types'] : [];
				// dump($km_typess);
				foreach ($km_typess as $km_types_id => $value) {
					$data = [];
					$data['title'] = $value['title'];
					$data['codenamed'] = $value['codenamed'];
					$data['orders'] = $value['orders'];
					$data['parent_id'] = $value['parent_id'];

					/*檢查格式*/
					if(!preg_match("/^[A-Z][a-z]+$/i", $data['codenamed'])){
						$this->error('有資料格式不符', U('Kmsetting/index'));
					}
					$data['description'] = strtoupper(substr($value['codenamed'], 0, 2));

					$km_types_ori = D('km_types')->where($this->km_where.' AND id='.$km_types_id)->find();
					if($km_types_ori){ /*編輯*/
						parent::check_has_access(CONTROLLER_NAME, 'edi');

						if($data['codenamed'] != $km_types_ori['codenamed']){ /*要改codenamed*/
							/*檢查重複*/
							$km_types_same = D('km_types')->where($this->km_where.' AND id!='.$km_types_id.' AND codenamed="'.$data['codenamed'].'"')->find();
							if($km_types_same){
								$this->error('資料重複', U('Kmsetting/index'));
							}
							$km_types_same = D('km_types')->where($this->km_where.' AND id!='.$km_types_id.' AND description="'. mb_strtoupper(substr($data['codenamed'], 0, 2)).'"')->find();
							if($km_types_same){
								$this->error('KM編碼重複', U('Kmsetting/index'));
							}
						}

						/*更新km_types資料*/
						// dump($data);exit;
						D('km_types')->where('id='.$km_types_id)->data($data)->save();

					}
					else{ /*新增*/
						parent::check_has_access(CONTROLLER_NAME, 'new');

						if($data['title'] && $data['codenamed']){
							D('km_types')->data($data)->add();
						}
					}
				}
				$this->redirect('Kmsetting/index');
			}
			if($_SERVER['REQUEST_METHOD']=='DELETE'){ /*刪除*/
				parent::check_has_access(CONTROLLER_NAME, 'del');
				$data = file_get_contents("php://input");
				$km_types_id = explode('id=', $data);
				$km_types_id = count($km_types_id)==2 ? $km_types_id[1] : "-1";
				D('km_types')->where('id='.$km_types_id)->delete();
				$this->success('刪除成功');
			}

			$km_types_group = D('km_types')->field('km_types.*')
										   ->join("LEFT JOIN powercat on powercat.id= km_types.parent_id")
										   ->where($this->km_where)
										   ->group('km_types.parent_id')
										   ->order('powercat.orders asc')->select();
			foreach ($km_types_group as $key => $value) {
				$km_types_group[$key]['km_types'] = D('km_types')
														->where($this->km_where.' AND parent_id="'.$value['parent_id'].'"')
														->order('orders asc, id desc')->select();
			}
			$this->assign('km_types_group', $km_types_group);

			parent::index_set('powercat', 'parent_id=0', '', false, 'orders asc, id desc');
			$this->display();
		}
	}
?>