<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use Photonic\CustoHelper;

  class ConlistController extends GlobalController{
    function _initialize($get_or_pay=0){
      parent::_initialize();
      parent::index_set('crm_cum_pri','id=1');
      parent::check_has_access(CONTROLLER_NAME, 'red');

      $powercat_id = 88;
      $powercat = D('powercat')->find($powercat_id);
      $this->powercat_current = $powercat;
      $this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

      $this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
      $this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
      $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);

      if($this->get_or_pay==0){
        $this->assign('alllist_controller', 'alllist');
      }else{
        $this->assign('alllist_controller', 'alllistpay');
      }
    }
    
    //新增客戶
    public function index(){
      $return_data = CustoHelper::search_customer([], $_GET, 1, true);
      $return_data = CustoHelper::merge_industr_with_search_customer($_GET, $return_data);
      $this->assign('levels', $return_data['levels']);
      $this->assign('industr', $return_data['industr']);
      $this->assign('industr2_search', $return_data['industr2_search']);
      $this->assign('country',$return_data['country']);
      $this->assign('district',$return_data['district']);
      $this->assign('crm_cum_pri',$return_data['crm_cum_pri']);
      $this->assign('eip_user',$return_data['eip_user']);

      $this->display('Conlist/index');
    }
    function ajax_search_customer(){
      foreach($_POST as $k=>$v){
        $post_data[$k] = trim($v);
      }
      $post_data['typeids'] = ['2','3'];
      $acc = parent::get_my_access();
      $return_data = CustoHelper::search_customer($acc, $post_data, 100);

      /*處理各客戶資料*/
      foreach($return_data['crmlist'] as $key => $vo){
        $crm_contract = D('crm_crm')->field('count(sn) as count,sum(money) as money,sum(allmoney) as allmoney')
                                    ->join("left join crm_contract on crm_crm.id=crm_contract.cid")
                                    ->where($this->get_or_pay_where." AND crm_contract.cid ='".$vo['id']."' and flag2!='3'")
                                    ->select()[0];
        $return_data['crmlist'][$key]['count'] = $crm_contract['count'];
        $return_data['crmlist'][$key]['money'] = $crm_contract['money'];
        $return_data['crmlist'][$key]['allmoney'] = $crm_contract['allmoney'];
        // dump($crm_contract);
      }

      /*員工*/
      $return_data['eip_user_all'] = parent::index_set('eip_user',"is_job=1 and id !=".self::$top_adminid." and (no like '正%' or no like '臨%')", '', true);

      $this->ajaxReturn($return_data);
    }
  }
?>