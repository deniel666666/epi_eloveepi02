<?php
namespace Photonic;
use Think\Controller;

use Photonic\Common;
use Photonic\CustoHelper;
use Photonic\Invoice;
use Photonic\ContractHelper;
use Photonic\ScheduleHelper;

class MoneyHelper extends Controller
{
    static public $money_tables = ['crm_seomoney', 'crm_othermoney'];

    public static function instance(){
        return new MoneyHelper();
    }

    /*依合約類別回傳money請款表*/
	public static function money_table($cate_id){
        /*找出請款的表格名稱*/
		if($cate_id=='1'){ /*seo合約*/
			$money_table = "crm_seomoney";
		}else{ /*非seo合約*/
			$money_table = "crm_othermoney";
		}
        return $money_table;
    }
    /*依合約id回傳money請款表*/
    public static function money_table_by_contract($id){
        /*找出請款的表格名稱*/
        $contract = D("crm_contract")->field("cate")->where('id="'.$id.'"')->limit(1)->find();
        if($contract){
            return self::money_table($contract['cate']);
        }else{
            return self::money_table(99);
        }
    }
    public static function get_one_money($caseid, $moneyid){
        $money_table = self::money_table_by_contract($caseid);
        return D($money_table)->where("id='".$moneyid."'")->find();
    }
    public static function get_money($params=[], $get_or_pay=0, $page_count=25){
        $money_data = [];
        $result = ContractHelper::get_contract_where_sql($params, $get_or_pay);
        $where_query = $result['where_query'];

        $queryflag = $params['queryflag'] ?? -1; 
        if($params['queryflag']!="" && $params['queryflag']!="-1"){
            $where_query .= " AND queryflag = '".$params['queryflag']."'";
        }
        $money_data['queryflag'] = $queryflag;
        
        $getedflag = $params['getedflag'] ?? -1;
        if($getedflag!="" && $getedflag!="-1"){
            $where_query .= " AND getedflag = '".$getedflag."'";
        }
        $money_data['getedflag'] = $getedflag;

        if($params['prepaid']!=""){
            $where_query .= " AND prepaid = '".$params['prepaid']."'";
        }
        
        if($params['ship_status']!=""){
            $where_query .= " AND ship_status = '".$params['ship_status']."'";
        }

        if($params['m_cdate']!=""){
            $print_r_qh=substr($params['m_cdate'],0,4)."/".substr($params['m_cdate'],4,2);
            //dump($print_r_qh);
            $startmktime=strtotime($print_r_qh."/01");
        }
        $params['m_cdate'] = str_replace("/","", $print_r_qh);
        $params['qh'] = $params['m_cdate'];
        if($params['qh']!=""){
            $where_query .= " AND qh like '".substr($params['qh'],0,4)."/".substr($params['qh'],4,2)."'";
        }

        if($params['ticketdate_start']!=""){
            $time = strtotime($params['ticketdate_start']);
            $where_query .= " AND ticketdate >= ".$time." AND ticketdate !=''";
        }
        if($params['ticketdate_end']!=""){
            $time = strtotime($params['ticketdate_end'].' +1Day');
            $where_query .= " AND ticketdate < ".$time." AND ticketdate !=''";
        }

        if($params['m_cdate_code']!=""){
            $where_query .= " AND CONCAT(c.sn,'-',m.qh,'-',count) LIKE '%".$params['m_cdate_code']."%'";
        }


        if($params['startdate'] != ""){
            $start = strtotime($params['startdate']);
            $where_query .= " AND m.exptime >= '".$start."'";
        }
        if($params['enddate'] != ""){
            $end = strtotime($params['enddate'].' +1Day');
            $where_query .= " AND m.exptime < '".$end."'";
        }

        if(isset($params['money_user_name'])){
            if($params['money_user_name'] != ""){
                $money_user_name = filter_var($params['money_user_name'], FILTER_SANITIZE_STRING);
                $where_query .= " AND ( 
                    m.create_user_name LIKE '%".$money_user_name."%' OR
                    m.audit_user_name LIKE '%".$money_user_name."%'
                )";
            }
        }
        

