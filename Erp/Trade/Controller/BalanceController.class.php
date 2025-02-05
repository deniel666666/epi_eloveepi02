<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use Photonic\MoneyHelper;
  use Photonic\MensHelper;
  use Photonic\SalaryHelper;
  use Photonic\ScheduleHelper;

  class BalanceController extends GlobalController {
    function _initialize($get_or_pay=0){
      parent::_initialize();
      parent::check_has_access(CONTROLLER_NAME, 'red');
      $this->assign('CONTROLLER_NAME', CONTROLLER_NAME);

      $powercat_id = 149;
      $powercat = D('powercat')->find($powercat_id);
      $this->powercat_current = $powercat;
      $this->assign('page_title_active', $powercat_id);	/*右上子選單active*/

      $time_pre = strtotime(date('Y-m-d').' -1month');
      $this->assign('ym_pre', date('Ym', $time_pre));

      $next_month = strtotime(date('Y-m-d').' +1Month');
      $num=0;
      $year=date("Y", $next_month);
      for($j=date("m", $next_month);$j>=1;$j--){
        $salary_ym[$num++]=$year."".str_pad($j,2,'0',STR_PAD_LEFT);
        if(count($salary_ym)>=8){ break; }
      }
      for($y=1;$y<=3;$y++){
        if(count($salary_ym)>=8){ break; }
        for($i=12;$i>=1;$i--){
          if(count($salary_ym)>=8){ break; }
          $salary_ym[$num++]=($year-$y)."".str_pad($i,2,'0',STR_PAD_LEFT);
        }
      }
      $this->assign("salary_ym",$salary_ym);
    }
    public function index(){
      /*更新本月報表資料*/
      try {
        $this->do_update_balance();
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->redirect('Balance/years');
    }
    public function years(){ /*以年度查看*/
      $this->display();
    }
    public function months(){ /*以月份查看(需指定年)*/
      $y = $_GET['y'] ?? date('Y');
      if(!$y){ $y = date('Y'); }
      $this->assign('y', $y);
      $this->display();
    }

    public function years_data_ajax(){
      $return_data = $this->years_data($_GET);
      // dump($return_data);exit;
      $this->ajaxReturn($return_data);
    }
    static private function years_data($get_data){
      $return_data = self::months_data('', $get_data); /*取得所有資料*/
      $years_obj = []; /*依年組織起來*/
      $in_columns = [];
      $out_columns = [];
      foreach ($return_data['months'] as $month) {
        if(!isset($years_obj[$month['y']])){
          $years_obj[$month['y']] = [
            'y' => $month['y'],
            'in_total' => 0,
            'in_tax' =>  0,
            'in_content' => [],
            'out_total' => 0,
            'out_tax' =>  0,
            'out_content' => [],
          ];
        }
        $in_total = $years_obj[$month['y']]['in_total'] + $month['in_total'];
        $years_obj[$month['y']]['in_total'] = is_float($in_total) ? round($in_total, 2) : $in_total;
        $in_tax = $years_obj[$month['y']]['in_tax'] + $month['in_tax'];
        $years_obj[$month['y']]['in_tax'] = is_float($in_tax) ? round($in_tax, 2) : $in_tax;
        $in_content = self::merge_month_column($years_obj[$month['y']]['in_content'], $month, 'in_content');
        $years_obj[$month['y']]['in_content'] = $in_content;

        $out_total = $years_obj[$month['y']]['out_total'] + $month['out_total'];
        $years_obj[$month['y']]['out_total'] = is_float($out_total) ? round($out_total, 2) : $out_total;
        $out_tax = $years_obj[$month['y']]['out_tax'] + $month['out_tax'];
        $years_obj[$month['y']]['out_tax'] = is_float($out_tax) ? round($out_tax, 2) : $out_tax;
        $out_content = self::merge_month_column($years_obj[$month['y']]['out_content'], $month, 'out_content');
        $years_obj[$month['y']]['out_content'] = $out_content;

        $in_columns = self::merge_month_column($in_columns, $month, 'in_content');
        $out_columns = self::merge_month_column($out_columns, $month, 'out_content');
      }
      $years = [];
      foreach ($years_obj as $year) {
        array_push($years, $year);
      }
      $return_data_years['years'] = $years;
      $return_data_years['in_columns'] = $in_columns;
      $return_data_years['out_columns'] = $out_columns;

      // dump($return_data);exit;
      return $return_data_years;
    }
    public function months_data_ajax(){
      $y = $_GET['y'] ?? date('Y');
      $return_data = self::months_data($y, $_GET);
      // dump($return_data['months']);exit;
      $this->ajaxReturn($return_data);
    }
    static private function months_data($y='', $get_data){
      $years = D('balance')->field('SUBSTRING(`ym`, 1,  4) as year')->group('year')->order('year desc')->select();
      $return_data['years'] = $years;

      $months = D('balance');
      if($y!=''){ $months = $months->where('ym LIKE "'.$y.'%"'); }
      $months = $months->order('ym asc')->select();
      $in_columns = [];
      $out_columns = [];
      foreach ($months as $key => $value) {
        $value['y'] = substr($value['ym'], 0, -2);
        $value['m'] = substr($value['ym'], -2);

        $value['in_content'] = json_decode($value['in_content'], true) ?? [];
        $value['out_content'] = json_decode($value['out_content'], true) ?? [];
        $months[$key] = $value;
        $in_columns = self::merge_month_column($in_columns, $value, 'in_content');
        $out_columns = self::merge_month_column($out_columns, $value, 'out_content');
      }
      $return_data['months'] = $months;
      $return_data['in_columns'] = $in_columns;
      $return_data['out_columns'] = $out_columns;

      // dump($return_data);exit;
      return $return_data;
    }


    /*AJAX:更新月統計*/
    /*http://eip.test/index.php/Balance/update_balance/ym/202305*/
    public function update_balance($ym=''){
      try {
        $this->do_update_balance($ym);
      } catch (\Exception $e) {
        $this->error($e->getMessage());
      }
      $this->success('操作成功');
    }
    static public function do_update_balance($ym=''){
      if(!$ym){ $ym = date('Ym'); }
      if(strlen($ym)<6){ throw new \Exception("月份有誤"); }
      $m = substr($ym, -2, 2);
      $y = substr($ym, 0, -2);
      if($m>'12' || $m<'01'){ throw new \Exception("月份有誤"); }

      /*處理款項(入&出)*/
      [$in_total, $in_tax, $in_content] = self::count_money($y, $m, $get_or_pay=0);
      [$out_total, $out_tax, $out_content] = self::count_money($y, $m, $get_or_pay=1);
      // dump($out_total);
      // dump($out_tax);
      // dump($out_content);

      $balance_data = [
        'ym' => $ym,
        'in_total' => $in_total,
        'in_tax' => $in_tax,
        'in_content' => json_encode((object)$in_content, JSON_UNESCAPED_UNICODE),
        'out_total' => $out_total,
        'out_tax' => $out_tax,
        'out_content' => json_encode((object)$out_content, JSON_UNESCAPED_UNICODE),
      ];
      // dump($balance_data);exit;
      $has_data = D('balance')->where('ym="'.$ym.'"')->find();
      if($has_data){
        D('balance')->where('ym="'.$ym.'"')->save($balance_data);
      }else{
        D('balance')->add($balance_data);;
      }
    }	
    static private function count_money($y, $m, $get_or_pay=0){
      $total = 0; $tax = 0; $content = [];

      $time_s = $y.'-'.$m.'-01';
      $time_s_stamp = strtotime($time_s);
      $time_e_stamp = strtotime(date('Y-m-t', $time_s_stamp).' +1Day');
      $time_e = date('Y-m-d', $time_e_stamp);

      if($get_or_pay==1){ /*計算付款*/
        /*處理薪資(匯薪列表)*/
        foreach ([1, 4] as $key => $item_id) { /*每月固定支出(薪資、保險)*/
          $accountant_salary = D('accountant_item')->where('id="'.$item_id.'"')->find();
          $content['k_'.$accountant_salary['id']] = [
            'id' => $accountant_salary['id'],
            'name' => $accountant_salary['name'],
            'order_id'=> $accountant_salary['order_id'],
            'num' => 0,
            'sub' => [],
          ];
          $accountant_salary_sub = D('accountant_item')->where('parent_id="'.$item_id.'"')->order('order_id asc')->select();
          foreach ($accountant_salary_sub as $sub) {
            $content['k_'.$accountant_salary['id']]['sub']['k_'.$sub['id']] = [
              'id' => $sub['id'],
              'name' => $sub['name'],
              'order_id'=> $sub['order_id'],
              'num' => 0,
            ];
          }
        }
        $cond['salary_date_s'] = $y.'-'.$m.'-01';
        $cond['salary_date_e'] = $y.'-'.$m.'-31';
        $salary = MensHelper::get_user_salary(0, $cond);
        // dump($salary);exit;
        foreach ($salary as $value) {
          /*本薪(不含時薪)*/
          $salary_basic = round(($value['total_pay_month'] + $value['total_rest_deduct']), 2);
          $total += $salary_basic;
          $content['k_'.'1']['num'] += $salary_basic;
          $content['k_'.'1']['sub']['k_'.'2']['num'] += $salary_basic;
          /*獎金*/
          $salary_bonus = round($value['total_bonus_award'], 2);
          $total += $salary_bonus;
          $content['k_'.'1']['num'] += $salary_bonus;
          $content['k_'.'1']['sub']['k_'.'3']['num'] += $salary_bonus;
          /*時薪*/
          $hour_count_detail = json_decode($value['hour_count_detail'], true) ?? [];
          if(count($hour_count_detail)){
            foreach ($hour_count_detail as $schedule_date_user) {
                $accountant_items = self::get_accountant_items([
                  'primary_key'=> $schedule_date_user['user_skill'], 
                  'content_table'=> 'user_skill',
                ],
                $get_or_pay);
                $accountant_item = $accountant_items['accountant_item'];
                $accountant_item_parent = $accountant_items['accountant_item_parent'];
                $content = self::set_default_data($content, $accountant_item_parent, $accountant_item);
                /*時薪-按工種分配*/
                $total += $schedule_date_user['total_pay_hour'];
                $content['k_'.$accountant_item_parent['id']]['num'] += $schedule_date_user['total_pay_hour'];
                $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] += $schedule_date_user['total_pay_hour'];
            }
          }

          $insurance = json_decode($value['insurance'], true);
          foreach ($insurance as $key => $value) {
            $accountant_items = self::get_accountant_items([
              'primary_key'=> $value['accountant_item_id'], 
              'content_table'=> 'accountant_item',
            ],
            $get_or_pay);
            $accountant_item = $accountant_items['accountant_item'];
            $accountant_item_parent = $accountant_items['accountant_item_parent'];
            $content = self::set_default_data($content, $accountant_item_parent, $accountant_item);
            /*公司負擔額*/
            $insurance_company_pay = round($value['insurance_company_pay'], 2);
            $total += $insurance_company_pay;
            $content['k_'.$accountant_item_parent['id']]['num'] += $insurance_company_pay;
            $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] += $insurance_company_pay;
          }
        }
        // dump($content);exit;
      }

      $money_rule = self::money_calculate_rule();
      $where_query = 'c.get_or_pay='.$get_or_pay.' AND'.'
               '.$money_rule['money_where'].' AND m.getedflag=1 AND
                 m.exptime>='.$time_s_stamp.' AND m.exptime<'.$time_e_stamp;
      // dump($where_query);
      $money = [];
      foreach (MoneyHelper::$money_tables as $money_tables) {
        $money_data = D($money_tables.' m')->field('m.*, c.get_or_pay, c.cate')
                           ->join('LEFT JOIN crm_contract c on c.id=m.caseid')
                           ->where($where_query)
                           ->order('m.id asc')
                           ->select();
        $money = array_merge($money, $money_data);
      }
      // dump($money);
      [$total2, $tax2, $content2] = self::count_money_data($money, $get_or_pay);
      // dump($total2);
      // dump($tax2);
      // dump($content2);
      $total += $total2;
      $tax += $tax2;
      $content = self::merge_month_column($content, ['data'=>$content2], 'data');
      // dump($total);
      // dump($tax);
      // dump($content);exit;
      return [$total, $tax, $content];
    }
    static private function count_money_data(array $money, $get_or_pay=0){
      $total = 0; $tax = 0; $content = [];
      $money_rule = self::money_calculate_rule();

      foreach ($money as $key => $value) {
        if(self::$control_money_input==0){ /*輸入方式為未稅*/
          $money_num = $value[$money_rule['money_column']] + $value[$money_rule['tax_column']]; /*把稅金也含入統計*/
        }else{ /*輸入方式為實收*/
          $money_num = $value[$money_rule['money_column']];
        }
        $total += $money_num;
        $tax += $value[$money_rule['tax_column']];

        if($value['cate']==1 && $value['prepaid']==0){ /*SEO合約 且 非預收付*/
          $crm_contract_unit = D('crm_contract_unit')->where('pid="'.$value['caseid'].'"')->find();
          $ships = [
            [
              'caseid' => $value['caseid'],
              'moneyid' => $value['id'],
              'money' => $value[$money_rule['money_column']],
              'contract_unit_id' => $crm_contract_unit ? $crm_contract_unit['id'] : 0,
              'name' => $crm_contract_unit ? $crm_contract_unit['name'] : 'SEO',
              'content' => $crm_contract_unit ? $crm_contract_unit['type'] : '',
              'num' => 1,
            ]
          ];
        }else{
          $ships = MoneyHelper::get_ships($value['caseid'], $value['id']);
        }
        foreach ($ships as $ship) {
          $accountant_items = self::get_accountant_items([
            'primary_key'=>$ship['contract_unit_id'], 
            'content_table'=>$ship['content_table'],
          ],
          $get_or_pay);
          $accountant_item = $accountant_items['accountant_item'];
          $accountant_item_parent = $accountant_items['accountant_item_parent'];
          $content = self::set_default_data($content, $accountant_item_parent, $accountant_item);

          if(self::$control_money_input==0){ /*輸入方式為未稅*/
            /*品項也計算稅金*/
            if($ship['content_table']=='accountant_item'){ /*如果是損益*/	
            }
            else{ /*如果是出貨品項(crm_contract_unit、user_skill)*/
              $ship['money'] = round($ship['money'] * ($value[$money_rule['tax_column']]>0 ? (1+TAX_RATE) : 1));
            }
          }else{ /*輸入方式為實收*/
          }

          $add_num = $ship['money']<=$money_num ?  $ship['money'] : $money_num;
          $num = $content['k_'.$accountant_item_parent['id']]['num'] + $add_num;
          $num = is_float($num) ? round($num, 2) : $num;
          $content['k_'.$accountant_item_parent['id']]['num'] = $num;
          $num_sub = $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] + $add_num;
          $num_sub = is_float($num_sub) ? round($num_sub, 2) : $num_sub;
          $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] = $num_sub;
          $money_num -= $add_num;
        }

        /*把損益也列入統計項目*/
        $tip_money = (float)$value['tips'] + (float)$value['tips1'];
        $accountant_items = self::get_accountant_items([
          'primary_key'=>$get_or_pay==0 ? 998 : 999, 
          'content_table'=>'accountant_item',
        ],
        $get_or_pay);
        $accountant_item = $accountant_items['accountant_item'];
        $accountant_item_parent = $accountant_items['accountant_item_parent'];
        $content = self::set_default_data($content, $accountant_item_parent, $accountant_item);
        $total += $tip_money;
        $num = $content['k_'.$accountant_item_parent['id']]['num'] + $tip_money;
        $num = is_float($num) ? round($num, 2) : $num;
        $content['k_'.$accountant_item_parent['id']]['num'] = $num;
        $num_sub = $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] + $tip_money;
        $num_sub = is_float($num_sub) ? round($num_sub, 2) : $num_sub;
        $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] = $num_sub;
      }
      return [$total, $tax, $content];
    }
    static private function set_default_data($content, $accountant_item_parent, $accountant_item){
      if(!isset($content['k_'.$accountant_item_parent['id']])){
        $content['k_'.$accountant_item_parent['id']] = [
          'id' => $accountant_item_parent['id'],
          'name' => $accountant_item_parent['name'],
          'order_id' => $accountant_item_parent['order_id'],
          'num' => 0,
          'sub' => [],
        ];
      }
      if(!isset($content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']])){
        $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']] = [
          'id' => $accountant_item['id'],
          'name' => $accountant_item['name'],
          'order_id' => $accountant_item['order_id'],
          'num' => 0,
        ];
      }
      return $content;
    }
    static private function money_calculate_rule($rule='cash'){ /*篩選款項方式*/
      if($rule=='service'){/*案件進程*/
        return [
          'money_where' => 'm.prepaid=0',
          'money_column' => 'dqmoney',
          'tax_column' => 'fax',
        ];
      }else{/*cash.現金流*/
        return [
          'money_where' => 'm.xqj!=0',
          'money_column' => 'xqj',
          'tax_column' => 'xqj_tax',
        ];
      }
    }

    /**
     * 依據傳入的參數回傳對應的統計名目(本層與父層)
     * @param array $params
     * - primary_key: 請款內容對應項目表的主鍵值
     * - content_table: 請款內容對應項目表
     * @param int $get_or_pay 收付款(0.收 1.付)
     * @return array
     * - accountant_item: 本層統計項目
     * - accountant_item_parent: 父層統計項目
    */
    static private function get_accountant_items($params=[], $get_or_pay=0){
      $primary_key = $params['primary_key'] ?? '';
      $content_table = $params['content_table'] ?? 'crm_contract_unit';

      // 找出目標會計名稱ID(本層)
      $accountant_id = 0; /*預設目標為0*/
      if($content_table=='accountant_item'){ /*會計名目表尋找*/
        $accountant_id = $primary_key;
      }
      else if($content_table=='crm_contract_unit'){ /*依合約簽訂的商品尋找(不必區分收付款)*/
        $crm_contract_unit = D('crm_contract_unit')->where('id="'.$primary_key.'"')->find();
        if($crm_contract_unit){ 
          $crm_cum_cat_unit = D('crm_cum_cat_unit')->where('id="'.$crm_contract_unit['cat_unit_id'].'"')->find();
          if($crm_cum_cat_unit){ 
            $accountant_id = $crm_cum_cat_unit['accountant_id'];						
          }
        }
      }
      else if($content_table=='user_skill'){ /*依合約簽訂的人力尋找(需區分收付款)*/
        $user_skill = D('user_skill')->where('id="'.$primary_key.'"')->find();
        if($user_skill){
          $accountant_id = $get_or_pay==0 ? $user_skill['account_in_id'] : $user_skill['account_out_id'];
        }
      }

      /*依目標會計名稱ID回找本層與父層統計資料*/
      $accountant_item = ['id'=>0, 'name'=>'無項目', 'parent_id'=>0, 'order_id'=>9999]; /*預設為無項目(本層)*/
      $accountant_item_parent = ['id'=>0, 'name'=>'無項目', 'order_id'=>9999];					/*預設為無項目(父層)*/
      if($accountant_id){ /*有目標會計名稱*/
        $accountant_item_db = D('accountant_item')->field('id, name, parent_id, order_id')->where('id="'.$accountant_id.'"')->find();
        if($accountant_item_db){
          $accountant_item = $accountant_item_db;
          $accountant_item_parent = D('accountant_item')->field('id, name, order_id')->where('id="'.$accountant_item_db['parent_id'].'"')->find();
        }
      }
      return ['accountant_item'=>$accountant_item, 'accountant_item_parent'=>$accountant_item_parent];
    }
    static private function merge_month_column($columns, $month, $column_name){
      foreach ($month[$column_name] as $column) {
        if(isset($columns['k_'.$column['id']])){
          $column_main = $columns['k_'.$column['id']];
        }else{
          $column_main = [
            'id' => $column['id'],
            'name' => $column['name'],
            'order_id' => $column['order_id'],
            'num' => 0,
            'sub' => [],
          ];
        }
        $column_num = $column_main['num'] + $column['num'];
        $column_main['num'] = is_float($column_num) ? round($column_num, 2) : $column_num;
        
        $column_sub = $column_main['sub'];
        foreach ($column['sub'] as $sub) {
          if(!isset($column_sub['k_'.$sub['id']])){
            $column_sub['k_'.$sub['id']] = [
              'id' => $sub['id'],
                'name' => $sub['name'],
                'order_id' => $sub['order_id'],
                'num' => 0,
            ];
          }
          $sub_num = $column_sub['k_'.$sub['id']]['num'] + $sub['num'];
          $column_sub['k_'.$sub['id']]['num'] = is_float($sub_num) ? round($sub_num, 2) : $sub_num;
        }
        $column_main['sub'] = self::sort_keys($column_sub);
        $columns['k_'.$column['id']] = $column_main;
      }
      $columns = self::sort_keys($columns);
      return $columns;
    }
    static private function sort_keys($dict){
      $array = [];
      foreach ($dict as $value) {
        array_push($array, $value);
      }
      usort($array, function($a, $b){ /*先依排序小到大，若order_id相同再依id小到大*/
        if($a['order_id']!=$b['order_id']){
          return (int)$a['order_id'] > (int)$b['order_id']; 
        }else{
          return (int)$a['id'] > (int)$b['id'];
        }
      });
      $dict_new = [];
      foreach ($array as $value) {
        $dict_new['k_'.$value['id']] =  $value;
      }
      return $dict_new;
    }


    public function contract(){ /*以合約查看*/
      $caseid = $_GET['caseid'] ?? '';
      if(!$caseid){ $this->error('連結有誤'); }
      $this->assign('caseid', $caseid);
      $this->display();
    }
    public function contract_data_ajax(){
      $caseid = I('get.caseid', '');  

      /*合約類別*/
      $crm_cum_cat = D('crm_cum_cat')->index('id')->select();
      /*合約狀態*/
      $crm_cum_flag = D('crm_cum_flag')->index('id')->select();

      $field_query = 'cc.id, cc.cid, cc.get_or_pay, cc.belongs_to, cc.pay_to, cc.sn, cc.cate, cc.topic, cc.flag, SUBSTR(FROM_UNIXTIME(cc.sign_date), 1, 10) AS sign_date_f, cc.allmoney, cc.topic, crm.name';
      /*此合約*/
      $contract_main = D('crm_contract cc')->field($field_query )
                                           ->join("LEFT JOIN crm_crm AS crm ON crm.id=cc.cid")
                                           ->where('cc.id="'.$caseid.'"')->order('id asc')->select();
      /*副約*/
      $contract_belongs_to = D('crm_contract cc')->field($field_query )
                                           ->join("LEFT JOIN crm_crm AS crm ON crm.id=cc.cid")
                                           ->where('cc.belongs_to="'.$caseid.'"')->order('id asc')->select();
      /*支出合約*/
      $contract_pay_to = D('crm_contract cc')->field($field_query )
                                           ->join("LEFT JOIN crm_crm AS crm ON crm.id=cc.cid")
                                           ->where('cc.pay_to="'.$caseid.'"')->order('id asc')->select();
      $caseids = [-1];
      foreach (array_merge($contract_main, $contract_belongs_to, $contract_pay_to) as $value) {
        array_push($caseids, $value['id']);
      }
      [$in_total, $in_tax, $in_content] = self::count_money_by_contract($caseids, $get_or_pay=0);
      [$out_total, $out_tax, $out_content] = self::count_money_by_contract($caseids, $get_or_pay=1);

      $this->ajaxReturn([
        'crm_cum_cat' => $crm_cum_cat,
        'crm_cum_flag' => $crm_cum_flag,
        'contract_main' => $contract_main,
        'contract_belongs_to' => $contract_belongs_to,
        'contract_pay_to' => $contract_pay_to,
        'in_total' => $in_total,
        'in_columns' => $in_content,
        'out_total' => $out_total,
        'out_columns' => $out_content,
      ]);
    }
    static private function count_money_by_contract($caseids, $get_or_pay=0){
      $total = 0; $tax = 0; $content = [];

      if($get_or_pay==1){ /*計算付款*/
        /*處理薪資(匯薪列表)*/
        foreach ([1, 4] as $key => $item_id) { /*每月固定支出(薪資、保險)*/
          $accountant_salary = D('accountant_item')->where('id="'.$item_id.'"')->find();
          $content['k_'.$accountant_salary['id']] = [
            'id' => $accountant_salary['id'],
            'name' => $accountant_salary['name'],
            'order_id'=> $accountant_salary['order_id'],
            'num' => 0,
            'sub' => [],
          ];
          $accountant_salary_sub = D('accountant_item')->where('parent_id="'.$item_id.'"')->order('order_id asc')->select();
          foreach ($accountant_salary_sub as $sub) {
            $content['k_'.$accountant_salary['id']]['sub']['k_'.$sub['id']] = [
              'id' => $sub['id'],
              'name' => $sub['name'],
              'order_id'=> $sub['order_id'],
              'num' => 0,
            ];
          }
        }

        /*時薪*/
        $params = [
          'contract_ids' => $caseids,
          'turn_salary_time' => 1,
        ];
        $schedules_data = ScheduleHelper::get_schedules($params, true, true);
        // dump($schedules_data);exit;
        if(count($schedules_data)){
          foreach ($schedules_data as $schedule_date_user) {
            $accountant_items = self::get_accountant_items([
              'primary_key'=> $schedule_date_user['user_skill'], 
              'content_table'=> 'user_skill',
            ],
            $get_or_pay);
            $accountant_item = $accountant_items['accountant_item'];
            $accountant_item_parent = $accountant_items['accountant_item_parent'];
            $content = self::set_default_data($content, $accountant_item_parent, $accountant_item);
            /*時薪-按工種分配*/
            $user_pay = $schedule_date_user['pay_total'] + (int)$schedule_date_user['change_num'];
            $total += $user_pay;
            $content['k_'.$accountant_item_parent['id']]['num'] += $user_pay;
            $content['k_'.$accountant_item_parent['id']]['sub']['k_'.$accountant_item['id']]['num'] += $user_pay;
          }
        }
        // dump($content);exit;
      }

      $money_rule = self::money_calculate_rule();
      // dump($money_rule);
      $where_query = 'c.get_or_pay='.$get_or_pay.' AND'.'
                      '.$money_rule['money_where'].' AND m.ship_status=1 AND m.queryflag=1 AND m.getedflag=1 AND
                      m.caseid in ('.implode(',', $caseids).')';
      // dump($where_query);
      $money = [];
      foreach (MoneyHelper::$money_tables as $money_tables) {
        $money_data = D($money_tables.' m')->field('m.*, c.get_or_pay, c.cate')
                           ->join('LEFT JOIN crm_contract c on c.id=m.caseid')
                           ->where($where_query)
                           ->order('m.id asc')
                           ->select();
        $money = array_merge($money, $money_data);
      }
      // dump($money);
      [$total2, $tax2, $content2] = self::count_money_data($money, $get_or_pay);
      // dump($total2);
      // dump($tax2);
      // dump($content2);exit;
      $total += $total2;
      $tax += $tax2;
      $content = self::merge_month_column($content, ['data'=>$content2], 'data');
      return [$total, $tax, $content];
    }
  }
?>