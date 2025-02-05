<?php
  namespace Customer\Controller;
  use Customer\Controller\GlobalController;

  use Photonic\Common;
  use Photonic\Invoice;
  use Photonic\MoneyHelper;
  use Photonic\MensHelper;

  class AjaxController extends GlobalController 
  {
    static public $money_tables = ['crm_othermoney', 'crm_seomoney'];

    function _initialize(){
      parent::_initialize();
    }

    /*分期自動出帳*/
    public function auto_contract_sale(){
      dump('開始');
      session('eid', self::$top_adminid);

      $auto_sale_date = D('system_parameter')->where('id=3')->find()['data'];
      if(date('d')!=$auto_sale_date || $auto_sale_date==0){
        $this->error('今天無需出帳');
      }

      /*找出 有設定分期 且 為已簽約 且 在執行區 且不是seo合約 且 開始日期小於等於當前日期*/
      $contracts = D('crm_contract')->field('*, SUBSTRING(`starttime`,1,4) as year, SUBSTRING(`starttime`,6,2) as month')
                                    ->where('
                                        contracttime!=0 AND 
                                        flag=1 AND 
                                        flag2=1 AND 
                                        cate!=1 AND 
                                        starttime <= "'.date('Y-m-d').'"
                                    ')->select();
      // dump($contracts);exit;
      foreach ($contracts as $key => $contract) {
        if($contract['cate']=='1'){ /*seo合約*/
          $money_table = "crm_seomoney";
        }else{ /*非seo合約*/
          $money_table = "crm_othermoney";

          /*判斷 已請款次數是否小於 設定分期*/
          $money_times = D($money_table)->where('caseid='.$contract['id'])->count();
          if($money_times<$contract['contracttime']){ /*需要請款*/
            /*新增請款*/
              if($money_times+1 == $contract['contracttime']){ /*本次請款為最後一期*/
                $money_has_saled = D($money_table)->where('caseid='.$contract['id'])->sum('dqmoney'); /*找出過去續累積出貨金額*/
                $money = $contract['allmoney'] - $money_has_saled; /*本次出貨 為 總金額 減去 過去續累積出貨(避免除不盡的問題)*/
              }else{
                $money = round($contract['allmoney'] / $contract['contracttime']);
              }

              $prepaid = '0';
              $ships = [
                [
                  'name' => $contract['sale_items'],
                  'money' => $money,
                  'content' => "第".($money_times+1)."期",
                  'num' => 1,
                ],
              ];
              $moneyid_new = MoneyHelper::create_money($contract['id'], $prepaid, $ships);
              if(!$moneyid_new){ continue; }

            /*設定 應收款金額*/
              $xqj = $money; /*預設為全部請款金額*/
              $count_data = MoneyHelper::count_contract_money($contract['id']);
              $allmoney_prepaid = $count_data['allmoney_prepaid']; /*找出合約剩餘預收款*/
              if($allmoney_prepaid >= $xqj){
                $xqj = 0;
              }else{
                $xqj -= $allmoney_prepaid;
              }
              $error_msg = MoneyHelper::set_xqj($contract['id'], $moneyid_new, $xqj);
              if($error_msg){ continue; }

            /*確認出貨金額*/
              $confirm_result = MoneyHelper::confirm_sale($contract['id'], $moneyid_new, $contract['invoice']);
              if($confirm_result['status']==0){ continue; }

            if(self::$control_ecpay_invoice=='1'){ /*如果有串電子發票*/
              /*自動核可請款*/
                D($money_table)->where('id='.$moneyid_new)->data([
                  'queryflag' => 1,
                  'queryflag_time' => time(),
                ])->save();

              /*自動寄送請款提醒*/
                MoneyHelper::send_pay_remaind($contract['id'], $moneyid_new);
            }
          }
        }
      }
      dump('結束');
    }
    
    /*未付款提醒*/
    public function auto_not_pay_remind(){
      dump('開始');
      foreach (self::$money_tables as $money_table) {
        $this->do_not_pay_remind($money_table);
      }
      dump('結束');
    }
    private function do_not_pay_remind($money_table){
      $data = json_decode(D('system_parameter')->where('id=4')->find()['data']); /*取得 未付款提醒設定*/
      $pay_limit_day = $data[0]; /*核可後後幾日提*/
      if($pay_limit_day<0){
        dump('不使用未付款提醒功能'); return;
      }
      $re_remind_day = $data[1]; /*提醒後幾日再提醒*/
      $day_change = $pay_limit_day-1 > 0 ? '-'.($pay_limit_day-1) : '+0';
      $limit_date = strtotime(date('Y-m-d').' '.$day_change.'Days');

      $payments = D($money_table.' cm')->field('cm.*,
                                                 crm.name as crm_name, crm.nick as crm_nick, crm.commail')
                                      ->join('LEFT JOIN crm_contract c ON c.id=cm.caseid')
                                      ->join('LEFT JOIN crm_crm crm ON crm.id=c.cid')
                                      ->where('cm.queryflag=1 AND cm.getedflag=0 AND c.flag=1 AND c.flag2=1 AND '.
                                              'queryflag_time_remind!="" AND queryflag_time_remind is not null AND queryflag_time_remind<"'.$limit_date.'"')
                                      ->select();
      foreach ($payments as $key => $payment) {
        $crm = D('crm_crm')->find($contract['cid']);
        if(!$crm){ continue; }

        /*寄送 未付款提醒信*/
          $money_content_text = MoneyHelper::get_money_content_text($payment['caseid'], $payment['id']);
          if(!$money_content_text){ continue; }
          $xqj = $payment['xqj'] ? $payment['xqj'] : 0;
          $xqj = round($xqj + $payment['xqj_tax']);

          $body = '';
          $body .= "<p>".$crm['name']." 您好：</p>";
          $body .= "<p>您有一筆款項超過 ".$pay_limit_day." 日未付清</p>";
          $body .= "<p>期號：".$payment['qh'].'-'.$payment['count']."</p>";
          $body .= "<p>請款項目：".$money_content_text."</p>";
          $body .= "<p>需付款金額：".$xqj."</p>";
          if($payment['ticket']){
            $body .= "<p>發票號碼：".$payment['ticket']."</p>";
            $body .= "<p>發票隨機碼：".$payment['ticket_rand']."</p>";
            $body .= "<p>發票開立日期：".date('Y-m-d', $payment['ticketdate'])."</p>";
            if(self::$control_ecpay_invoice==1){ /*有串電子發票*/
              if($payment['ticket_rand']){
                $print_invoice_url = 'http://'.$_SERVER['HTTP_HOST'].u('Ajax/print_invoice')."?InvoiceNo=".$payment['ticket'].'&RandomNumber='.$payment['ticket_rand'];
                $body .= "<p><a href='".$print_invoice_url."' target='_blank'>點我查看發票</a></p>";
              }
            }
          }
          $body .= "<p></p>";
          $body .= "<p>再請您盡快繳納，謝謝</p>";
          $body .= "<p style='color:red'>===此為系統訊息，請勿回覆===</p>";
          // dump($body);
          send_email($body, $crm['commail'], "未付款提醒");

        /*更新日期*/
          $new_queryflag_time_remind = strtotime( date('Y-m-d H:i:s', $payment['queryflag_time_remind']).' +'.$re_remind_day.'Days' );
          D($money_table)->where('id="'.$payment['id'].'"')->data(['queryflag_time_remind' => $new_queryflag_time_remind])->save();

      }
    }

    /*寄送發票*/
    public function auto_create_invoice(){
      dump('開始');
      foreach (self::$money_tables as $money_table) {
        $this->create_invoice($money_table);
      }
      dump('結束');
    }
    public function create_invoice($money_table){
      if(self::$control_ecpay_invoice!=1){ return; }

      $day_change = '-2'; /*今天開立三天前收到款項的發票*/
      $limit_date = strtotime(date('Y-m-d').' '.$day_change.'Days'); /*此日期前建立的未付款項須提醒*/
      // dump($limit_date); dump(date('Y-m-d', $limit_date));

      $payments = D($money_table.' cm')->field('cm.*, 
                                                crm.name as crm_name, crm.nick as crm_nick, crm.commail, crm.id as crm_id')
                                      ->join('LEFT JOIN crm_contract cc ON cc.id=cm.caseid')
                                      ->join('LEFT JOIN crm_crm crm ON crm.id=cc.cid')
                                      ->where('cm.queryflag=1 AND cm.getedflag=1 AND cc.get_or_pay=0 AND cc.flag=1 AND cc.flag2=1 AND
                                               cm.dateline!="" AND cm.dateline!="0" AND cm.dateline<"'.$limit_date.'"')
                                      ->select();
      // dump($payments);exit;
      foreach ($payments as $key => $payment) {
        dump("寄送發票：".$money_table.":\t".$payment['id']);
        /*取得出貨內容資訊*/
        $money_content_text = MoneyHelper::get_money_content_text($payment['caseid'], $payment['id']);
        if(!$money_content_text){ continue; }
        $xqj = $payment['xqj'] ? $payment['xqj'] : 0;
        $xqj = round($xqj + $payment['xqj_tax']);

        $body = '';
        $body .= "<p>".$payment['crm_name']." 您好：</p>";
        $body .= "<p>您付的款項已確認付款，付款資料如下：</p>";
        $body .= "<p>期號：".$payment['qh'].'-'.$payment['count']."</p>";
        $body .= "<p>請款項目：".$money_content_text."</p>";
        $body .= "<p>請款金額：".$xqj."</p>";
        $body .= "<p></p>";
        $body .= "<p>對應款項之發票內容如下：</p>";
        if($payment['ticket']!='' && $payment['ticket']){ /*已設定過發票*/
          $ticket_no = $payment['ticket'];
          $ticket_rand = $payment['ticket_rand'];
          $ticketdate = date('Y-m-d', $payment['ticketdate']);
        }else{
          /*串電子發票 開立*/
          $Invoice = Invoice::instance();
          $Invoice->setAphabeticLetter();
          $Return_Info = $Invoice->create_invoice($money_table, $payment['id']);
          dump($Return_Info);
          if(!$Return_Info['InvoiceNo']){ continue; }
          $ticket_no = $Return_Info['InvoiceNo'];
          $ticket_rand = $Return_Info['RandomNumber'];
          $ticketdate = mb_substr($Return_Info['InvoiceDate'], 0, 10);
        }
        $body .= "<p>發票號碼：".$ticket_no."</p>";
        $body .= "<p>發票隨機碼：".$ticket_rand."</p>";
        $body .= "<p>發票開立日期：".$ticketdate."</p>";
        if(self::$control_ecpay_invoice==1){ /*有串電子發票*/
          if($payment['ticket_rand']){
            $print_invoice_url = 'http://'.$_SERVER['HTTP_HOST'].u('Ajax/print_invoice')."?InvoiceNo=".$ticket_no.'&RandomNumber='.$ticket_rand;
            $body .= "<p><a href='".$print_invoice_url."' target='_blank'>點我查看發票</a></p>";
          }
        }
        $body .= "<p></p>";
        $body .= "<p>請查收</p>";
        $body .= "<p style='color:red'>===此為系統訊息，請勿回覆===</p>";

        /*寄送 開立發票通知信*/
        if($ticketdate==date('Y-m-d')){ /*當日開出的發票才寄送通知*/
          // dump($body);
          send_email($body, $payment['commail'], "開立發票通知");

          $crm_crm = D('crm_crm')->where('id="'.$payment['crm_id'].'"')->find();
          // dump($output);
        }
      }
    }
    public function print_invoice(){
      $InvoiceNo = $_GET['InvoiceNo'] ?? '';
      $RandomNumber = $_GET['RandomNumber'] ?? '';

      foreach (self::$money_tables as $money_table) {
        $payment = D($money_table.' cm')->field('cm.*, 
                                                crm.name as crm_name, crm.nick as crm_nick, crm.commail, crm.id as crm_id')
                                        ->join('LEFT JOIN crm_contract cc ON cc.id=cm.caseid')
                                        ->join('LEFT JOIN crm_crm crm ON crm.id=cc.cid')
                                        ->where('cc.get_or_pay=0 AND cm.ticket="'.$InvoiceNo.'" AND cm.ticket_rand="'.$RandomNumber.'"')
                                        ->find();
        // dump($payment);
        if($payment){
          $print_invoice = Invoice::instance()->print_invoice($money_table, $payment['id']); /*列印發票(B2B)*/
          // dump($print_invoice);
          if($print_invoice['InvoiceHtml']!='' && $print_invoice['InvoiceHtml']){
            header("location: ".$print_invoice['InvoiceHtml']);
            return;
          }
        }
      }
      $this->error('無效連結，或為手開發票，請再來電確認');
    }

    /*派發特休*/
    public function auto_set_special_rest(){
      $eip_user = MensHelper::get_mens_working();
      foreach ($eip_user as $key => $value) {
        $dut_years = MensHelper::count_seniority($value['dutday'])['dut_years'];
        $layer_macth = D('special_rest')->where('seniority<='.$dut_years)->order('seniority desc')->find();
        if(!$layer_macth){ continue; }
        $had_changed = D('special_rest_accumulation')->where('user_id='.$value['id'].' AND seniority='.$layer_macth['seniority'])->find();
        if(!$had_changed){ /*還未套用過此年資*/
          $add_data = [
            'user_id' => $value['id'],
            'seniority' => $layer_macth['seniority'],
            'rest_day' => $layer_macth['rest_day'],
            'datetime' => date('Y-m-d'),
          ];
        }
        else{ /*已套用過此年資*/
          $layer_higher = D('special_rest')->where('seniority>'.$layer_macth['seniority'])->order('seniority desc')->find();
          if(!$layer_higher){ /*不存在更高年資的設定 (如果存在，就等以後檢查是否套用過年資後再處理)*/
            /*檢查目前年資 是否等於 已套用最大年資+1年*/
            $last_accumulation = D('special_rest_accumulation')->where('user_id='.$value['id'])->order('seniority desc')->find();
            if($last_accumulation){
              if($dut_years==$last_accumulation['seniority']+1){
                $special_rest_last = D('special_rest')->order('seniority desc')->find();
                $add_data = [
                  'user_id' => $value['id'],
                  'seniority' => $last_accumulation['seniority']+1,	/*條件年自為已取得的最大年資+1*/
                  'rest_day' => $special_rest_last['rest_day'],		/*依最大年資設定值給假*/
                  'datetime' => date('Y-m-d'),
                ];
              }
            }
          }
        }

        if(isset($add_data)){
          dump('添加特休:員工'.$value['id'].', 資料:'.json_encode($add_data, JSON_UNESCAPED_UNICODE));
          D('special_rest_accumulation')->data($add_data)->add();
          unset($add_data);
        }
        
      }
    }

    /*排班自動生成請款*/
    public function auto_schedules_money_create(){
      /*撈取今日需要請款的客戶*/
      $day = (int)date('d');
      $crm_crms = D('crm_crm')->field('id, ask_money_date')->where('ask_money_date ="'.$day.'"')->select();
      // dump($crm_crms);exit;
      foreach ($crm_crms as $crm_crm) {
        $date_s = '';
        $date_e = date('Y-m-d', strtotime(date('Y-m-d').' -1Days'));
        try {
          $params = [
            'crm_id' => $crm_crm['id'],
            'date_s' => "",						/*不限開始時間*/
            'date_e' => $date_e, 			/*結束日期為觸發請款日的1日*/
            'turn_salary_time' => 1, 	/*限已拋轉薪資的日程*/
          ];
          // dump($params);
          $result = MoneyHelper::create_money_schedule($params);
          $ids = array_map(function($item){
            return $item['id'];
          }, $result);
          dump('處理客戶：'.$crm_crm['id'].' ,生成請款:'.implode(',', $ids));
        } catch (\Exception $e) {
          dump('處理客戶：'.$crm_crm['id'].' ,錯誤:'.$e->getMessage());
        }
      }
    }
  }
?>