        /*排序*/
        $order="m.id desc, caseid desc";
        foreach($params as $key =>$vo){
            if($key=="order"){//排序
                $order = $vo." desc, ". $order;
            }
        }

        $cate = $params['cate'] ?? '';
        if($params['caseid']!=""){
            $where_query .= " AND caseid = '".$params['caseid']."'";
            $target_contract = D('crm_contract')->where("id='".$params['caseid']."'")->find();
            if($target_contract){ $cate = $target_contract['cate']; }
        }

        $money_table = self::money_table($cate);
        $crm_contract = D($money_table." m");
        $money_data['money_table'] = $money_table;
        $money_data['where_query'] = $where_query;
        // dump($where_query);exit;
        $count = clone $crm_contract;
        $count = $count->field("crm_crm.*,c.*,m.*,m.id as mid,crm_crm.id as crm_id,c.id as id,crm_crm.id as cusid ")
                        ->join("LEFT JOIN crm_contract c ON m.caseid=c.id ")
                        ->join("LEFT JOIN crm_crm ON c.cid=crm_crm.id ")
                        ->where($where_query)
                        ->count();
        if($page_count<=0){ /*不使用分頁*/
            $page_count = (int)$count; /*讓一頁的量等於全部的量*/
        }
        $Page = new \Think\Page($count, $page_count);// 实例化分页类 传入总记录数和每页显示的记录数
        $Page->setConfig('header',"%TOTAL_ROW% 個客戶");
        $Page->setConfig('prev',"上一頁");
        $Page->setConfig('next',"下一頁");
        $Page->setConfig('first',"第一頁");
        $Page->setConfig('last',"最後一頁 %END% ");
        $show = $Page->show();// 分页显示输出
        $money_data['show'] = $show;

