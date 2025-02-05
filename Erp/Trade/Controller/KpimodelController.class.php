<?php
	namespace Trade\Controller;
	use Think\Controller;

	use Photonic\ProductHelper;
	
	class KpimodelController extends GlobalController {
		function _initialize()
		{
			parent::_initialize();
			$this->assign('company', '傳訊光商用EIP');
			$this->assign('pagetitle','權限管理');
			$this->dao=D('kpimodel');

			$this->assign('page_title', '績效設定');
			$this->assign('page_title_link_self', U('Kpimodel/index'));
			$this->assign('page_title_active', 106);  /*右上子選單active*/
		} 
		
		function index(){
			$daoModel = empty($model)?strtolower(CONTROLLER_NAME)."_all" : $model."_all";
			$acc=D('access')->field($daoModel)->where('id='.session('accessId'))->select()[0];

			$poweritem=array('new','red','edi','hid','del','all');
			//搜尋權限類別管理
			$glist = $this->dao->select();
			if(session('accessId') != '1' && $acc[$daoModel] == 0)
				$glist = $this->dao->where("id = '".session('accessId')."'")->order('id asc')->select();
			$this->assign('glist',$glist); // 左側選單

			$group = (int)$_GET['group'] > 0 ? $_GET['group'] : 1;
			$target = $this->dao->where('id ='.$group)->find();
			if(!in_array(121, self::$use_function_top)){ /*如果未使用商品管理*/
				$target['use_account_sum'] = 1;
				$target['use_event_sum'] = 1;
			}
			$this->assign('target',$target); // 模組
			$this->assign('group',$group); // 模組ID

			$crm_cum_cat=D('crm_cum_cat')->field('id, name')->where('status = 1')->order('sort asc, id desc')->select();

			$crm_contract_unit = array([
								'id'	=> 0,
								'number'	=> '',
								'u_id'	=> 'u_0',
								'name'	=> '免'.self::$system_parameter['合約'].'執行',
								'type'	=> '',
								'profit'=> 1,
							]);
			$cat_units = ProductHelper::get_cat_unit($status=1, $get_or_pay=0)['cat_units'];
			array_walk($cat_units, function($v, $k)use(&$cat_units){
				$cat_units[$k]['u_id'] = 'u_'.$v['id']; // 必須這樣處理，未來才能調用的了值
			});
			$crm_contract_unit = array_merge($crm_contract_unit, $cat_units);
			// dump($crm_contract_unit);exit;
			$this->assign('crm_contract_unit',$crm_contract_unit); // 商品

			$this->display();
		}
		
		function add(){
			if(isset($_POST['name']) && $_POST['name']!=''){
				
				$_POST['dividual_account'] = '{}';
				$_POST['account_bonus'] = '[]';
				
				$_POST['dividual_event'] = '{}';
				$_POST['event_bonus'] = '[]';

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
			parent::_delete();
		}
		
		
		function update(){	
			if(!isset($_POST['id'])){
				$this->error( '更新失敗!!');
			}else{
				$id = $_POST['id'];
				$_POST['use_account'] = $_POST['use_account'] ? $_POST['use_account'] : 0 ;
				$_POST['dividual_account'] = json_encode($_POST['dividual_account']) != 'null' ? json_encode($_POST['dividual_account']) : '{}';
				$_POST['account_bonus'] = json_encode($_POST['account_bonus']) != 'null' ? json_encode($_POST['account_bonus']) : '[]';
				$_POST['account_pv_bonus'] = $_POST['account_pv_bonus'] == 'on' ? 1 : $_POST['account_pv_bonus'];
				$_POST['account_pv_bonus'] = $_POST['account_pv_bonus'] == 'off' ? 0 : $_POST['account_pv_bonus'];
				$_POST['account_accum_bonus'] = $_POST['account_accum_bonus'] == 'on' ? 1 : $_POST['account_accum_bonus'];
				$_POST['account_accum_bonus'] = $_POST['account_accum_bonus'] == 'off' ? 0 : $_POST['account_accum_bonus'];

				$_POST['use_event'] = $_POST['use_event'] != 'null' ? $_POST['use_event'] : 0 ;
				$_POST['dividual_event'] = json_encode($_POST['dividual_event']) != 'null' ? json_encode($_POST['dividual_event']) : '{}';
				$_POST['event_bonus'] = json_encode($_POST['event_bonus']) != 'null' ? json_encode($_POST['event_bonus']) : '[]';
				$_POST['event_pv_bonus'] = $_POST['event_pv_bonus'] == 'on' ? 1 : $_POST['event_pv_bonus'];
				$_POST['event_pv_bonus'] = $_POST['event_pv_bonus'] == 'off' ? 0 : $_POST['event_pv_bonus'];
				$_POST['event_accum_bonus'] = $_POST['event_accum_bonus'] == 'on' ? 1 : $_POST['event_accum_bonus'];
				$_POST['event_accum_bonus'] = $_POST['event_accum_bonus'] == 'off' ? 0 : $_POST['event_accum_bonus'];
				unset($_POST['id']);
				unset($_POST['write']);

				// dump($_POST);
				// exit;
				if($this->dao->where('id ='.$id)->data($_POST)->save()){
					$this->success( '更新成功!!',U('kpimodel/index/group/'.$id));
				}else{
					$this->error( '更新失敗!!',U('kpimodel/index/group/'.$id));
				}	
			}			
		}
		
	}
	
?>																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																																							