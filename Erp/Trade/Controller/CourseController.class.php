<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use Photonic\CustoHelper;
  use Photonic\MoneyHelper;
  use Photonic\ContractHelper;

  class CourseController extends GlobalController 
  {
    static public $pagecount=25;

    function _initialize($get_or_pay=0){
      parent::_initialize();
      parent::check_has_access(CONTROLLER_NAME, 'red');

      $powercat_id = 74;
      $powercat = D('powercat')->find($powercat_id);
      $this->powercat_current = $powercat;
      $this->assign('page_title_active', $powercat_id);  /*右上子選單active*/

      $this->get_or_pay = $get_or_pay; 						/*收付款判斷 0.收款 1.付款*/
      $this->get_or_pay_where='get_or_pay="'.$get_or_pay.'"'; /*收付款判斷*/
      $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);
      $this->assign('AlllistController', $get_or_pay==0 ? 'Alllist' : 'Alllistpay');
      $this->assign('GetmoneyController', $get_or_pay==0 ? 'Getmoney' : 'Getmoneypay');

      $get_pay_words = ContractHelper::get_pay_words($this->get_or_pay);
      foreach ($get_pay_words as $key => $value) {
        $this->$key = $value;
        $this->assign($key, $value);
      }
    }

    //客户管理 進度選日期
    public function index(){
      $params = $_GET;

      $crm_cum_flag = D('crm_cum_flag')->where('status=1 and id!=0')->order('id')->select();
      $this->assign("crm_cum_flag",$crm_cum_flag);

      $crm_cum_flag2=D('crm_cum_flag2')->where('status=1')->order('id')->select();
      $this->assign("crm_cum_flag2",$crm_cum_flag2);

      $crm_cum_cat=ContractHelper::get_crm_cum_cat($this->get_or_pay);
      $this->assign("crm_cum_cat", $crm_cum_cat);

      parent::index_set('eip_user', "is_job=1 and id !=". self::$top_adminid, '', false);
      parent::index_set('crm_cum_pri', "true", '', false);//2019/11/08  fatfat改版
      $this->assign("page", ($params['p'] ?? 1));

      $this->assign("fix", (1+TAX_RATE));
      
      $this->assign("current_qh", date('Y/m'));

      $course_data = self::get_course_data($params, self::$pagecount, $this->get_or_pay);
      $this->assign("flag2_flag3", $course_data['flag2_flag3']);
      $this->assign("levels", $course_data['levels']);
      $this->assign("industr", $course_data['industr_all']);
      $this->assign("industr2_search", $course_data['industr2_search']);
      $this->assign("show", $course_data['show']);				// 分页显示输出
      $this->assign("all", $course_data['all']);					// 總統計
      $this->assign("list_search", $course_data['list_search']);	// 搜尋合約列表
      $this->assign("all_search", $course_data['all_search']);	// 搜尋統計

      $this->display('Course/index');
    }
    //匯出excel
    public function excel(){
      $params = $_GET;

      $title = [
        '客戶簡稱',
        self::$system_parameter['合約'].'號',
        self::$system_parameter['合約'].'金額',
        self::$system_parameter['合約'].'訂金',
        $this->已收.'金額',
        $this->收款.'率',
        '剩餘'.$this->預收.'款',
        '未'.$this->出貨.'金額',
        '已'.$this->出貨.'金額',
        $this->出貨.'率',
        $this->超收,
        '總損益',
        $this->收款.'狀態',
        self::$system_parameter['合約'].'狀態',
        '結案日期',
      ];

      $crm_contract = [];
      $course_data = self::get_course_data($params, 0, $this->get_or_pay);
      $list_search = $course_data['list_search'];
      // dump($list_search);exit;
      foreach($list_search as $key=>$v){
        $data['客戶簡稱'] = $v['show_name'];
        $data[self::$system_parameter['合約'].'號'] = $v['sn'];
        $data[self::$system_parameter['合約'].'金額'] = number_format($v['allmoney'], 2);
        $data[self::$system_parameter['合約'].'訂金'] = number_format($v['money'], 2);
        $data[$this->已收.'金額'] = number_format($v['money_count']['real_get_paid'], 2);
        $data[$this->收款.'率'] = number_format($v['money_count']['real_get_paid']/$v['allmoney']*100, 2).'%';
        $data['剩餘'.$this->預收.'款'] = number_format($v['money_count']['allmoney_prepaid'], 2);
        $data['未'.$this->出貨.'金額'] = number_format($v['money_count']['shipments_un'], 2);
        $data['已'.$this->出貨.'金額'] = number_format($v['money_count']['shipments'], 2);
        $data[$this->出貨.'率'] = number_format($v['money_count']['shipments']/$v['allmoney']*100, 2).'%';
        $data[$this->超收] = $v['real_get']>$v['allmoney'] ? number_format($v['real_get'] - $v['allmoney'], 2) : 0;
        $data['總損益'] = number_format($v['money_count']['tips'], 2);
        $data[$this->收款.'狀態'] = $v['flag3']==0 ? $this->收款.'中' : '款'.$this->收罄;
        $data[self::$system_parameter['合約'].'狀態'] = $v['flag']==1 ? '已簽約' : ($v['flag']==2 ? '問題案' : '');
        $data['結案日期'] = $v['endtime_format'];

        array_push($crm_contract, $data);
      }
      // dump($crm_contract);exit;
      $file_title = $this->收款.self::$system_parameter['合約']."歷程列表";
      parent::DataDbOut($crm_contract,$title,$list_start="A2",$file_title);
    }
    /*AJAX取得計算過後的合約歷程資料(CRM中使用)*/
    public function ajax_get_course(){
      $params = $_GET;
      $course_data = self::get_course_data($params, self::$pagecount, $this->get_or_pay);
      $words = [];
      $get_pay_words = ContractHelper::get_pay_words($this->get_or_pay);
      foreach ($get_pay_words as $key => $value) { $words[$key] = $value; }
      $course_data['words'] = $words;
      $this->ajaxReturn($course_data);
    }
    /*取得合約歷程的列表資料*/
    private static function get_course_data($params, $pagecount=0, $get_or_pay=0){
      // if($_GET['cate'] == '')	$_GET['cate'] = 1;
      $flag = $params['flag'] ?? '';
      $flag2 = $params['flag2'] ?? '1';
      $flag3 = $params['flag3'] ?? '-1';
      $course_data['flag2_flag3'] = $flag2.'_'.$flag3;

      $params['flag2'] = $flag2;
      $params['flag3'] = $flag3;
      $result = ContractHelper::get_contract_where_sql($params, $get_or_pay);
      // dump($result);
      /*等級選單*/
      $course_data['levels'] = $result['levels'];			
      /*通用產業選單*/
      $course_data['industr_all'] = $result['industr_all'];
      $course_data['industr2_search'] = $result['industr2_search'];

      $where_query = $result['where_query'];
      if($flag==''){ /*沒搜尋flag*/
        $where_query .= ' AND flag!=0'; /*預設只顯示非提案的合約*/
      }
      // dump($where_query);exit;

      $list = D("crm_contract c")->field('c.*')
                                ->join('LEFT JOIN crm_crm ON crm_crm.id=c.cid')
                                ->where($where_query)->order('id desc')->select();
      // dump($list);exit;
      foreach ($list as $key => $value) {
        $list[$key]['show_name'] = CustoHelper::get_crm_show_name($value['cid']);
        $money_count = MoneyHelper::count_contract_money($value['id'])['all'];
        $list[$key]['money_count'] = $money_count;
        
        $all['allmoney'] += $value['allmoney'];
        $all['money'] += $value['money'];

        $all['real_get'] += $money_count['real_get'];
        $all['real_get_paid'] += $money_count['real_get_paid'];
        $all['real_get_unpaid'] += $money_count['real_get_unpaid'];
        $all['shipments'] += $money_count['shipments'];
        $all['shipments_un'] += $money_count['shipments_un'];
        $all['sale_completed'] += $money_count['sale_completed'];
        $all['sale_uncompleted'] += $money_count['sale_uncompleted'];
        $all['allmoney_prepaid'] += $money_count['allmoney_prepaid'];
        $all['allmoney_xdj'] += $money_count['allmoney_xdj'];
        $all['allmoney_xqj'] += $money_count['allmoney_xqj'];
        $all['money_uncomplete'] += $money_count['money_uncomplete'];
        $all['tips'] += $money_count['tips'];
      }
      $course_data['all'] = $all;				// 總統計
      $course_data['total'] = count($list);	// 總數

      if($pagecount>0){
        $Page = new \Think\Page($course_data['total'], $pagecount);// 实例化分页类 传入总记录数和每页显示的记录数($pagecount)
        $Page->setConfig('header',"%TOTAL_ROW% 個合約");
        $Page->setConfig('prev',"上一頁");
        $Page->setConfig('next',"下一頁");
        $Page->setConfig('first',"第一頁");
        $Page->setConfig('last',"最後一頁 %END% ");
        $course_data['show'] = $Page->show();								// 分頁顯示
        $course_data['total_page'] = $Page->totalPages;						// 總頁數
        $list_search = array_slice($list, $Page->firstRow, $Page->listRows);// 對應頁數資料
      }else{
        $course_data['show'] = '';
        $course_data['total_page'] = 1;
        $list_search = $list;
      }
      
      foreach ($list_search as $key => $value) {
        // 設定結案時間
        $endtime_format = '';
        if($value['flag2']=='2'){
          $endtime_format = $value['endtime'] ? date('Y-m-d', $value['endtime']) : '';
        }
        $list_search[$key]['endtime_format'] = $endtime_format;

        $all_search['allmoney'] += $value['allmoney'];
        $all_search['money'] += $value['money'];

        $all_search['real_get'] += $value['money_count']['real_get'];
        $all_search['real_get_paid'] += $value['money_count']['real_get_paid'];
        $all_search['real_get_unpaid'] += $value['money_count']['real_get_unpaid'];
        $all_search['shipments'] += $value['money_count']['shipments'];
        $all_search['shipments_un'] += $value['money_count']['shipments_un'];
        $all_search['sale_completed'] += $value['money_count']['sale_completed'];
        $all_search['sale_uncompleted'] += $value['money_count']['sale_uncompleted'];
        $all_search['allmoney_prepaid'] += $value['money_count']['allmoney_prepaid'];
        $all_search['allmoney_xdj'] += $value['money_count']['allmoney_xdj'];
        $all_search['allmoney_xqj'] += $value['money_count']['allmoney_xqj'];
        $all_search['money_uncomplete'] += $value['money_count']['money_uncomplete'];
        $all_search['tips'] += $value['money_count']['tips'];
      }
      $course_data['list_search'] = $list_search;	// 搜尋合約列表
      $course_data['all_search'] = $all_search;	// 搜尋統計

      return $course_data;
    }

    /*款項明細頁面*/
    public function receivedetail(){
      $id=$_GET['id'];
      $crm_contract = D("crm_contract c")->find($id);
      if(!$crm_contract) $this->error('查無此'.self::$system_parameter['合約']);
      $crm_contract['show_name'] = CustoHelper::get_crm_show_name($crm_contract['cid']);
      $this->assign("crm_contract", $crm_contract);

      $count_data = MoneyHelper::count_contract_money($id);
      // dump($count_data['all']);
      $this->assign("money", $count_data['money']);
      $this->assign("all", $count_data['all']);

      $this->display('Course/receivedetail');
    }
  }
?>