        $crm_contract = $crm_contract->field("crm_crm.*, crm_crm.id as crm_id, crm_crm.id as cusid,
                                              c.*,
                                              m.*, m.id as mid,
                                              c.id as id")
                                    ->join("LEFT JOIN crm_contract c ON m.caseid=c.id ")
                                    ->join("LEFT JOIN crm_crm ON c.cid=crm_crm.id ")
                                    ->where($where_query)
                                    ->limit($Page->firstRow.','.$Page->listRows)->order($order)->select();
        // dump($crm_contract);
        foreach($crm_contract as $k=>$v){
            $crm_contract[$k]['qh_count'] = $v['qh']."-".$v['count'];
            $crm_contract[$k]['show_name'] = CustoHelper::get_crm_show_name($v['crm_id']);
            if(self::$control_money_input==0){ /*輸入方式為未稅*/
                $crm_contract[$k]['earn'] = $v['xqj'] + $v['xqj_tax'];          /*本期消費者應付款金額(總額)*/
                $crm_contract[$k]['earn_pretax'] = $v['xqj'];                   /*本期消費者應付款金額(稅前)*/
            }else{ /*輸入方式為實收*/
                $crm_contract[$k]['earn'] = $v['xqj'];                          /*本期消費者應付款金額(總額)*/
                $crm_contract[$k]['earn_pretax'] = $v['xqj'] - $v['xqj_tax'];   /*本期消費者應付款金額(稅前)*/
            }
            $ship_countent = [];
            $crm_shipment = D('crm_shipment')->where('caseid="'.$v['id'].'" AND moneyid="'.$v['mid'].'"')->order('id asc')->select();
            foreach ($crm_shipment as $key => $value) {
                $ship_name = $value['content'] ? $value['name'].'-'.$value['content'] : $value['name'];
                array_push($ship_countent, $ship_name.':'.$value['num']);
            }
            $crm_contract[$k]['ship_countent'] = implode('、 ', $ship_countent);
            switch($v['paymenttype']){
                case 1:
                    $crm_contract[$k]['payment']="現金";
                    break;
                case 2:
                    $crm_contract[$k]['payment']="匯款";
                    break;
                case 3:
                    $crm_contract[$k]['payment']="支票";
                    break;
            }
            
            //計算分頁統計    
            $all['allmoney'] += $v['allmoney'];
            $all['earn'] += $crm_contract[$k]['earn'];                  //本期消費者應付款金額(總額)
            $all['earn_pretax'] += $crm_contract[$k]['earn_pretax'];    //本期消費者應付款金額(稅前)
            $all['xqj'] += $v['xqj'];                                   //本期應收款
            $all['xdj'] += $v['xdj'];                                   //本期銷預收款
            $all['xqj_tax'] += $v['xqj_tax'];                           //本期應收款稅金
            $all['fax'] += $v['fax'];                                   //總稅金
            $all['tips'] += $v['tips'];                                 //請款損益
            $all['tips1'] += $v['tips1'];                               //收款損益
            $all['dqmoney'] += $v['dqmoney'];                           //出貨總金額
        }
        // dump($crm_contract);

        $money_data['crm_contract'] = $crm_contract;    // 搜尋結果
        $money_data['all'] = $all;                      // 搜尋結果統計
        return $money_data;
    }
    public static function get_ships($caseid, $moneyid){
        $shipments = D("crm_shipment")->where('caseid='.$caseid.' AND moneyid='.$moneyid)->order('id asc')->select();
        foreach ($shipments as $key => $value) {
            $shipments[$key]['time_format'] = date('Y-m-d', $value['time']);
        }
        return $shipments;
    }
    public static function get_ships_uesed_num($caseid, $contract_unit_id){
        /*crm_seomoney紀錄的crm_shipment一定是對應到預收款，因此已用數量一定為0*/
        $num = D("crm_shipment ship")->join("LEFT JOIN crm_othermoney m ON m.id=ship.moneyid")
                                ->where('m.prepaid=0 AND ship.caseid='.$caseid.' AND ship.contract_unit_id='.$contract_unit_id)
                                ->sum('ship.num');
        return $num;
    }
    // 計算請款總金額
    public function count_money_total($caseid){
        $money_table = self::money_table_by_contract($caseid);
        $total= D($money_table)->where("caseid=".$caseid.' AND prepaid=0')->sum('dqmoney'); /*一般出貨*/
        $total2= D($money_table)->where("caseid=".$caseid.' AND prepaid=1')->sum('dqmoney');/*預收款出貨*/
        return [
            'total'=> number_format($total),
            'total2'=> number_format($total2),
        ];
    }
    public static function count_contract_money($caseid){
        $crm_contract = D("crm_contract c")->find($caseid);
        $all['contract_all'] = $crm_contract['allmoney'];

        $money_table = self::money_table_by_contract($caseid);
        $money = D($money_table)->where("caseid='".$caseid."'")->order('id asc')->select();
        $count_data["money"] = $money;

        $all['real_get'] = 0;           /*實際需付款額*/
        $all['real_get_paid'] = 0;      /*實際需付款額(已付部分)*/
        $all['real_get_unpaid'] = 0;    /*實際需付款額(未付部分)*/
        $all['shipments'] = 0;          /*總出貨金額(不論是否已收款)*/
        $all['shipments_un'] = 0;       /*總未貨金額(不論是否已收款)*/
        $all['sale_completed'] = 0;     /*總銷貨金額*/
        $all['sale_uncompleted'] = 0;   /*總未銷貨金額*/
        $all['allmoney_prepaid'] = 0;   /*剩餘預付款*/
        $all['allmoney_xdj'] = 0;       /*總銷預收款*/
        $all['allmoney_xqj'] = 0;       /*總銷期金*/
        $all['money_uncomplete'] = 0;   /*剩餘未收款金額*/
        $all['tips'] = 0;               /*總損益*/
        foreach( $money as $k => $v ) {
            $all['real_get'] += $v['xqj'];
            if($v['prepaid']=='0'){ /*貨款*/
                $all['shipments'] += $v['dqmoney'];
            }           
            if($v['getedflag']=='1'){ //請款單要已付才會進去統計
                $all['real_get_paid'] += $v['xqj'];             
                if($v['prepaid']=='0'){ /*貨款*/
                    $all['sale_completed'] += $v['dqmoney'];
                    $all['allmoney_prepaid'] -= $v['xdj'];  /*扣預付款*/
                    $all['allmoney_xdj'] += $v['xdj'];
                    $all['allmoney_xqj'] += $v['xqj'];
                }else{ /*預付款*/
                    $all['allmoney_prepaid'] += $v['xqj'];  /*加預付款*/
                }
            }else{
                $all['real_get_unpaid'] += $v['xqj'];
            }
            $all['tips'] += $v['tips']+$v['tips1'];
        }
        /*總未出貨金額*/
        $all['shipments_un'] = $all['contract_all'] - $all['shipments'];
        /*總未銷貨金額*/
        $all['sale_uncompleted'] = $all['contract_all'] - $all['sale_completed'];
        /*剩餘未收款金額*/
        $all['money_uncomplete'] = $all['contract_all'] - $all['allmoney_xdj'] - $all['allmoney_prepaid'] - $all['allmoney_xqj'];
        $count_data["all"] = $all;

        return $count_data;
    }

