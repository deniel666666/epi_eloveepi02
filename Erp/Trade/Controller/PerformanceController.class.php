<?php
  namespace Trade\Controller;
  use Trade\Controller\GlobalController;

  use Photonic\CustoHelper;
  use Photonic\ProductHelper;
  use Photonic\ContractHelper;

  class PerformanceController extends GlobalController 
  {
    function _initialize()
    {
      parent::_initialize();
      parent::check_has_access(CONTROLLER_NAME, 'red');

      $num=0;
      $year=date("Y",time());
      for($y = 0;$y <= 3;$y++){
        $years[$y] = $year - (3 - $y);
      }
      for($m = 1;$m <= 12;$m++){
        // $months[$m] = str_pad($m,2,'0',STR_PAD_LEFT);
        $months[$m] = $m;
      }
      $this->assign("years",$years);
      $this->assign("months",$months);

      $this->assign('page_title', '績效列表');
      $this->assign('page_title_link_self', U('Performance/index'));
      $this->assign('page_title_active', 108);  /*右上子選單active*/
    }
    
    function index(){
      $this->display();
    }

    function group(){
      $group_id = $_GET['group_id'];
      $type = $_GET['type'];
      switch ($type) {
        case 'depart': // 依部門
          $groups = D('eip_apart')->field('id, name')->find($group_id);
          break;
        
        case 'team': // 依組別
          $groups = D('eip_team')->field('id, name')->find($group_id);
          break;

        default:
          $this->error('請選擇查詢方式');
          break;
      }

      if(empty($groups))
        $this->error('此部門或組別不存在');

      $this->assign("group_name",$groups['name']);
      $this->display();
    }

    public function get_perform_data(){
      $return_data = [];

      $year = $_GET['year'] ? $_GET['year'] : date("Y",time()); // 搜尋年
      $month = $_GET['month']; // 搜尋月
      $type = $_GET['type'] ? $_GET['type'] : 'depart'; // 搜尋方式
      $group_where = $_GET['group_id'] ? 'id = '.$_GET['group_id'] : 'true'; // 目標群組id

      // 處理權限
      $acc = parent::get_my_access();
      $see_all = (session('eid')!=self::$top_adminid && $acc['performance_all'] == 0) ? false : true;

      // 找出搜尋的全部部門或組別
      $id_in = [0];
      switch ($type) {
        case 'depart': // 依部門
          if($see_all){ // 查看所有有開啟kpi的部門
            $groups = D('eip_apart')->field('id, name')->where('is_kpi =1 and `status`=1 and '.$group_where)->order('sort asc, id asc')->select();
          }else{
            $apartmentid = D('eip_user')->find(session('eid'))['apartmentid']; // 自己的部門ID
            // 查看所有有開啟kpi的部門且部門長是自己、或是自己所屬於此部門
            $groups = D('eip_apart')->field('id, name, boss_id')->where('is_kpi =1 and `status`=1 and '.$group_where.' and ( boss_id='.session('eid').' or id ='.$apartmentid.' )')->order('sort asc, id asc')->select();
          }

          $only_member = ' and true';
          array_walk($groups, function(&$item, $key)use(&$id_in, &$only_member, $see_all){
            array_push($id_in, $item['id']); // 記錄要找出的所有部門

            if($see_all || $item['boss_id']==session('eid')){  // 看全部或你是部門長
              $group_member= D('eip_user')->field('id')->where('is_kpi =1 and `is_job`=1 and `status`=1 and apartmentid ='.$item['id'])->select(); // 記錄此群體有哪些員工
              $item['group_member'] = [];
              array_walk($group_member, function(&$item2, $key)use(&$item){ array_push($item['group_member'], $item2['id']); }); // 從各個物件中抓出id
            }else{ // 你只是部員
              $item['group_member'] = [session('eid')];
              $only_member = 'and id ='.session('eid');
            }
          });

          $id_in = 'apartmentid in ('.join(',', $id_in).')'.$only_member;
          break;
        
        case 'team': // 依組別
          if($see_all){ // 查看所有有開啟kpi的組別
            $groups = D('eip_team')->field('id, name')->where('is_kpi =1 and `status`=1 and id !='.self::$top_teamid.' and '.$group_where)->order('sort asc, id asc')->select();
          }else{
            // 查看所有有開啟kpi的組別且組長是自己、或是自己所屬於此組
            $groups = D('eip_team')->field('id, name')->where('is_kpi =1 and `status`=1 and id !='.self::$top_teamid.' and '.$group_where." and (boss_id=".session('eid')." or childeid like '\"%".session('eid')."%\"' )")->order('sort asc, id asc')->select();
          }

          array_walk($groups, function(&$item, $key)use(&$id_in, $see_all){ $this->get_all_member_in_team($item, $id_in, $item['id'], $see_all); });
          $id_in = 'id in ('.join(',', $id_in).')';
          break;

        default:
          return;
          break;
      }
      $return_data['groups'] = $groups;

      // 找出相關人員
      $users = D('eip_user')->field('id, name')->where('is_kpi =1 and `is_job`=1 and `status`=1 and '.$id_in)->select();
      $return_data['users'] = $users;

      // 依搜尋年月整理人員績效
      $performance = [];
      $month_range = $month ? range($month,$month) : range(1,12);
      foreach ($month_range as $month) { // 依月份整理績效
        if( strtotime($year."/".($month)."/01") > strtotime(date("Y/m",time()).'/01')){ // 年月在目前的時間之後
          continue; // 跳過
        }
        $s_time= strtotime($year."/".$month."/01 00:00:00");
        $e_time= ($month==12) ? strtotime(($year+1)."/01/01 00:00:00") : strtotime($year."/".($month+1)."/01 00:00:00");
        $performance[$year][$month] = [];

        // 依個人取得績效、目標
        foreach ($users	 as $key => $value) {
          $performance[$year][$month][$value['id']] = $this->get_user_performance($value['id'], $s_time, $e_time);
        }
      }
      // dump($performance);exit;
      $return_data['performance'] = $performance;
      $this->ajaxreturn($return_data);
    }

    function get_all_member_in_team(&$pass_item, &$re_array, $team_id, $see_all){
      $group_member = [];
      $team = D('eip_team')->field('id, name, boss_id, childeid')->find($team_id);
      $member =  str_replace( '"', '', explode('、', $team['childeid']) );
      
      if($see_all || $team['boss_id'] == session('eid')){ // 看全部或你是組長
        // 放入組長id
        array_push($re_array, $team['boss_id']); // 記錄要找出的所有員工
        array_push($group_member, $team['boss_id']); // 記錄此群體有哪些員工
        
        // 放入組員id
        array_walk($member, function($item, $key)use(&$re_array, &$group_member){
          array_push($re_array, $item); // 記錄要找出的所有員工
          array_push($group_member, $item); // 記錄此群體有哪些員工
        });
      }else{ // 你只是組員
        if(in_array(session('eid'), $member)){// 你有在組中
          array_push($re_array, session('eid')); // 記錄要找出的所有員工
          array_push($group_member, session('eid')); // 記錄此群體有哪些員工
        }
      }

      $pass_item['group_member'] = $group_member;
    }

    function get_user_performance($user_id, $s_time, $e_time){
      $data = [];

      // 找出所有時間區間內的績效模組
      $api_models = D('kpimodel_association ka')
                      ->field('ka.time, k.*')
                      ->join('kpimodel k on k.id=ka.kpimodel_id' ,'left')
                      ->where('ka.user_id ='.$user_id.' and ka.time <='.$e_time)
                      ->order('ka.time asc')
                      ->select();
      $api_model_sort = [];
      array_walk($api_models, function($item, $key)use(&$api_model_sort){  // 只留同一日中最後設定的模組
        $api_model_sort[$item['time']] = $item;
      });
      $api_model_sort = array_values($api_model_sort); // 重設index
      array_walk($api_model_sort, function($item, $key)use(&$api_model_sort, $s_time, $e_time){
        $api_model_sort[$key]['e_time'] = isset($api_model_sort[$key+1])  ? $api_model_sort[$key+1]['time'] : $e_time;  // 設定個模組的結束時間
        $api_model_sort[$key]['s_time'] = $api_model_sort[$key]['time'] > $s_time ? $api_model_sort[$key]['time'] : $s_time;  // 數字化開始時間
        $api_model_sort[$key]['dividual_account'] = (array)json_decode($api_model_sort[$key]['dividual_account']);
        $api_model_sort[$key]['account_bonus'] = (array)(array)json_decode($api_model_sort[$key]['account_bonus']);
        $api_model_sort[$key]['dividual_event'] = (array)json_decode($api_model_sort[$key]['dividual_event']);
        $api_model_sort[$key]['event_bonus'] = (array)json_decode($api_model_sort[$key]['event_bonus']);
      });

      $aim = 0;
      $a_count = ['complete'=>0, 'out'=>0, 'in'=>0, 'bonus'=>0, 'items'=>[]];
      $e_count = ['complete'=>0, 'out'=>0, 'in'=>0, 'bonus'=>0, 'items'=>[]];
      foreach ($api_model_sort as $kpi_k => $kpi_v) {
        // 目標統計
        $e_pre_minutes = strtotime( date("Y/m/d",$kpi_v['e_time']).' -1 Days'); // 結束時間前一天，因為結束時間可能會為下個月第一天，減一天確保回到查詢的月份
        if($e_pre_minutes > $kpi_v['s_time']){
          $days = date("t", $e_pre_minutes); // 該月天數
          $datediff = round( ($kpi_v['e_time'] - $kpi_v['s_time']) / (60 * 60 * 24) ); // 模組天數
          // dump('e_time:'. $e_pre_minutes);
          // dump('s_time:'.$kpi_v['s_time']);
          // dump('datediff:'.$datediff);
          // dump('days:'.$days);
          $month_use_ratio = (float)$datediff / $days; // 模組套用占整月天數之比
          $aim += $kpi_v['aim'] * $month_use_ratio; // 依比例累計目標

          if($kpi_v['use_account'] == 1){ // 要依會計入帳日計算
            // 找出模組時間區間內到付的款項
            // SEO請款(只記收款)
            $crm_seomoney = D('crm_seomoney as m')->field('
                                 c.id, 
                                 c.eid as user_id, 
                                 c.allmoney,
                                 c.cate,
                                 c.id as contract_id,
                                 cc.name as cate_name,
                                 FROM_UNIXTIME(m.exptime,"%Y-%m-%d %H:%i:%s") as date, 
                                 (m.dqmoney-m.fax) as money, 
                                 c.count_type, 
                                 c.sn as no, 
                                 cc.name, 
                                 cr.id as crm_id
                                ')
                ->join('crm_contract as c on c.id = m.caseid', 'left')
                ->join('crm_crm as cr on cr.id = c.cid', 'left')
                ->join('crm_cum_cat as cc on cc.id = c.cate', 'left')
                ->where('c.get_or_pay=0 AND m.prepaid=0 AND c.eid = '.$user_id.' AND m.exptime >= '.$kpi_v['s_time'].' AND m.exptime < '.$kpi_v['e_time'].' AND c.flag=1 AND c.flag2!=3') //  AND cc.`status`=1
                ->order('m.exptime asc')
                ->select();
            // 其他請款(只記收款)
            $crm_othermoney = D('crm_othermoney as m')->field('
                                 c.id, 
                                 c.eid as user_id,
                                 c.allmoney,
                                 c.cate,
                                 c.id as contract_id,
                                 cc.name as cate_name,
                                 FROM_UNIXTIME(m.exptime,"%Y-%m-%d %H:%i:%s") as date, 
                                 (m.dqmoney-m.fax) as money, 
                                 c.count_type, 
                                 c.sn as no, 
                                 cc.name, 
                                 cr.id as crm_id
                                ')
                ->join('crm_contract as c on c.id = m.caseid', 'left')
                ->join('crm_crm as cr on cr.id = c.cid', 'left')
                ->join('crm_cum_cat as cc on cc.id = c.cate', 'left')
                ->where('c.get_or_pay=0 AND m.prepaid=0 AND c.eid = '.$user_id.' AND m.exptime >= '.$kpi_v['s_time'].' AND m.exptime < '.$kpi_v['e_time'].' AND c.flag=1 AND c.flag2!=3') //  AND cc.`status`=1
                ->order('m.exptime asc')
                ->select();
            $account = array_merge($crm_seomoney, $crm_othermoney);
            // dump($account);
            foreach ($account as $key => $value) {
              $account[$key]['show_name'] = CustoHelper::get_crm_show_name($value['crm_id']);
              $account[$key]['title']	= "簽訂".self::$system_parameter['合約'];
            }

            $a_count = $this->calculate('account', $kpi_v, $account, $a_count, $month_use_ratio);
          }

          if($kpi_v['use_event'] == 1){ // 要依事件簿完成日計算
            // 找出所有時間區間內完成的事件(正規事件)
            $event = D('eve_steps as es')->field('
                            e.id, 
                            e.cum_id,
                            es.user_id, 
                            c.cate,
                            c.allmoney,
                            c.id as contract_id,
                            cc.name as cate_name,
                            FROM_UNIXTIME(es.kpi_time,"%Y-%m-%d %H:%i:%s") as date, 
                            es.price as money, 
                            es.count_type, 
                            e.evesno as no, 
                            cc.name, 
                            e.title,
                            es.content,
                            es.estimated_time,
                            es.exact_time
                          ')
                          ->join('eve_events as e on e.id = es.eve_id', 'left')
                          ->join('crm_contract as c on c.id = e.caseid', 'left')
                          ->join('crm_crm as cr on cr.id = e.cum_id', 'left')
                          ->join('crm_cum_cat as cc on cc.id = c.cate', 'left')
                          ->where('es.user_id = '.$user_id.' and es.kpi_time >= '.$kpi_v['s_time'].' and es.kpi_time < '.$kpi_v['e_time'].' and (es.step_id=3 OR es.step_id=5) and es.`status`=1')
                          ->order('es.kpi_time asc')
                          ->select();
            // 找出所有時間區間內完成的往返單
            $wrong_job = D('wrong_job as wj')->field('
                            e.id, 
                            e.cum_id,
                            wj.user_id, 
                            c.cate,
                            c.allmoney,
                            c.id as contract_id,
                            cc.name as cate_name,
                            FROM_UNIXTIME(wj.complete_time,"%Y-%m-%d %H:%i:%s") as date, 
                            wj.money, 
                            es.count_type, 
                            e.evesno as no, 
                            cc.name, 
                            e.title,
                            wj.content,
                            wj.estimated_time,
                            wj.exact_time
                          ')
                          ->join('eve_steps as es on es.id = wj.steps_id', 'left')
                          ->join('eve_events as e on e.id = wj.eve_id', 'left')
                          ->join('crm_contract as c on c.id = e.caseid', 'left')
                          ->join('crm_crm as cr on cr.id = e.cum_id', 'left')
                          ->join('crm_cum_cat as cc on cc.id = c.cate', 'left')
                          ->where('wj.user_id = '.$user_id.' and wj.complete_time is not null and wj.complete_time >= '.$kpi_v['s_time'].' and wj.complete_time < '.$kpi_v['e_time'].' and es.step_id=3')
                          ->order('wj.complete_time asc')
                          ->select();
            $event_all = array_merge($event, $wrong_job);
            foreach ($event_all as $key => $value) {
              $event_all[$key]['show_name'] = CustoHelper::get_crm_show_name($value['cum_id']);
            }

            $e_count = $this->calculate('event', $kpi_v, $event_all, $e_count, $month_use_ratio);
          }
        }
      }
      $data['account'] = $a_count;
      $data['event'] = $e_count;
      $data['aim'] = round($aim);

      return $data;
    }

    function calculate($cal_type, $kpi_modle, $items, $cal_array, $month_use_ratio){
      $use_sum = 'use_'.$cal_type.'_sum';		// 合計或分開計
      $total = 'total_'.$cal_type; 			// 合約共用的pv值
      $dividual = 'dividual_'.$cal_type;		// 各合約的pv值
      $bonus = $cal_type.'_bonus';			// 獎金
      $pv_bonus = $cal_type.'_pv_bonus';		// 使用pv值比對獎金區間
      $accum_bonus = $cal_type.'_accum_bonus';// 累進回饋率

      $pv_sum = 0; // PV值統計
      foreach ($items as $key => $value) {
        // 完成統計
        $cal_array['complete'] += $value['money'];
        if($value['count_type'] === '1'){ // 外單
          $cal_array['out'] += $value['money'];
        }elseif($value['count_type'] === '0'){ // 內單
          $cal_array['in'] += $value['money'];
        }

        // PV值統計
        if($kpi_modle[$use_sum] == 1 || !in_array(121, self::$use_function_top)){ // 合約類別加總計算 或 未啟用商品管理
          $pv_sum += $value['money'] * (float)$kpi_modle[$total];
        }
        else{ // 合約類別分開計算
          if($value['contract_id']){ // 有合約
            // 績效計算
            $unit_contracts = ContractHelper::get_crm_contract_unit($value['contract_id'])['cat_units'];
            foreach ($unit_contracts as $unit) {
              $unit_pv_value = 1; // 預設 pv值為1 
              $u_id = 'u_'.$unit['cat_unit_id']; // 各執行項目pv值的key
              if(isset($kpi_modle[$dividual][$u_id])){
                if($kpi_modle[$dividual][$u_id] !=''){
                  $unit_pv_value = (float)$kpi_modle[$dividual][$u_id];
                }
              }

              $_POST['cond'] = ['id'=>$unit['cat_unit_id']];
              $cat_units = ProductHelper::get_cat_unit($status='', $get_or_pay=0)['cat_units'];
              if($cat_units){
                $unit_pv_value = $unit_pv_value * $cat_units[0]['profit'];
              }

              // 績效金額(會計請款或事件簿績效) * 各執行單位金額佔合約總額的比率 * 各執行單位pv值(內含BV值)
              $pv_sum += $value['money'] * ((float)$unit['total_dis']/$value['allmoney']) * $unit_pv_value;
            }
          }
          else{ // 免合約
            $unit_pv_value = 1; // 預設 pv值為1 
            if(isset($kpi_modle[$dividual]['u_0'])){
              if($kpi_modle[$dividual]['u_0'] !=''){
                $unit_pv_value = (float)$kpi_modle[$dividual]['u_0'];
              }
            }
            $pv_sum += $value['money'] * $unit_pv_value; // 績效金額(會計請款或事件簿績效) * 免合約pv值
          }
        }

        // 記錄內外單
        if($value['count_type']=='0'){
          $value['count_type']='內單';
        }elseif($value['count_type']=='1'){
          $value['count_type']='外單';
        }

        // 更改金額格式
        $value['money'] = number_format($value['money']);
        $value['cate_name'] = $value['cate_name'] ? $value['cate_name'] : '免'.self::$system_parameter['合約'];
        array_push($cal_array['items'], $value);			
      }

      // 獎金統計
      if($pv_sum !=0){ // 有獎金要發
        // 使用 pv值乘後比對區間 或 使用完成總金額比對區間
        $compare_amount = ($kpi_modle[$pv_bonus] == 1) ? (float)$pv_sum : (float)$cal_array['complete'];
        // dump($pv_sum);
        // dump($cal_array['complete']);
        foreach ($kpi_modle[$bonus] as $k_bonus => $v_bonus) {
          $v_bonus = (array)$v_bonus;
          
          // 處理下界
          if( is_null($v_bonus['from']) ){
            $bigger_than_from = true;
            $bonus_lower_bound = 0.00;
          }else{
            $bonus_lower_bound = (float)$v_bonus['from']*$month_use_ratio;
            $bigger_than_from = $bonus_lower_bound <= $compare_amount;
          }

          // 處理上界
          if( is_null($v_bonus['to']) ){
            $smaller_than_to = true;
            $bonus_upper_bound = 10000000000.00;
          }else{
            $bonus_upper_bound = (float)$v_bonus['to']*$month_use_ratio;
            $smaller_than_to = $compare_amount < $bonus_upper_bound;
          }

          $bonus_amount = $v_bonus['amount'] ? (float)$v_bonus['amount']*$month_use_ratio : 0; // 獎金去間定額獎金(按模組套用占整月天數之比計算)
          $bonus_percent = $v_bonus['percent'] ? (float)$v_bonus['percent']/100 : 0; // 獎金區間抽成比例

          // 計算獎金
          if($kpi_modle[$accum_bonus]==0){ // 不累進計算獎金
            if($bigger_than_from && $smaller_than_to){ // 落在區間內
              $cal_array['bonus'] += $bonus_amount; // 給指定金額
              $cal_array['bonus'] += $pv_sum * $bonus_percent; // pv_sum * 抽成比例
              break;
            }

          }else{ // 累進計算獎金
            if( $bigger_than_from ){ // 比區間下界大
              if($smaller_than_to){ // 比區間上界小
                $area_amount = $compare_amount - $bonus_lower_bound; // 以超出下界的量計算本層將金
              }else{  // 比區間上界大
                $area_amount = $bonus_upper_bound - $bonus_lower_bound; // 以本層區間的量計算本層將金
              }

              $cal_array['bonus'] += $bonus_amount; // 給指定金額
              $cal_array['bonus'] += $pv_sum * ($area_amount/$compare_amount) * $bonus_percent; // pv_sum * 此區間量占全額的比率 * 抽成比例
            }
          }
        }
      }
      $cal_array['bonus'] = round($cal_array['bonus']);

      return $cal_array;
    }
  }
?>