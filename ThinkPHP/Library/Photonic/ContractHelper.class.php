<?php
namespace Photonic;
use Think\Controller;

use Photonic\Common;
use Photonic\CustoHelper;
use Photonic\MensHelper;

class ContractHelper extends Controller
{
    public static $types_file = ['file', 'img'];
    public static $types_need_limit = ['text', 'textarea', 'number', 'file', 'img'];
    public static $types_need_option = ['radio', 'radio_box', 'checkbox', 'checkbox_time', 'checkbox_box', 'select'];
    public static $types_need_checked = ['radio', 'radio_box', 'checkbox', 'checkbox_time', 'checkbox_box', 'select'];

    public static function instance(){
        return new ContractHelper();
    }

    /*依給定發票與總金額，計算稅前&稅後金額*/
    public static function get_pre_aftertax_money($invoice, $money){
        if($invoice=='二聯' || $invoice=='三聯'){ /*合約有稅金*/
            if(self::$control_money_input==0){ /*輸入方式為未稅*/
                $money_pretax = $money;
                $money_aftertax = $money * (1+TAX_RATE);
            }
            else{ /*輸入方式為實收*/
                $money_pretax = count_money_pre_tax($money, self::$control_money_input);
                $money_aftertax = $money;
            }
            }else{ /*無稅金*/
            $money_pretax = $money;
            $money_aftertax = $money;
        }
        return [
            'money_pretax' => $money_pretax,
            'money_aftertax' => $money_aftertax,
        ];
    }

    public static function get_crm_cum_cat($get_or_pay=''){
        $where = "status=1";
        if($get_or_pay!==''){
            $where .= " AND get_or_pay=".$get_or_pay;
        }
        $crm_cum_cat = D('crm_cum_cat')->where($where)->order('sort asc, id asc')->select();
        return $crm_cum_cat;
    } 

    /*依欄位類型設定預設答案*/
	public static function set_examinee_default_ans($input_type){
        if( in_array($input_type, self::$types_need_checked) ){ /*選項類型*/
            $ans = [];
        }
        elseif( in_array($input_type, self::$types_file) ){ /*檔案類型*/
            $ans = (object)["file_name"=>"", "data"=>""];
        }
        else{ /*文字類型*/
            $ans = "";
        }

        return $ans;
    }