    /**
     * 生成請款單(排班)(依日程篩選的參數)
     * @param array $params 篩選參數
     * @return array 生成的請款資料
     */
    public static function create_money_schedule($params){
        /*依條件撈取所有需請款的日程人員*/
        $params['auto_money'] = '1';    /*需依人力請款*/
        $params['moneyid'] = '0';       /*未請過款*/
        $schedules = ScheduleHelper::get_schedules($params, true, true);
        if(count($schedules)==0){ throw new \Exception('無符合條件的請款項目，無法申請付款'); }
        foreach ($schedules as $value) {
            if(!$value['turn_salary_time']){
                throw new \Exception('包含未拋新的日程，無法申請付款');
            }
        }
        // dump($schedules);exit;
        
        $contract_user_skills = []; /*合約簽訂的人力請款金額的資料*/
        /*依日程組統計請款金額*/
        $money_data = [];
        foreach ($schedules as $schedule) {
            if(!$schedule['caseid']){ continue; } /*無對應合約*/

            if(!isset($money_data[$schedule['id']])){
                $crm_contract = D('crm_contract')->where('id="'.$schedule['caseid'].'"')->find();
                $money_data[$schedule['id']] = [
                    'schedule_date_primary'=>[], 
                    'caseid'=>$schedule['caseid'],
                    'invoice'=>$crm_contract['invoice'] ?? '',
                    'ships'=>[],
                ];
            }
            $money_data[$schedule['id']]['schedule_date_primary'][] = $schedule['schedule_date_primary'];

            if(!isset($contract_user_skills[$schedule['caseid']])){ /*尚未有人力請款的資料*/
                /*取得資料*/
                $contract_user_skills[$schedule['caseid']] = ContractHelper::get_user_skills($schedule['caseid']);
            }
            $skills = $contract_user_skills[$schedule['caseid']];
            /*計算金額*/
            if( isset($skills[$schedule['user_skill']]) ){/*存在此工種*/
                $money_data = self::set_hour_data($money_data, $skills, $schedule, false);  /*正規工時*/
                $money_data = self::set_hour_data($money_data, $skills, $schedule, true);   /*加班工時*/
            }
        }
        // dump($money_data);exit;

        /*逐個日程組生成請款單*/
        if(count($money_data)==0){ throw new \Exception('無需請款項目'); }
        $return_data = [];
        foreach ($money_data as $schedule_id => $value) {
            $moneyid = self::create_money($value['caseid'], 0, $value['ships']);
            D('schedule_date')->where('id in ('.implode(',', $value['schedule_date_primary']).')')->data([
                'moneyid' => $moneyid,
                'create_money_name' => session('userName'),
            ])->save();
            $money_table = self::money_table_by_contract($value['caseid']);
            $money = D($money_table)->where('id='.$moneyid)->find();
            self::set_xqj($value['caseid'], $moneyid, $money['dqmoney']);
            $value['invoice'] = $value['invoice']=='免付' ? '無' : $value['invoice'];
            self::confirm_sale($value['caseid'], $moneyid,  $value['invoice']);
            $return_data[] = $money;
        }
        return $return_data;
    }
    public static function set_hour_data($money_data, $skills, $schedule, $overtime=false){
        $ship_key = $schedule['date'].'_'.$schedule['user_skill'];
        $hisp_name = $schedule['date'].$skills[$schedule['user_skill']]['name'];
        if($overtime){ /*處理加班工時*/
            $ship_key = $ship_key.'_加班';
            $hisp_name = $hisp_name.'_加班';
            $hour_price = $skills[$schedule['user_skill']]['hour_price_over'] ?? 0;
            $do_hour = (float)$schedule['do_hour_overtime'];
        }else{ /*處理正規工時*/
            $hour_price = $skills[$schedule['user_skill']]['hour_price'] ?? 0;
            $do_hour = (float)$schedule['do_hour'];
        }
        if($do_hour<=0){ return $money_data; } /*沒有時數則不處理*/

        if(!isset($money_data[$schedule['id']]['ships'][$ship_key])){
            $unit_name = $skills[$schedule['user_skill']]['unit_name'] ?? '';
            $per_unit_name = $unit_name ? '/'.$unit_name : '';
            $money_data[$schedule['id']]['ships'][$ship_key] = [
                'name' => $hisp_name,
                'content' => $hour_price.$per_unit_name,
                'unit' => $unit_name,
                'num' => 0,
                'money' => 0,
                'contract_unit_id' => $schedule['user_skill'],
                'content_table' => 'user_skill',
            ];
        }
        $money_data[$schedule['id']]['ships'][$ship_key]['num'] += $do_hour;
        $money_data[$schedule['id']]['ships'][$ship_key]['money'] += $hour_price * $do_hour;
        return $money_data;
    }

