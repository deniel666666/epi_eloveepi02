<?php
namespace Trade\Controller;
use Trade\Controller\CustoController;

class CrmtraceController extends CustoController 
{	
	function _initialize(){
		parent::_initialize();
	}
	
	//現況追蹤
	function index(){
		$return_data = parent::search_customer($_GET, 50);
		$this->assign('country',$return_data['country']);
		$this->assign('district',$return_data['district']);
		$this->assign('page',$return_data['page']);
		$this->assign('crm_cum_pri',$return_data['crm_cum_pri']);
		$this->assign('linit',$return_data['linit']);
		$this->assign('crmtype',$return_data['crmtype']);
		$this->assign('typespan',$return_data['typespan']);
		$this->assign('total',$return_data['total']);
		$this->assign('team_name',$return_data['team_name']);

		$cid = session('cid');
		$levels = D()->query("select `id`,`name` from crm_cum_level where `cid`=$cid and `status`=1 ");

		$typeid = $_GET['typeid'];
		$industr=D("crm_crm")->where($return_data['swhere']."and `industr` != ''")->field("industr")->group("industr")->select();
		$industr_all=D("crm_crm")->where("`industr` != ''")->field("industr")->group("industr")->select();
		$industr2_search=D("crm_industr")->where("industr = '".$_GET['industr']."'")->field("industr2")->select();
		
		foreach($return_data['crmlist'] as $key => $vo){
			$return_data['crmlist'][$key]['level']=$levels[$vo['levelid']-1]['name'];
			$return_data['crmlist'][$key]['type']=$crmtype[$vo['typeid']-1]['name'];

			$industr2[$key+1]=M("crm_industr")->where("industr = '".$vo['industr']."'")->select();
		}
		
		if($return_data['team_id'] == ''){
			parent::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and (no like '正%' or no like '臨%')");
		}else{
			//parent::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and id in( ".session('childeid').$team_id.") ");
			parent::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and id in( ".$return_data['team_id'].") ");
		}
		foreach ($return_data['crmlist'] as $key => $value) {
			$return_data['crmlist'][$key]['last_chats'] = D("crm_chats cc")
															->field('cc.*, ct.*')
															->join('crm_contact ct on ct.id = cc.lxrid')
															->where("cc.cumid =".$value['id'])
															->order("cc.id desc")
															->limit(1)->select()[0];
		}
		// dump($return_data['crmlist']);exit;
		parent::index_set('crm_cum_sourse');
		parent::index_set('crm_chatqulity');
		$this->assign('crmlist',$return_data['crmlist']);
		$this->assign('levels',$levels);
		$this->assign('scv',$_GET['searchvalue']);
		$this->assign('typeid',$typeid);
		$this->assign('industr',$industr);
		$this->assign('industr_all',$industr_all);
		$this->assign('industr2',$industr2);
		$this->assign('industr2_search',$industr2_search);
		
		$this->display();
	}
}

?>