    /*依合約ID取得下掛商品*/
    static public function get_crm_contract_unit($pid=0, $params=[]){
        if(in_array(121, self::$use_function_top) || in_array(148 , self::$use_function_top)){
            $cat_units = M('crm_contract_unit ccu')->field('ccu.*, cate.name as category_name')
                                              ->join("LEFT JOIN crm_cum_cat_unit AS unit on unit.id=ccu.cat_unit_id")
                                              ->join("LEFT JOIN crm_cum_cat_unit_category AS cate on cate.id=unit.category_id")
                                              ->where('pid="'.$pid.'"')->order("id asc")->select();
            // dump($cat_units);exit;
        }else{
            $cat_units = [];
        }

        $data['cat_units'] = $cat_units;
        // dump($data);exit;
        return $data;
    }
    /*依合約ID取得對應工種*/
    static public function get_user_skills($pid=0){
        $skills = [];
        if(in_array(153, self::$use_function_top)){
            $user_skill = MensHelper::get_user_skills();
            $crm_contract_user_skill = M('crm_contract_user_skill')->where('pid="'.$pid.'"')->order("id asc")->index('user_skill_id')->select();
            // dump($crm_contract_user_skill);exit;
			foreach ($user_skill as $skill) {
                if( isset($crm_contract_user_skill[$skill['id']]) ){
                    $skill['crm_contract_user_skill_id'] = $crm_contract_user_skill[$skill['id']]['id'];
                    $skill['hour_price'] = $crm_contract_user_skill[$skill['id']]['hour_price'];
                    $skill['hour_price_over'] = $crm_contract_user_skill[$skill['id']]['hour_price_over'];
                }else{
                    $skill['crm_contract_user_skill_id'] = 0;
                }
                $skills[$skill['id']] = $skill;
            }
        }
        // dump($skills);exit;
        return $skills;
    }
    /*依合約ID、是否未選擇、篩選參數，取得員工名單*/
    static public function get_crm_contract_user($pid=0, $unselected=false, $params=[]){
        $crm_contract_user = M('crm_contract_user')->where('caseid="'.$pid.'"')->select();
        $user_ids = [0];
        foreach ($crm_contract_user as $value) {
            array_push($user_ids, $value['user_id']);
        }
        $users = MensHelper::get_mens_working($where_query=[
            'eip_user.id '.($unselected ? 'NOT' : '').' in ('.implode(',', $user_ids).')',
        ], $params);
        // dump($skills);exit;
        return $users;
    }
    static public function contract_select_user($caseid, $user_ids){
        $data = [];
        foreach ($user_ids as $value) {
            array_push($data, [
                'caseid' => $caseid,
                'user_id' => $value['user_id'],
            ]);
        }
        try {
            $result = D('crm_contract_user')->addAll($data);
            if($result){
                Common::error_log('新增自行排班人員, 資料：'.json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $th) { /*因為(caseid,user_id)的獨一限制 或 其他*/
            $result = 0;
        }
        return $result;
    }
    static public function contract_delete_user($caseid, $user_ids){
        $ids = [];
        foreach ($user_ids as $value) {
            array_push($ids, $value['user_id']);
        }
        try {
            $result = D('crm_contract_user')->where('caseid="'.$caseid.'" AND user_id in ('.implode(',', $ids).')')->delete();
            if($result){
                Common::error_log('刪除自行排班人員, 合約：'.$caseid.', 名單：'.json_encode($ids, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $th) {
            $result = 0;
        }
        return $result;
    }
    

    /** 
     * 依傳入之合約類別回傳新的合約編號
     * @param int $cate 合約類別id
     * @return string 新合約類別編號
     */
    static public function get_new_sn_number($cate){
        $crm_cum_cat = M("crm_cum_cat")->where("id='".$cate."'")->find();
        $date = date("Ymd");
        $count=D("crm_contract")->where("`cate`=".$cate." and `cdate`>".strtotime($date))->count();
        $sn_new = $crm_cum_cat['sn_num'].$date.str_pad($count+1, 3,'0', STR_PAD_LEFT);
        return $sn_new;
    }

    /** 
     * 依客戶列表及合約列表篩選條件回傳合約(另可指定是否只顯示「與人員相關」的合約)
     * @param int $get_or_pay 收款或付款合約(0.收款 1.付款)
     * @param bool $check_cooperation 是否只顯示「與人員相關」的合約
     * @return array 符合篩選條件的合約陣列
     */
    static public function get_contracts($get_data=[], $get_or_pay=0, $check_cooperation=true){
        $result = self::get_contract_where_sql($get_data, $get_or_pay);
        if(!$check_cooperation){ /*不只顯示「與人員相關」的合約*/
            /*刪除協同人員的篩選*/
            $crm_cum_pri=D("crm_cum_pri")->where("status=1")->order("orders asc, id asc")->select();
            foreach($crm_cum_pri as $key=>$vo){
                $result['where_query'] = str_replace("crm_crm.{$vo['ename']} in( ".session('childeid').")", 'true', $result['where_query']);
            }
        }
        $result['where_query'] = str_replace("get_or_pay", 'crm_contract.get_or_pay', $result['where_query']);
        // dump($result['where_query']);exit;
        $contracts = D('crm_contract')->field("crm_contract.*, FROM_UNIXTIME(crm_contract.sign_date) as sign_date_f,
                                               crm_crm.name as name, 
                                               crm_cum_cat.name as cate_name,
                                               crm_cum_flag.name as flag_name")
                                      ->join("left join crm_crm on crm_crm.id=crm_contract.cid")
                                      ->join("left join crm_cum_cat on crm_cum_cat.id=crm_contract.cate")
                                      ->join("left join crm_cum_flag on crm_cum_flag.id=crm_contract.flag")
                                      ->where($result['where_query'])
                                      ->select();
        return $contracts;
    }
    /** 
     * 依客戶列表及合約列表篩選條件回傳sql where語法 (篩選條件透過$_GET傳入)
     * @param int $get_or_pay 收款或付款合約(0.收款 1.付款)
     * @return array An associative array with the following keys:
     * - swhere: string 
     * - where_query: string 合約的資料庫篩選語法
     * - order_query: string 合約的資料庫排序語法
     * - levels: array 搜尋區等級選單的資料
     * - industr_all: array 完整產業大項列表(更換產業用大項的資料用)
     * - industr: array 搜尋結果相關產業大項列表(搜尋區使用)
     * - industr2_search: array 搜尋結果相關產業次項列表(搜尋區使用)
     */
    static public function get_contract_where_sql($get_data=[], $get_or_pay=0){
        /*處理客戶相關參數篩選*/
        $customer_get_data = json_decode(json_encode($get_data), true);
        unset($customer_get_data['flag2']);  /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['cate']);   /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['cdate']);  /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['contract_text']); /*清除客戶列表搜尋中不需要的參數*/

        unset($customer_get_data['qh']);     /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['m_mdate']);/*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['order']);  /*清除客戶列表搜尋中不需要的參數*/

        unset($customer_get_data['queryflag']);  /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['getedflag']);  /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['year']);   /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['month']);  /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['startdate']);  /*清除客戶列表搜尋中不需要的參數*/
        unset($customer_get_data['enddate']);    /*清除客戶列表搜尋中不需要的參數*/
        
        unset($customer_get_data['flag3']);  /*清除客戶列表搜尋中不需要的參數*/

        /*檢查看全部權限*/
        $daoModel = empty($model)?strtolower(CONTROLLER_NAME)."_all" : $model."_all";
        $acc = Common::get_my_access();

        $return_data = CustoHelper::search_customer($acc, $customer_get_data, $page_count=NULL, $type_count=false);
        $return_data = CustoHelper::merge_industr_with_search_customer($customer_get_data, $return_data);
        $swhere = $return_data['swhere'];
        $crm_where = $return_data['all_where'];
        $order_query = $return_data['order_query'];

        $crm_cum_pri=D("crm_cum_pri")->where("status=1")->order("orders asc, id asc")->select();
        if($acc[$daoModel] == 1){ /*有此處的看全部*/
            /*刪除協同人員的篩選*/
            foreach($crm_cum_pri as $key=>$vo){
                $crm_where = str_replace("crm_crm.{$vo['ename']} in( ".session('childeid').")", 'true', $crm_where);
            }
        }elseif($acc[$daoModel]==0 && $acc['custo_all']==1 && !$customer_get_data['teamid']){ /*沒此處的看全部 但有 客戶列表看全部 且 非以組員查看*/
            /*添加協同人員的篩選*/
            $swhere = '';
            foreach($crm_cum_pri as $key=>$vo){
                $swhere.="crm_crm.{$vo['ename']} in( ".session('childeid').") or ";
            }
            $crm_where .=" and (".$swhere." false )";
        }
        // dump($crm_where);

        $contract_where = ' and  get_or_pay="'.$get_or_pay.'"';
        foreach ($get_data as $key => $value) {
            if($key=='flag'){
                if($value!='' && $value!='-1'){ /*執行、垃圾桶、歸檔*/
                    $contract_where .= ' and flag ="'.$value.'"'; 
                }
            }else if($key=='flag2'){
                if($value){ /*執行、垃圾桶、歸檔*/
                    $contract_where .= ' and flag2 ="'.$value.'"'; 
                }
            }else if($key=='flag3'){
                if($value!='' && $value!='-1'){ /*收款中、款收罄*/
                    $contract_where .= ' and flag3 ="'.$value.'"'; 
                }
            }else if(in_array($key, ['cate', 'pay_to', 'belongs_to'])){ /*合約類別、付款對象、附屬對象*/
                if($value!=''){
                    $contract_where .= ' and '.$key.' ="'.$value.'"';
                }
            }else if($key=='crm_contract_id'){ /*合約id*/
                if($value!=''){
                    $contract_where .= ' and crm_contract.id ="'.$value.'"'; 
                }
            }else if($key=='cdate'){
                if($value){ /*合約年月*/
                    $contract_where .= ' and sn like "%'.$value.'%"'; 
                }
            }else if($key=='cdate_start'){
                if($value){ /*合約建立開始日期*/
                    $contract_where .= ' and cdate >='.strtotime($value); 
                }
            }else if($key=='cdate_end'){
                if($value){ /*合約建立結束日期*/
                    $contract_where .= ' and cdate <='.strtotime($value.'+1Day'); 
                }
            }else if($key=='sign_date_start'){
                if($value){ /*合約簽訂開始日期*/
                    $contract_where .= ' and sign_date >='.strtotime($value); 
                }
            }else if($key=='sign_date_end'){
                if($value){ /*合約簽訂結束日期*/
                    $contract_where .= ' and sign_date <='.strtotime($value.'+1Day'); 
                }
            }else if($key=='contract_text'){
                if($value){ /*合約文字輸入(比對合約編號、合約備註)*/
                    $value = trim($value);
                    $contract_where .= ' and ( sn like "%'.$value.'%" OR note like "%'.$value.'%")';
                }
            }
        }

        $result = [
            'swhere' => $swhere,
            'where_query' =>$crm_where.$contract_where,
            'order_query' => $order_query,
            /*搜尋區等級選單的資料*/
            'levels' => $return_data['levels'],
            /*搜尋區通用產業選單*/
            'industr' => $return_data['industr'],
            'industr_all' => $return_data['industr_all'],
            'industr2_search' => $return_data['industr2_search'],
        ];
        return $result;
    }

    static public function get_pay_words($get_or_pay=0){
        return [
            '已收' => $get_or_pay==0 ? '已收' : '已付',
            '未收' => $get_or_pay==0 ? '未收' : '未付',
            '收訖' => $get_or_pay==0 ? '收訖' : '付訖',
            '收款' => $get_or_pay==0 ? '收款' : '付款',
            '出貨' => $get_or_pay==0 ? '出貨' : '取貨',
            '預收' => $get_or_pay==0 ? '預收' : '預付',
            '應收' => $get_or_pay==0 ? '應收' : '應付',
            '收罄' => $get_or_pay==0 ? '收罄' : '付罄',
            '超收' => $get_or_pay==0 ? '超收' : '超付',
        ];
    }

    static public function set_sign_date($case_id){
        $crm_contract = D('crm_contract')->where("id='".$case_id."'")->find();
        if($crm_contract){
            if(!$crm_contract['sign_date']){
                D('crm_contract')->data([
                    'sign_date' => time(),
                ])
                ->where("id='".$case_id."'")->save();
            }
        }
    }
}