    /*生成請款單(一般請款、SEO預收款)*/
    public static function create_money($caseid, $prepaid, $ships){
        $money_table = self::money_table_by_contract($caseid);

        $money['caseid'] = $caseid;
        $money['prepaid'] = $prepaid;
        $new_patch = MoneyHelper::get_patch_num($caseid, $prepaid);
        $money['qh'] = $new_patch['qh'];
        $money['count'] = $new_patch['count'];
        $money['dqmoney'] = 0;
        $money['upmoney'] = 0;
        $money['ship_status'] = 0;
        $money['create_user_name'] = session('userName');
        $moneyid_new = D($money_table)->data($money)->add();

        self::add_sales($caseid, $moneyid_new, $ships);
        return $moneyid_new;
    }
    /*依合約及出貨類型取得本次批號*/
    public static function get_patch_num($caseid, $prepaid){
        $money_table = self::money_table_by_contract($caseid);

        /*預設期數值*/
        $y = date("Y", time());
        $m = date("m", time());
        $count = 1;

        $last_money = M($money_table)->where("caseid=".$caseid)->order("id desc")->find();
        if($last_money){ /*有最後一筆相同類型 或 核可的*/
            if($y.'/'.$m==$last_money['qh']){
                $qh = explode('/', $last_money['qh']);
                $y = $qh[0];
                $m = $qh[1];
                $count = $last_money['count']+1;
            }
        }
        return [
            'qh' => $y."/".$m,
            'count' => $count,
        ];
    }
    /*請款單添加出貨*/
    public static function add_sales($caseid, $moneyid, $ships){
        // dump($ships);exit;
        $money_table = self::money_table_by_contract($caseid);
        $money = D($money_table)->where('id='.$moneyid)->find();
        if(!$money){ return; }

        $time = time();
        foreach ($ships as $key => $ship) {
            if(!isset($ship['num'])){ $ship["num"] = ''; }
            if($ship["num"]!==''){
                $ship['caseid'] = $money['caseid'];
                $ship['contract_unit_id'] = $ship['contract_unit_id'] ?? 0;
                $ship['content_table'] = $ship['content_table'] ?? 'crm_contract_unit';
                $ship['moneyid'] = $money['id'];
                $ship['name'] = $ship['name'] ?? '';
                $ship['money'] = $ship['money'] ?? 0;
                $ship['content'] = $ship['content'] ?? '';
                $ship['time'] = $time;
                D("crm_shipment")->data($ship)->add();
            }
        }
        $dqmoney = D("crm_shipment")->where('moneyid='.$moneyid)->sum('money');

        $data = ['dqmoney'=>$dqmoney, 'xdj'=>0];
        if($money['prepaid']==1){
            $data['xqj'] = $dqmoney;
        }else{
            $data['xqj'] = null;
        }
        D($money_table)->where('id='.$moneyid)->data($data)->save();
    }

    public static function get_outer_money_seo($caseid, $moneyid, $qh=''){ /*取得出貨單資料(SEO貨款)*/
        $money_table = MoneyHelper::money_table_by_contract($caseid);
        $money=D($money_table)->where("id='".$moneyid."'")->find();

        if($money){
            $qh = $money['qh_seo'];
        }else{
            $qh = $qh ? $qh : date('Y/m');
        }
        $timesplit = explode("/", $qh);
		$y = $timesplit[0];
		$m = $timesplit[1];
		$prestarttime = date("Y/m/d",strtotime($y."-".$m."-01"));	//本月第一日
		$ndate1 = strtotime(Common::getCurMonthLastDay($qh.'/01'));	//本月最后一天
		$preendtime = date("Y/m/d", $ndate1);
		$outer_data['datespan'] = $prestarttime."~".$preendtime;

		$endday = date("d", $ndate1);
		/*選擇seo紀錄表*/
		if($y==date("Y") && $m==date("m")){
			$dbname="crm_key_rank";
			$mydb=D();
		}else{
			$dbname="seo_rank_".$y.$m;
			$mydb=M()->db(1,"DB_SEO_RANK")->table("seo_rank_".$y.$m);
		}

		$seolist=D("crm_seo_key")->where("contract_id='{$caseid}'")->select();
		//dump($seolist);
		foreach($seolist as $key => $val) {
			$range=D('crm_seo_range')->where("id=".$val['starts'])->find()['rang'];

			//計算計費天數
			$sql="key_Id='" . $val['id'] . "' and key_ranking in(" . $range . ") and `update` >='".$prestarttime."' and `update`<='".$preendtime."'";
			//取得該月日數
			$total_date = $mydb->table($dbname)->where($sql)->order("`update`")->group("`update`")->select();
			// dump($total_date);
			$total_date = $total_date ? $total_date : [];
			$seolist[$key]['total'] = count($total_date);

			$seo_key = $val['key_name'].'_'.$val['someno'].'_'.$val['starts'];
			if( $f_var[$seo_key]['day'] < $seolist[$key]['total'] || $f_var[$seo_key]['day']==0 ){
				$f_var[$seo_key]['key_name'] = $val['key_name'] ;                                       /*關鍵字*/
				$f_var[$seo_key]['url1']     = $val['url1'] ;                                           /*比對網址*/
				$f_var[$seo_key]['price']    = $val['price'] ;                                          /*每月單價*/
				$f_var[$seo_key]['oneprice'] = round($val['price']/$endday ,3);                         /*每天單價*/
				$f_var[$seo_key]['day']    =$seolist[$key]['total'];                                    /*當月有排名天數*/
				$f_var[$seo_key]['tprice'] = round($val['price']/$endday * $seolist[$key]['total']);    /*當月有排名金額*/
			}
		}
		$salelist = [];
        $all = [ 'money'=>0, 'money_real'=>0, ];
		foreach($f_var as $key => $val){
			array_push($salelist, $val);
            $all['money'] += $val['tprice'];        /*用於紀錄dqmoney*/
			$all['money_real'] += $val['tprice'];   /*用於出貨單展示合計*/ 
		}
		$outer_data['salelist'] = $salelist;

        /*處理收費上限*/
    		$crm_contract_seo_upmoney = D("crm_contract_seo")->field("upmoney")->where("pid='{$caseid}'")->find();
            if($crm_contract_seo_upmoney){
                $crm_contract_seo_upmoney = $crm_contract_seo_upmoney['upmoney']===null ? $all['money'] : $crm_contract_seo_upmoney['upmoney'];
            }else{
    		  $crm_contract_seo_upmoney =  $all['money'];
            }
            $outer_data['crm_contract_seo_upmoney'] = $crm_contract_seo_upmoney;

            if($all['money']>$crm_contract_seo_upmoney){
                $all['money'] = $crm_contract_seo_upmoney;
            }
        $outer_data['all'] = $all;

        // dump($outer_data);exit;
		return $outer_data;
    }
    public static function get_outer_money($caseid, $moneyid){ /*取得出貨單資料(SEO預付款, othermoney貨款或預付款)*/
		$salelist = self::get_ships($caseid, $moneyid);
        $outer_data['salelist'] = $salelist;
		//dump($salelist);
        $all = [ 'money'=>0, 'money_real'=>0, ];
		foreach($salelist as $key=>$vo){
			$all['money']+=$vo['money'];            /*用於紀錄dqmoney*/     
            $all['money_real'] += $vo['money'];     /*用於出貨單展示合計*/ 
		}
        $outer_data['all'] = $all;
		
        return $outer_data;
    }

    /*修改請款的應收金額*/
    public static function set_xqj($caseid, $moneyid, $val){
        $data['xqj'] = $val;
        $money_table = self::money_table_by_contract($caseid);
        $money = D($money_table)->where("id='".$moneyid."'")->find();
        if(!$money){ return '項目不存在'; }
        if($money['prepaid']==1){ return '不可修改預收款應收金額'; }

        if($data['xqj']>$money['dqmoney']){ return '請輸入小於等於本期出貨的金額('.$money['dqmoney'].')'; }
        if($data['xqj']<0){ return '請輸入大於0的金額'; }
        $data['xdj'] = $money['dqmoney'] - $data['xqj'];

        $result = D($money_table)->data($data)->where("id='".$moneyid."'")->save();
        if($result){ 
            return '';
        }else{
            return '無需修改項目';
        }
    }

    /*確認出貨，進入請款步驟*/
    public static function confirm_sale($caseid, $moneyid, $invoice){
        // dump($_POST);exit;
    	$crm_contract = D('crm_contract')->where('id="'.$caseid.'"')->find();
    	if(!$crm_contract){ return ['status'=>0, 'info'=>"無此合約"]; }

        $money_table = self::money_table($crm_contract['cate']);
        $money = D($money_table)->where("id='".$moneyid."'")->find();
        if($money['xqj']==null){ return ['status'=>0, 'info'=>"請先設定本期應收金額"]; }

        if($crm_contract['cate']=='1' && $money['prepaid']==0){ // SEO合約 且 非預收款
            $outer_data = self::get_outer_money_seo($caseid, $moneyid);
        }else{
            $outer_data = self::get_outer_money($caseid, $moneyid);
        }
        $salelist = $outer_data['salelist'];
        if(count($salelist)==0){
            return ['status'=>0, 'info'=>"請款單內容為空"];
        }

        if($invoice!='無' && $invoice!='未決定' && $invoice!=''){
            if(self::$control_money_input==0){ /*輸入方式為未稅*/
                $update_data['fax'] = $money['dqmoney'] * TAX_RATE;
                $update_data['xqj_tax'] = $money['xqj'] * TAX_RATE;
            }else{ /*輸入方式為實收*/
                $update_data['fax'] = count_tax($money['dqmoney'], self::$control_money_input);
                $update_data['xqj_tax'] = count_tax($money['xqj'], self::$control_money_input);
            }
        }else{
            $update_data['fax'] = 0;
            $update_data['xqj_tax'] = 0;
        }
        $update_data['invoice'] = $invoice;
        $update_data['ship_status'] = 1;
        // dump($update_data);exit;
        D($money_table)->where("id='".$moneyid."'")->data($update_data)->save();

        $txt_data['caseid']=$caseid;
        $txt_data['moneyid']=$moneyid;
        self::save_print_txt($txt_data); /*紀錄其他$_POST資料*/

        Common::error_log("出貨查核完畢 合約ID:".$caseid.'('.$money['qh'].'-'.$money['count'].')');
        return ['status'=>1, 'info'=>"保存成功", 'moneyid'=>$moneyid];
    }
    /*儲存出貨單內容*/
    public static function save_print_txt($txt_data){
        $txt_data['txt']=$_POST['txt'];
        $txt_data['bz']=$_POST['outnote'];
        $txt_data['sale_code']=$_POST['sale_code'];
        $txt_data['shipping_code']=$_POST['shipping_code'];
        $txt_data['shipping_date']=$_POST['shipping_date'];
        $txt_data['receive_name']=$_POST['receive_name'];
        $txt_data['receive_phone']=$_POST['receive_phone'];
        $txt_data['phone_time']=$_POST['phone_time'];
        $txt_data['bank']=$_POST['bank'];
        $txt_data['card']=$_POST['card'];
        $txt_data['card_end_code']=$_POST['card_end_code'];
        $txt_data['card_name']=$_POST['card_name'];
        $txt_data['card_date']=$_POST['card_date'];
        $txt_data['card_period']=$_POST['card_period'];
        $txt_data['invoice_code']=$_POST['invoice_code'];
        // dump($txt_data);exit;

        $print_txt = self::get_print_txt($txt_data['caseid'], $txt_data['moneyid']);
        // dump($print_txt);exit;
        if($print_txt){ /*編輯*/
            D("print_txt")->where("id='".$print_txt['id']."'")->data($txt_data)->save();
        }else{ /*新增*/
            D("print_txt")->data($txt_data)->add();
        }
    }
    /*依照POST內容取得出貨單*/
    public static function get_print_txt($caseid, $moneyid){
        $print_txt = M("print_txt")->where("caseid='".$caseid."' AND moneyid='".$moneyid."'")->find();
        return $print_txt;
    }

    /*寄送請款信*/
    public static function send_pay_remaind($caseid="", $payment_id=""){
        if(self::$control_send_pay_remaind!='1'){ return; }
        if(!$caseid || !$payment_id){ return; }

        $money_table = MoneyHelper::money_table_by_contract($caseid);
        $payment = D($money_table)->where("id='".$payment_id."'")->find();
        
        $contract = D('crm_contract')->find($caseid);
        if(!$contract){ return; }

        /*客戶資料*/
        $crm = CustoHelper::get_crm_rightdata(['id'=>$contract['cid']])['newbier'];
        if(!$crm){ return; }
        
        $money_content_text = self::get_money_content_text($caseid, $payment_id);
        if(!$money_content_text){ return; }

        $xqj = $payment['xqj'] ? $payment['xqj'] : 0;
        $xqj = round($xqj + $payment['xqj_tax']);

        $body = '';
        $body .= "<p>".$crm['name']." 您好：</p>";
        $body .= "<p>您有一筆款項須支付，付款資料如下：</p>";
        $body .= "<p>期號：".$payment['qh'].'-'.$payment['count']."</p>";
        $body .= "<p>請款項目：".$money_content_text."</p>";
        $body .= "<p>請款金額：".$xqj."</p>";
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
        // dump($crm['commail']);
        send_email($body, $crm['commail'], "付款提醒");
    }

    public static function get_money_content_text($caseid, $payment_id){
        $shipments = self::get_ships($caseid, $payment_id);
        $money_content = [];
        foreach ($shipments as $key => $value) {
            array_push($money_content, $value['name']."-".$value['content'].":".$value['num']);
        }
        return implode("、", $money_content);
    }
}
