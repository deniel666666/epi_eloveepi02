<?php
namespace Photonic;
use Think\Controller;

use Photonic\Common;
use Photonic\IndustrHelper;

class CustoHelper extends Controller
{
	function _initialize(){
    }

    public static function instance(){
        return new CustoHelper();
    }

    static public function check_crm_num($add_num=1){
        if(self::$control_crm_num!=-1){
            $crm_crm_count = D('crm_crm')->count();
            if($crm_crm_count+$add_num > self::$control_crm_num){
                return true; /*超出上限*/
            }
        }
        return false; /*未超出上限*/
    }

    // 設定起追日期
    public static function get_newclient_date($target_id=0, $update_data=[]){
        if($update_data['wid']=="-1" || $update_data['wid']==""){ unset($update_data['wid']); }
        if($update_data['typeid']=="-1" || $update_data['typeid']==""){ unset($update_data['typeid']); }

        $newclient_date = null;     /*回傳的起追日期*/
        $ori_wid = 0;               /*原業務*/
        $ori_typeid = 0;            /*原客戶類別*/
        $ori_newclient_date = "";   /*原起追日期*/
        $crm = D('crm_crm')->where('id="'.$target_id.'"')->find(); /*找出修改的目標客戶*/
        if($crm){
            $ori_wid = $crm['wid'];
            $ori_typeid = $crm['typeid'];
            $ori_newclient_date = $crm['newclient_date'];
        }

        $need_change_date = false;
        /*判斷是否要更換起追日期*/
            if( $update_data['typeid'] && $update_data['typeid']!=$ori_typeid ){ /*有要修改分類 且 新分類與原分類不同*/
                $need_change_date = true;
            }
            if( $update_data['wid'] && $update_data['wid']!=$ori_wid ){ /*有要修改業務 且 新業務與原業務不同*/
                $need_change_date = true;
            }

        $need_count = false;
        /*判斷是否必要設定起追日期*/
            if( $update_data['typeid'] && $update_data['typeid']==1 ){ /*有要修改分類 且 新分類為新進*/
                $need_count = true;
            }
            if( !$update_data['typeid'] && $ori_typeid==1 ){ /*不修改分類 且 原分類為新進*/
                $need_count = true;
            }

        // dump($need_change_date);
        // dump($need_count);
        // exit;

        if($need_change_date){ /*如果需更換起追日期*/
            if($need_count){ /*如果有必要設定起追日期*/
                /*找出目標業務*/
                    $target_wid = $ori_wid; /*預設目標業務為原業務*/
                    if($update_data['wid']){ /*如有要修改業務*/
                        $target_wid = $update_data['wid']; /*設定目標業務為新業務*/
                    }

                    $user = D('eip_user')->where('id="'.$target_wid.'"')->find(); /*找出目標業務*/
                    if(!$user){ /*使用者不存在*/
                        $newclient_date = null;
                    }
                    else if($user['is_anysis']==0){ /*使用者關閉週分析*/
                        $newclient_date = null;
                    }
                    else{
                        $newclient_date = date('Y-m-d');
                    }
            }
            else{
                $newclient_date = null;
            }
        }
        else{  /*如果不需更換起追日期，回傳原始起追日期*/
            $newclient_date = $ori_newclient_date;
        }

        return $newclient_date;
    }

    // 計算潛在客戶上限
    static public function potNum_anysis($wid, $edi_ids){
        if(!in_array(51, self::$use_function_top)){ /*未使用週分析*/
            return '';
        }

        /*計算此業務累積的潛在數量*/
        $potCnt = D('crm_crm')->where('wid="'.$wid.'" AND typeid = 2')->count();
        $potCnt = (int)$potCnt;

        /*計算修改中對象中，原本就是此業務的潛在的數量*/
        // dump($edi_ids);
        $potOriCnt = D('crm_crm')->where('wid="'.$wid.'" AND typeid = 2 and id in ('.implode(',', $edi_ids).')')->count();
        $potOriCnt = (int)$potOriCnt;
        // dump($potOriCnt);exit;

        //取得此業務的設定
        $user = D('eip_user_data')->join("join eip_user on eip_user.id=eip_user_data.eid")->where('eid="'.$wid.'"')->find();
        $potNum = (int)$user['pot_num'];

        // dump($edi_ids);dump($potCnt);dump($potNum);
        if($user['is_anysis']=='1'){
            if ( (count($edi_ids) - $potOriCnt + $potCnt ) > $potNum){
                return $user['name']." 超過潛客戶上限!!";
            }
        }else{
            return '';
        }
    }

    //添加客戶轉換紀錄(週分析客戶轉換數使用)
    static public function add_salesrecord($salesid, $opeid, $cid, $typeid, $new=false){
        if(!$salesid){ return; }

        $user = D('crm_crm')->where('id="'.$cid.'"')->field('typeid,wid')->find();
        $saldata = [
            'salesid'   => $salesid,    // 業務id
            'opeid'     => $opeid,      // 操作人員
            'cid'       => $cid,        // 客戶id
            'ctype'     => $typeid,     // 修改的客戶類型(預設無)
            'dateline'  => time(),
        ];

        if($saldata['ctype']){
            if($new){
                D("salesrecord")->data($saldata)->add();
            }
            else if($user['typeid'] != $typeid || $user['wid'] != $salesid){
                D("salesrecord")->data($saldata)->add();
            }
        }
    }

    /*取得顯示公司名稱*/
    static public function get_crm_show_name($crm_id, $full_name=false){
        $show_name = "";
        $crm = D('crm_crm')->where(['id' => $crm_id])->find();

        if (empty($crm) == false) {
            if ($full_name == true) {
                $show_name = $crm['name'];
            } else {
                if (empty($crm['nick']) == false) {
                    $show_name = $crm['nick'];
                } else {
                    $show_name = mb_substr($crm['name'], 0, 5);
                }
            }
        }

        return $show_name;
    }
    /*依需求取得顯示地址*/
    static public function get_crm_show_addr($crm_data, $type='visit'){
        $show_addr = '';
        if($type=='visit'){ /*取拜訪地址*/
            if($crm_data['addr'] != ''){
                $show_addr = $crm_data['addr'];
            }
            else if($crm_data['accounting_addr'] != ''  &&  $crm_data['accounting_addr'] != '1'){
                $show_addr = $crm_data['accounting_addr'];
            }
            else if($crm_data['shipment_addr'] != ''  &&  $crm_data['shipment_addr'] != '1'){
                $show_addr = $crm_data['shipment_addr'];
            }
            else if($crm_data['factory_addr'] != ''  &&  $crm_data['factory_addr'] != '1'){
                $show_addr = $crm_data['factory_addr'];
            }
            else if($crm_data['register_addr'] != '' &&  $crm_data['register_addr'] != '1'){
                $show_addr = $crm_data['register_addr'];
            }
        }
        else if($type=='mail'){ /*取寄信地址*/
            if($crm_data['accounting_addr'] != ''  &&  $crm_data['accounting_addr'] != '1'){
                $show_addr= $crm_data['accounting_addr'];
            }
            else if($crm_data['addr'] != ''){
                $show_addr= $crm_data['addr'];
            }
            else if($crm_data['shipment_addr'] != ''  &&  $crm_data['shipment_addr'] != '1'){
                $show_addr= $crm_data['shipment_addr'];
            }
            else if($crm_data['factory_addr'] != ''  &&  $crm_data['factory_addr'] != '1'){
                $show_addr= $crm_data['factory_addr'];
            }
            else if($crm_data['register_addr'] != '' &&  $crm_data['register_addr'] != '1'){
                $show_addr= $crm_data['register_addr'];
            }
        }

        return trim($show_addr);
    }

    /*修改輸入文字已符合sql篩選(主要用於json格式的文字比對)*/
    static public function replace_str_to_do_sql_search($str){
        $str = str_replace("/", '\\\\\\\\/', $str);
        return $str;
    }

    // 寄送小事件提醒給處理者
    public function send_smallthing_remind($chat, $user_id, $cumid){
        $remind_users = self::get_remind_users($user_id);
        $user = $remind_users['user'];
        
        $crm = D('crm_crm')->find($cumid);
        if(!$user || !$crm){ return; }

        $show_name = self::get_crm_show_name($crm['id']);
        $mail_title = self::$system_parameter['小事']."提醒";
        $crm_url = "http://".$_SERVER['HTTP_HOST']."/index.php/Custo/view/id/".$cumid.".html?tab=tab5";
        // dump($user); exit;
        foreach ($user as $key => $value) {
            $body ="
                <p>親愛的_".$value['name']."_您好：</p>
                <p>".self::$system_parameter['客戶']."：".$show_name."</p>
                <p>對話內容：<br>".str_replace("\n", '<br>', $chat['content'])."</p>
                <p>有".self::$system_parameter['小事']."需要您處理，再麻煩登入系統處理</p>
                <p>".self::$system_parameter['客戶']."網址：<a href='".$crm_url."'>".$crm_url."</a></p>";
            send_email($body, $value['email'], $mail_title);
        }

        $payload = [
            'title' => $mail_title,
            'msg' => "請登入系統處理",
            'open_url' => $crm_url,
        ];
        Common::send_notification_to_user($user_id, $payload);
    }
    // 寄送小事件完成給建立者
    static public function send_smallthing_done($chat, $user_id, $cumid){
        $remind_users = self::get_remind_users($user_id);
        $user = $remind_users['user'];
        
        $crm = D('crm_crm')->find($cumid);
        if(!$user || !$crm){ return; }

        $show_name = self::get_crm_show_name($crm['id']);
        $mail_title = self::$system_parameter['小事']."已完成";
        $crm_url = "http://".$_SERVER['HTTP_HOST']."/index.php/Custo/view/id/".$cumid.".html?tab=tab5";
        // dump($user); exit;
        foreach ($user as $key => $value) {
            $body ="
                <p>親愛的_".$value['name']."_您好：</p>
                <p>".self::$system_parameter['客戶']."：".$show_name."</p>
                <p>對話內容：<br>".str_replace("\n", '<br>', $chat['content'])."</p>
                <p>".self::$system_parameter['小事']."已完成，再麻煩登入系統確認</p>
                <p>".self::$system_parameter['客戶']."網址：<a href='".$crm_url."'>".$crm_url."</a></p>";
            send_email($body, $value['email'], $mail_title);
        }

        $payload = [
            'title' => $mail_title,
            'msg' => "請登入系統查看",
            'open_url' => $crm_url,
        ];
        Common::send_notification_to_user($user_id, $payload);
    }
    static public function get_remind_users($user_id=""){
        if($user_id){
            $where_query = 'e.is_job=1 && e.id!='.self::$top_adminid.' && e.id="'.$user_id.'"';
        }else{
            $where_query = 'e.is_job=1 && e.id!='.self::$top_adminid.' && (e.`no` like "%正%" or e.`no` like "%臨%")';
        }
        $user = D('eip_user e')->where($where_query)->select();
        return [
            'user'=>$user,
        ];
    }


    /*依照欄位資料表、篩選條件，回傳欄位資料*/
    static public function get_fields($fieldsTable, $cond=[]){
        $crm_property = D($fieldsTable);
        if($cond){
            if($cond['online']!=""){
                $crm_property = $crm_property->where('online="'.$cond['online'].'"');
            }
        }
        
        $crm_property = $crm_property->order('order_id asc, id desc')->select();
        foreach ($crm_property as $key => $value) {
            $crm_property[$key]['options'] = $value['options'] ? json_decode($value['options']) : [];
            $crm_property[$key]['select'] = false;
        }

        return $crm_property;
    }
    /*依照欄位資料表、填寫紀錄，回傳合併預設答案的欄位資料*/
    static public function get_fields_with_ans($fieldsTable, $fields_data){
        $crm_property = self::get_fields($fieldsTable, ['online'=>1]);
        foreach ($crm_property as $key => $value) {
            /*答案併入問題中*/
            $ans_key = 'field_id_'.$value['id'];
            if( isset($fields_data[$ans_key]) ){ /*有紀錄的話，依紀錄*/
                $crm_property[$key]['ans'] = $fields_data[$ans_key];
            }
            else{ /*沒紀錄的話，依預設*/
                $crm_property[$key]['ans'] = self::set_field_default_ans($value['type']);
            }
        }

        return $crm_property;
    }
    /*依欄位類型設定預設答案*/
    static public function set_field_default_ans($input_type){
        if( in_array($input_type, ['radio', 'radio_box', 'checkbox', 'checkbox_box']) ){ /*選項類型*/
            $ans = [];
        }
        elseif( in_array($input_type, ['file']) ){ /*檔案類型*/
            $ans = (object)["file_name"=>"", "data"=>""];
        }
        else{ /*文字類型*/
            $ans = "";
        }
        
        return $ans;
    }
    /*依照欄位資料表、填寫紀錄，檢驗並回傳要儲存的資料*/
    public function check_ans_of_fields($fieldsTable, $fields_data=[], $saved_fields_data){
        // if(empty($fields_data)){ $this->error('無資料需儲存'); }

        $ans_data = [];
        $crm_property = self::get_fields($fieldsTable, ['online'=>1]);
        foreach ($crm_property as $key => $value) {
            /*設定問題答案*/
            $ans_key = 'field_id_'.$value['id'];
            if(isset($fields_data[$ans_key])){ /*有輸入的話，依使用者輸入*/
                if($value['type']=='file'){
                    $fields_data[$ans_key] = (object)$fields_data[$ans_key];
                    $image_base64 = $fields_data[$ans_key]->data;
                    if($image_base64=='delete'){
                        $fields_data[$ans_key] = self::set_field_default_ans($value['type']);
                    }
                    else if( mb_substr($image_base64, 0, 8)!="/Uploads" && $image_base64!='' ){
                        $fields_data[$ans_key]->data = Common::uploadFile('/Uploads/crm_property', $image_base64);
                    }
                    else if(isset($saved_fields_data[$ans_key])){ /*有紀錄的話，依紀錄*/
                        $fields_data[$ans_key] = $saved_fields_data[$ans_key];
                    }
                }

                $ans = $fields_data[$ans_key];
            }
            else{ /*沒輸入的話，依預設*/
                $ans = self::set_field_default_ans($value['type']);
            }

            /*檢查必填*/
            if($value['type']=='file' && $value['required']==1 && ($ans->file_name=="" || $ans->data=="")){
                $this->error("請輸入必填欄位：" . $value['title']);
            }
            if($value['required']==1 && ($ans=="" || $ans==[])){
                $this->error("請輸入必填欄位：" . $value['title']);
            }

            /*檢查格式*/
            if( in_array($value['type'], ['radio', 'radio_box', 'checkbox', 'checkbox_box', 'select']) ){ /*選項類型*/
                if($ans){
                    if(is_array($ans)){
                        foreach ($ans as $ans_k=>$ans_v) {
                            if( !in_array($ans_v, $value['options']) ){
                                unset($ans[$ans_k]);
                                // $this->error("欄位：" . $value['title'] . " 格式有誤，請重新選擇");
                            }
                        }
                    }else{
                        if( !in_array($ans, $value['options']) ){
                            $ans = "";
                            // $this->error("欄位：" . $value['title'] . " 格式有誤，請重新選擇");
                        }
                    }
                }
            }
            else if( in_array($value['type'], ['file']) ){ /*檔案類型*/
                if($value['limit'] && $ans->data){
                    $file_type = explode('.', $ans->file_name);
                    $file_type = end($file_type);
                    if( !preg_match("/$file_type/", $value['limit']) ){
                        $this->error("檔案：" . $value['title'] . " 格式有誤，請重新選擇");
                    }
                }
            }
            else{ /*文字類型*/
                if($value['limit'] && $ans){
                    $pattern = $value['limit'];
                    if( !preg_match("/$pattern/", $ans) ){
                        $this->error("欄位：" . $value['title'] . " 格式有誤，請重新輸入");
                    }
                }
            }

            $ans_data[ $ans_key ] = $ans;
        }

        return $ans_data;
    }

    static public function get_search_query($acc, $get_data, $page_count=NULL, $type_count=true){
        // dump($get_data);exit;
        $cid = session('cid');
        $page = Common::getArgs('page') ? Common::getArgs('page') : 1;

        // 處理排序
        $order_query = "";
        if($get_data['orders'] != '-1' && $get_data['orders'] != ''){
            $order_list = explode(":", $get_data['orders']);
            $order_query =  " ".$order_list[0]." ".$order_list[1].",";
            unset($get_data['orders']);
        }

        if($get_data['typeid']=='-1' || $get_data['typeid']==''){
            unset($get_data['typeid']);
        }
        if($get_data['levelid']=='-1' || $get_data['levelid']=='all'){
            unset($get_data['levelid']);
        }
        // dump($get_data);
        // 處理組別相關搜尋
        $crm_cum_pri = D("crm_cum_pri")->where("status=1")->order("orders asc, id asc")->select();
        $crm_cum_pri_ename_list = array_map(function($item){ return $item['ename'];}, $crm_cum_pri);
        $rData['crm_cum_pri'] = $crm_cum_pri;

        $team_id = '';      // 允許查看的協同人員們
        $swhere=" crm_crm.id='".self::$our_company_id."' "; // 篩選屬於對應協同人員的客戶語法
        $acc_custo_all = $acc['custo_all'] ?? '';
        if($get_data['teamid']){ // 使用組別查看
            $eip_team_access = D("eip_team")
                                ->where("(`id`={$get_data['teamid']}  and status=1 ) and 
                                         ( boss_id = '".$_SESSION['adminId']."' or childeid like '%".$_SESSION['adminId']."%') ")
                                ->field('id,name,boss_id,childeid,each_customers,show_leader_customers,edit_member_customes,edit_leader_customers')
                                ->find();
            // dump($eip_team_access);
            if($eip_team_access){ // 自己有在組別內
                if($get_data['teamid'] != self::$top_teamid){ // 不是最高權限組
                    // dump(session('childeid'));
                    if($eip_team_access['each_customers']=="1" || 
                       $eip_team_access['boss_id']==session('adminId')){// 查看組員 或 自己是組長
                        $team_list = str_replace('"', '', $eip_team_access['childeid']);
                        $team_id = str_replace('、', ',', $team_list);
                    }else if(preg_match(session('childeid'), $eip_team_access['childeid'])){ // 自己是組員
                        $team_id = session('childeid');
                    }

                    if($eip_team_access['show_leader_customers']=="1"){//查看組長
                        if($team_id != ''){ $team_id .= ','; }
                        $team_id .= $eip_team_access['boss_id'];
                    }

                    if($team_id){ // 有能看的組別人員
                        $swhere .= ' or ';
                        foreach($crm_cum_pri as $key=>$vo){
                                $swhere.="crm_crm.{$vo['ename']} in( ".$team_id.") or ";
                        }
                        $swhere ="(".$swhere." false )";
                    }
                }else{
                    $swhere=" true ";
                }
            }
        }
        else if(false
                || session('adminId') == self::$top_adminid // 或是管理員
                || $acc_custo_all == 1                      // 或有看全部
                || !self::$system_parameter['協同人員']     // 或未使用協同人員
        ){ 
            $swhere=" true ";
        }
        else{
            $swhere .= ' or ';
            foreach($crm_cum_pri as $key=>$vo){
                $swhere.="crm_crm.{$vo['ename']} in( ".session('childeid').") or ";
            }
            $swhere ="(".$swhere." false )";
        }
        // dump($swhere);exit;
        // dump($team_id);

        $rData['team_id'] = $team_id;
        $twhere="";                                         // 客戶類型的篩選語法
        $where="";                                          // 搜尋條件的篩選語法
        $where_need_chats_sql = "";                         // 篩選客戶是否有訪談紀錄
        $crm_chats_where = "";                              // 訪談記錄搜尋
        if(!empty($get_data)){
            foreach($get_data as $k=>$v){
                if(!is_array($v)){
                    $v = trim($v);
                }else{
                    $v = implode(",", $v);
                }
                $get_data[$k] = trim($get_data[$k]);

                // 處理篩選條件(無預設篩選方式，有需要使用的變數都需要設定)
                    if($k=='typeid'){ //類別分離
                        if($v>0){
                            $twhere .=' and crm_crm.'.$k.' = \''.$v.'\' ';
                        }
                    }
                    else if($k=='typeids' && $v){ //類別分離
                        $twhere .=' and crm_crm.typeid in ('.$v.')';
                    }
                    else if($k=='view' && $v=='crmtrace'){ /*如果是 現況追蹤 的搜尋，只顯示有訪談紀錄的客戶*/
                        $where_need_chats_sql .= ' AND last_chat.last_qulid !=""';
                    }
                    else if(in_array($k, ["industr", "industr2", "levelid", "newclient_date", "status_supplier"]) && $v!=""){ //完整比對的crm_crm欄位
                        $where .=' and crm_crm.'.$k.' = \''.$v.'\' ';
                    }
                    else if(in_array($k, ["no", "mom"])){ //模糊比對的crm_crm欄位
                        if($v!='' && !is_null($v)){
                            $where .=" and crm_crm.".$k." like '%".$v."%' ";
                        }
                    }
                    else if(in_array($k, $crm_cum_pri_ename_list) && $v!=""){ // 協同處理人員搜尋
                        if (array_key_exists('no_'.$k, $get_data)){
                            if($get_data['no_'.$k] == '1'){ // 有勾選排除
                                $where.=" and crm_crm.$k != '$v'";
                            }else{
                                $where.=" and crm_crm.$k = '$v'";
                            }
                        }
                    }
                    else if($k=='crm_id' && $v!=""){ // crm_crm id
                        $where .=' and crm_crm.id = \''.$v.'\' ';
                    }
                    else if($k=='country' && $v!=""){
                        $id=$get_data['schaddr'];
                        $rData['country'] = $v;

                        $v_1 =  str_replace('台','臺',$v);
                        $v_2 =  str_replace('臺','台',$v);
                        $where .=' and (crm_crm.addr like \'%'.$v_1.'%\' or crm_crm.addr like \'%'.$v_2.'%\' )';
                    }
                    else if($k=='district' && $v!=""){  
                        $where .=' and crm_crm.addr like \'%'.$v.'%\' ';
                        $rData['district'] = $v;
                    }
                    else if ($k=='name'){ /*搜尋公司名稱*/
                        $v_1 =  str_replace('台','臺',$v);
                        $v_2 =  str_replace('臺','台',$v);
                        $where .=' and (crm_crm.name like \'%'.$v_1.'%\' or crm_crm.nick like \'%'.$v_1.'%\' or crm_crm.name like \'%'.$v_2.'%\' or crm_crm.nick like \'%'.$v_2.'%\')';
                    }
                    else if($k=='bossname'){ /*搜尋人名*/
                        $contacter = D('crm_contact')->where('cname like \'%'.$v.'%\'')->group('cumid')->select();
                        $cumids = [];
                        foreach ($contacter as $key => $value) {
                            array_push($cumids, $value['cumid']);
                        }
                        $cumids_where = $cumids ? 'or crm_crm.id in ('.implode(',', $cumids).') ' : '';
                        $where .=' and (crm_crm.bossname like "%'.$v.'%" '.$cumids_where.')';
                    }
                    else if($k=='bossphone'){ /*搜尋電話*/
                        $v = preg_replace("/[_\-()]/i", '', $v);
                        $contacter = D('crm_contact')->where('REPLACE(REPLACE(REPLACE(REPLACE(phone, " ", ""), "-", ""), "(", ""), ")", "") like \'%'.$v.'%\'')->group('cumid')->select();
                        $cumids = [];
                        foreach ($contacter as $key => $value) {
                            array_push($cumids, $value['cumid']);
                        }
                        $cumids_where = $cumids ? 'OR crm_crm.id in ('.implode(',', $cumids).') ' : '';
                        $where .='AND (
                                    REPLACE(REPLACE(REPLACE(REPLACE(crm_crm.comphone, " ", ""), "-", ""), "(", ""), ")", "") like "%'.$v.'%" OR 
                                    REPLACE(REPLACE(REPLACE(REPLACE(crm_crm.bossphone, " ", ""), "-", ""), "(", ""), ")", "") like "%'.$v.'%"
                                    '.$cumids_where.'
                                )';
                    }
                    else if($k=='bossmobile'){ /*搜尋手機*/
                        $v = preg_replace("/[_\-()]/i", '', $v);
                        $contacter = D('crm_contact')->where('REPLACE(REPLACE(REPLACE(REPLACE(mobile, " ", ""), "-", ""), "(", ""), ")", "") like \'%'.$v.'%\'')->group('cumid')->select();
                        $cumids = [];
                        foreach ($contacter as $key => $value) {
                            array_push($cumids, $value['cumid']);
                        }
                        $cumids_where = $cumids ? 'OR crm_crm.id in ('.implode(',', $cumids).') ' : '';
                        $where .='AND (
                                    REPLACE(REPLACE(REPLACE(REPLACE(crm_crm.commobile, " ", ""), "-", ""), "(", ""), ")", "") like "%'.$v.'%" OR 
                                    REPLACE(REPLACE(REPLACE(REPLACE(crm_crm.bossmobile, " ", ""), "-", ""), "(", ""), ")", "") like "%'.$v.'%" 
                                    '.$cumids_where.'
                                )';
                    }
                    else if($k=='bossmail'){ /*搜尋信箱*/
                        $contacter = D('crm_contact')->where('mail like \'%'.$v.'%\'')->group('cumid')->select();
                        $cumids = [];
                        foreach ($contacter as $key => $value) {
                            array_push($cumids, $value['cumid']);
                        }
                        $cumids_where = $cumids ? 'OR crm_crm.id in ('.implode(',', $cumids).') ' : '';
                        $where .=' AND (
                                    crm_crm.commail like "%'.$v.'%" OR
                                    crm_crm.bossmail like "%'.$v.'%" 
                                    '.$cumids_where.'
                                )';
                    }
                    else if($k=='url1'){ /*搜尋網址*/
                        $crm_website = D('crm_website')->where('url like \'%'.$v.'%\'')->group('cumid')->select();
                        $cumids = [];
                        foreach ($crm_website as $key => $value) {
                            array_push($cumids, $value['cumid']);
                        }
                        $cumids_where = $cumids ? 'or crm_crm.id in ('.implode(',', $cumids).') ' : '';
                        $where .=' and (crm_crm.url1 like "%'.$v.'%" or crm_crm.url2 like "%'.$v.'%" '.$cumids_where.')';
                    }
                    else if($k=='zbe_start' && $v != ''){ /*資本額區間 起*/
                        $where .=' and REPLACE(crm_crm.zbe, ",", "") >= '. $v;
                    }
                    else if($k=='zbe_end' && $v != ''){ /*資本額區間 訖*/
                        $where .=' and REPLACE(crm_crm.zbe, ",", "") <= '. $v;
                    }
                    else if($k=='hzrq_start' && $v != ''){ /*成立時間區間 起*/
                        $where .=' and UNIX_TIMESTAMP(crm_crm.hzrq) >= '. strtotime($v);
                    }
                    else if($k=='hzrq_end' && $v != ''){ /*成立時間區間 訖*/
                        $where .=' and UNIX_TIMESTAMP(crm_crm.hzrq) <= '. strtotime($v);
                    }
                    else if($k=='fields_data'){ /*搜尋特性*/
                        $v = (Array)json_decode($v);
                        $where .= " and ";
                        foreach ($v as $fd_k => $fd_v) {
                            if($fd_v!="" && $fd_v!=[]){
                                if( is_array($fd_v) ){ /*選項類型*/
                                    $where .= '(';
                                    foreach ($fd_v as $vk => $vv) {
                                        if(is_null($vv)){
                                            $where .= '(fields_data is null OR fields_data like \'%"' .$fd_k. '":[]%\') and ';
                                        }
                                        else if($vv!=""){
                                            $str = CustoHelper::replace_str_to_do_sql_search($vv);
                                            $where .= 'fields_data like \'%"' .$fd_k. '":[%"' .$str. '"%]%\' and ';
                                        }
                                    }
                                    $where .= ' true ) and ';
                                }
                                else if (is_object($fd_v)) { /*檔案類型*/
                                    if($fd_v->file_name){
                                        $str = CustoHelper::replace_str_to_do_sql_search($fd_v->file_name);
                                        $where .= 'fields_data like \'%"' .$fd_k. '":{"file_name":"%' .$str. '%","data"%\' and ';
                                    }
                                }
                                else{
                                    $str = CustoHelper::replace_str_to_do_sql_search($fd_v);
                                    $where .= 'fields_data like \'%"' .$fd_k. '":"%' .$str. '%"%\' and ';
                                }
                            }
                        }
                        $where .= ' true ';
                        // dump($where);exit;
                    }
                    else if( $k=="chats_content"){ //模糊比對的crm_chats欄位
                        if($v!='' && !is_null($v)){
                            $crm_chats_where .=" and crm_chats.content like '%".$v."%' ";
                        }
                    }
                    else if( $k=="chat_qulid"){ //精準比對的crm_chats欄位
                        if($v!='' && !is_null($v)){
                            $crm_chats_where .=" and crm_chats.qulid = ".$v;
                        }
                    }
                    else if( $k=="chat_startdate"){ //比對大於crm_chats的dateline時間
                        if($v!='' && !is_null($v)){
                            $crm_chats_where .=" and crm_chats.dateline >= ".strtotime($v);
                        }
                    }
                    else if( $k=="chat_enddate"){ //比對小於crm_chats的dateline時間
                        if($v!='' && !is_null($v)){
                            $crm_chats_where .=" and crm_chats.dateline < ".strtotime($v.' +1day');
                        }
                    }
            }

            // $typeid5 .="  or crm_crm.typeid ='5' "; 
            if($get_data['searchname']){   
                $vo_name1 = str_replace ('台','臺',$get_data['searchname']);
                $vo_name2 = str_replace ('臺','台',$get_data['searchname']);
                $where .=' AND (
                                crm_crm.no like \'%'.$get_data['searchname'].'%\' OR 
                                crm_crm.commail like \'%'.$get_data['searchname'].'%\' OR 
                                crm_crm.industr like \'%'.$get_data['searchname'].'%\' OR 
                                crm_crm.url1 like \'%'.$get_data['searchname'].'%\' OR 

                                crm_crm.name like \'%'.$vo_name1.'%\' OR 
                                crm_crm.name like \'%'.$vo_name2.'%\' OR 
                                crm_crm.nick like \'%'.$vo_name1.'%\' OR 
                                crm_crm.nick like \'%'.$vo_name2.'%\' OR 

                                crm_crm.addr like \'%'.$vo_name1.'%\' OR 
                                crm_crm.addr like \'%'.$vo_name2.'%\' OR 
                                
                                crm_crm.bossmail like \'%'.$get_data['searchname'].'%\' OR 
                                crm_crm.bossname like \'%'.$get_data['searchname'].'%\' OR 
                                crm_crm.bossmobile like \'%'.$get_data['searchname'].'%\' OR 
                                crm_crm.bossphone like \'%'.$get_data['searchname'].'%\'
                            )';
            }
            //echo $where;
        }
        // dump($where);exit;
        $rData['swhere'] = $swhere;
        $rData['twhere'] = $twhere;
        $rData['where'] = $where;
        $rData['where_need_chats_sql'] = $where_need_chats_sql;
        $rData['crm_chats_where'] = $crm_chats_where;

        $rData['all_where'] = "({$swhere}{$where}){$twhere}";
        $rData['part_where'] = "( true {$where})";
        $rData['order_query'] = $order_query." crm_cum_type.sort,crm_crm.levelid,CAST(CONVERT(crm_crm.nick using big5) AS BINARY)";
        // dump($rData);exit;
        return $rData;
    }
    static public function search_customer($acc, $get_data, $page_count=NULL, $type_count=true){
        $rData = self::get_search_query($acc, $get_data, $page_coun, $type_count);
        $last_chat_sql = "
            LEFT JOIN (
                SELECT cumid AS last_cumid, qulid AS last_qulid, content AS last_content 
                FROM `crm_chats`
                INNER JOIN ( SELECT MAX(id) AS max_id FROM `crm_chats` GROUP BY cumid ) a ON a.max_id = crm_chats.id
            ) last_chat ON last_chat.last_cumid = crm_crm.id
        ";

        /*符合篩選的總客戶數(新進+潛在+成交)*/
        $total_where_query = " (".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].")";
        if($get_data['typeid']!=-2){ /*不是看『真』全部*/
            $total_where_query .= " AND (typeid = '1' OR typeid = '2' OR typeid = '3')"; /*僅計有用的全部(新進、潛在、現有)*/
        }
        $total = D('crm_crm')->join($last_chat_sql)->where($total_where_query)->count();
        $rData['total'] = $total;

        /*符合篩選的全部客戶*/
        if(($get_data['typeid'] < 0 || !$get_data['typeid']) ){
            $p_count = $total;
        }else if($get_data['typeid'] ==5){
            $p_count = D('crm_crm')->join($last_chat_sql)->where("typeid='5' ".$rData['where'].$rData['where_need_chats_sql'])->count();
        }else{
            $p_count = D('crm_crm')->join($last_chat_sql)->where("(".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].")".$rData['twhere'])->count();
        }
        if($page_count){
            $pagwAllA = new \Think\Page($p_count, $page_count);
            $pagwAllA->setConfig('prev',"上頁");
            $pagwAllA->setConfig('next',"下頁");
            $pagwAllA->setConfig('theme',"%UP_PAGE% %LINK_PAGE% %DOWN_PAGE%");
            $rData['pagwAllA'] = $pagwAllA;
            $rData['linit'] = $pagwAllA->firstRow;
            $rData['page'] = $pagwAllA->show();

            if( !empty($get_data['p']) ){
                $pagwAllA_firstRow = ((int)$get_data['p']-1) * (int)$pagwAllA->listRows;
            }else{
                $pagwAllA_firstRow = $pagwAllA->firstRow;
            }
            $limit_query = ' limit '.$pagwAllA_firstRow.','.$pagwAllA->listRows;
        }else{
            $rData['pagwAllA'] = '';
            $rData['linit'] = 0;
            $rData['page'] = '';
            $limit_query = '';
        }

        $select_field_sql = "
            crm_crm.*, crm_crm.name as show_name,
            crm_cum_type.name as type_name, crm_cum_type.cid, crm_cum_type.type, crm_cum_type.status, crm_cum_type.sort,
            crm_cum_level.name as level_name,
            last_chat.last_qulid, last_chat.last_content";
        $crmlist_query = "
            SELECT {$select_field_sql}
            FROM `crm_crm` 
            LEFT JOIN crm_cum_type ON crm_crm.typeid=crm_cum_type.id
            LEFT JOIN crm_cum_level ON crm_crm.levelid=crm_cum_level.id
            LEFT JOIN crm_chats ON crm_chats.cumid=crm_crm.id
            {$last_chat_sql}
        ";
        if(isset($get_data['typeids'])){
            $crmlist_query.="WHERE (".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].")".$rData['twhere'].$rData['crm_chats_where'];
        }else{
            if($get_data['typeid'] ==-2){ /*看『真』全部類別*/
                $crmlist_query.="WHERE (
                (".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].") 
                ) ".$rData['crm_chats_where'];
            }
            else if(($get_data['typeid'] ==-1 || !$get_data['typeid']) ){ /*看全部類別(新進、潛在、現有)*/
                $crmlist_query.="WHERE (
                    (".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].") AND 
                    (typeid = '1' OR typeid = '2' OR typeid = '3') 
                ) ".$rData['crm_chats_where'];
            }else if($get_data['typeid']==5){
                $crmlist_query.="WHERE typeid='5' ".$rData['where'].$rData['where_need_chats_sql'].$rData['crm_chats_where'];
            }else{
                $crmlist_query.="WHERE (".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].")".$rData['twhere'].$rData['crm_chats_where'];
            }
        }
        $crmlist_query .= "
            GROUP BY crm_crm.id
            ORDER BY ".$rData['order_query']."
            {$limit_query}
        ";
        $crmlist = D()->query($crmlist_query);
        // dump($crmlist);
        // dump(D()->getLastSql());exit;

        //依客戶列別統計數量
        $count=[];
        if($type_count == true){
            $cid = session('cid');
            $crmtype = D()->query("select * from crm_cum_type where `cid`=$cid and status=1 order by `id` asc");
            foreach($crmtype as $key=>$vo){
                if($vo['id'] == 5){
                    // $count=D()->query("SELECT COUNT(*) as count_num FROM `crm_crm` WHERE   typeid = '5' and ( true ".$rData['where'].")");
                    $count=[['count_num'=>0]];
                }else if($vo['id'] == 4){
                    // $count=D()->query("SELECT COUNT(*) as count_num FROM `crm_crm` WHERE   typeid = '4' and ( true ".$rData['where'].")");
                    $count=[['count_num'=>0]];
                }else{
                    $count=D()->query("SELECT COUNT(*) as count_num FROM `crm_crm` 
                                       {$last_chat_sql}
                                       WHERE (".$rData['swhere'].$rData['where'].$rData['where_need_chats_sql'].") and typeid='".$vo['id']."'");
                }
                //dump(D()->getLastSql());  
                $crmtype[$key]['count'] = $count[0]['count_num'];
            }
            //dump($crmtype);
            $rData['crmtype'] = $crmtype;
        }else{
            $rData['crmtype'] = [];
        }

        foreach ($crmlist as $key => $value) {
            $crmlist[$key]['show_name'] = CustoHelper::get_crm_show_name($value['id']);
            $crmlist[$key]['addr'] = CustoHelper::get_crm_show_addr($value);
        }
        $rData['crmlist'] = $crmlist;
        $rData['team_name'] = $eip_team_access ? $eip_team_access['name'] : '';
        // dump($crmlist);exit;
        
        return $rData;
    }
    static public function merge_industr_with_search_customer($get_data, $rData){
        // 產業選單
        /*跟你有關的產業大項(搜尋用)*/
        $rData['industr'] = D("crm_crm")->where($rData['swhere']."and `industr` != ''")->field("industr")->group("industr")->select();
        /*所有的產業大項(編輯用)*/
        $rData['industr_all'] = IndustrHelper::get_all_industr();
        /*蒐尋大項的產業次項*/
        $rData['industr2_search'] = D("crm_industr")->where("industr = '".$get_data['industr']."'")->field("industr2")->select();

        // 客戶等級選單
        $cid = session('cid');
        $rData['levels'] = D()->query("select `id`,`name` from crm_cum_level where `cid`=$cid and `status`=1 ");

        if($rData['team_id'] == ''){
            $rData['eip_user'] = Common::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and (no like '正%' or no like '臨%')", '', false);
        }else{
            $rData['eip_user'] = Common::index_set('eip_user',"is_job=1  and id !=".self::$top_adminid." and id in( ".$rData['team_id'].")", '', false);
        }

        return $rData;
    }

    /*文字處理選轉(需搭配前臺樣式)*/
    static public function add_rotate($arr){
        $new_str = "";
        foreach ($arr as $k => $v) {
            if(is_numeric($v) || in_array($v, ['-', '(', ')', '（', '）'])){
                $new_str .= '<span>'.$v.'</span>';
            }else{
                $new_str .= '<span class="rotate">'.$v.'</span>';
            }
        }
        return $new_str;
    }
    // 檢查公司資料是否重複
    static public function check_crm_repeat($column, $value, $crm_id=0){
        if($column == 'name'){ /*檢查公司名稱*/
            $replace_resault = self::replace_crm_name('name', $value);
            $crm_crm_repeat=D('crm_crm')->where($replace_resault['name_sql'].' = "'.$replace_resault['name_replace'].'" AND `name`!="" AND id!="'.$crm_id.'"')->count();
        }
        else{ /*檢查其他欄位*/
            $crm_crm_repeat=D('crm_crm')->where('`'.$column.'`="'.$value.'" AND `'.$column.'`!="" AND id!="'.$crm_id.'"')->count();//完全一樣
        }
        return $crm_crm_repeat;
    }
    // 公司名稱去掉固定內容(比對重複時使用)
    static public function replace_crm_name($column, $name_ori){
        $name_sql = "REPLACE(".$column.",' ','')";
        $im_importclient_replace = D('im_importclient_replace')->where('name="'.$column.'"')->find();
        if($im_importclient_replace){
            $im_importclient_replace = $im_importclient_replace['content'] ? explode(',', $im_importclient_replace['content']) : [];
            foreach ($im_importclient_replace as $value) {
                $name_sql = "REPLACE(".$name_sql.",'".$value."','')";

                $name_ori = str_replace($value, "", $name_ori);
            }
        }

        return ['name_sql'=>$name_sql, 'name_replace'=>$name_ori];
    }
    // 依客戶id取得連絡者
    static public function get_contacter($crmid){
        $crm_contact = D('crm_contact cc')->field('cc.*, eu.name as user_name')
                                        ->join('LEFT JOIN eip_user eu on eu.id=cc.eid')
                                        ->where("cc.cumid =".$crmid)
                                        ->order("cc.radio desc, cc.id desc")->select();
        foreach ($crm_contact as $key => $value) {
            if(checkDateisValid($value['birth'])){
                if(date('Y-m-d')>=$value['birth']){
                    $datetime1 = date_create(date('Y-m-d'));
                    $datetime2 = date_create($value['birth']);
                    $interval = date_diff($datetime1, $datetime2); 
                    $crm_contact[$key]['birthcount'] = ceil($interval->format('%a') / 365);
                }else{
                    $crm_contact[$key]['birthcount'] = 0;
                }
            }else{
                $crm_contact[$key]['birthcount'] = "";
            }
        }
        return $crm_contact;
    }
    // 以訪談紀錄取得聯繫對象
    static public function get_chat_contacter($crm_chats=null){
        $name = ""; $phone = ""; $mobile = ""; $mail = "";
        if($crm_chats){
            if($crm_chats['lxrid']>0){
                $crm_contact = D("crm_contact cc")->where("cc.id =".$crm_chats['lxrid'])->find();
                if($crm_contact){
                    $name = $crm_contact['cname'];
                    $phone = $crm_contact['phone'];
                    $mobile = $crm_contact['mobile'];
                    $mail = $crm_contact['mail'];
                }
            }else{
                $crm_crm = D('crm_crm')->where('id="'.$crm_chats['cumid'].'"')->find();
                if($crm_crm){
                    if(self::$system_parameter['負責人']==""){ /*如果負責人被隱藏，撈取公司資料*/
                        $name = CustoHelper::get_crm_show_name($crm_chats['cumid']);
                        $phone = $crm_crm['comphone'];
                        $mobile = $crm_crm['commobile'];
                        $mail = $crm_crm['commail'];
                    }else{ /*撈取負責人資料*/
                        $name = $crm_crm['bossname'];
                        $phone = $crm_crm['bossphone'];
                        $mobile = $crm_crm['bossmobile'];
                        $mail = $crm_crm['bossmail'];
                    }
                }
            }
        }
        $chat_contacter['name'] = $name;
        $chat_contacter['phone'] = $phone;
        $chat_contacter['mobile'] = $mobile;
        $chat_contacter['mail'] = $mail;

        return $chat_contacter;
    }
    // 計算等級數量
    static public function get_level_and_count($post_data=false, $join_query="", $where=""){
        $cid = session('cid');
        $levels = D()->query("SELECT `id`,`name` FROM crm_cum_level WHERE `cid`=$cid AND `status`=1 ORDER BY `id` ASC");
        $level_and_count_data['levels'] = $levels;
        
        $levels_count = [];
        $crm_count = 0;       /*預設某等級的計數*/
        $level_count_all = 0; /*預設全部等級的計數*/
        // 取得篩選客戶的sql語法
            if($where==""){ /*依客戶查詢*/
                unset($post_data['levelid']);
                $acc = Common::get_my_access(); /*取得我的權限*/
                 // dump($acc);
                $search_customer_result = CustoHelper::search_customer($acc, $post_data, 1);
                $where = $search_customer_result['all_where'];
            }
        foreach ($levels as $key => $value) {
            $levels_count[$key] = $value;
            // 計算各等級的客戶數
            $crm_count = D()->query("SELECT * FROM `crm_crm` ".
                                     $join_query."
                                     WHERE levelid=".$value['id']." AND ".$where."
                                     GROUP BY crm_crm.id");
            $levels_count[$key]['crm_count'] = $crm_count ? count($crm_count) : 0;
        }
        
        // 計算全部等級
            $level_count_all = D()->query("SELECT * FROM `crm_crm` ".
                                           $join_query." 
                                           WHERE ".$where."
                                           GROUP BY crm_crm.id");
        array_push($levels_count, [
            'id'        => 'all',
            'name'      => '全部',
            'crm_count' => $level_count_all ? count($level_count_all) : 0,
        ]);
        $level_and_count_data['levels_count'] = $levels_count;

        return $level_and_count_data;
    }
    // 取得crm詳細內容右側資料
    static public function get_crm_rightdata($get_data, $chats_page_count=-1){
        $return_data = [];

        if(!$get_data['id']){
            $get_data['id'] = self::$our_company_id;
        }
        /*檢查crm操作權限檢查*/
        // $crms = [ $get_data['id'] ];
        // $teamid = isset($get_data['teamid']) ? $get_data['teamid'] : 0;
        // $this->check_crm_access('crm', 'red', $crms, $teamid);

        $crm_cum_pri=D("crm_cum_pri")->where("status=1")->order("orders asc, id asc")->select();

        /*客戶資料*/
            $where_v2 = "crm_crm.id =".$get_data['id'] ;
            $newbier = D('crm_crm')->field('crm_crm.*, 
                                            crm_cum_type.name as type_name, 
                                            crm_cum_level.name as level_name,
                                            crm_cum_sourse.name as sourse_name')
                                    ->join("left join crm_cum_type on crm_cum_type.id= crm_crm.typeid")
                                    ->join("left join crm_cum_level on crm_cum_level.id= crm_crm.levelid")
                                    ->join("left join crm_cum_sourse on crm_cum_sourse.id= crm_crm.sourceid")
                                    ->where($where_v2)
                                    ->order("crm_crm.id desc")->find();
            if(!$newbier){ $newbier['typeid'] = 1; }
            $newbier['show_addr']= CustoHelper::get_crm_show_addr($newbier);
            if(self::$system_parameter["負責人"]==""){
                $newbier['show_contacter_name']= CustoHelper::get_crm_show_name($newbier['id']);
            }else{
                $newbier['show_contacter_name']= $newbier['bossname'];
            }

            if( !preg_match("/http/i", $newbier['url1']) && $newbier['url1'] != '')
                $newbier['url1']= 'http://'.$newbier['url1'];
            if(!preg_match("/http/i", $newbier['url2']) && $newbier['url2'] != '')
                $newbier['url2']= 'http://'.$newbier['url2'];
            if(checkDateisValid($newbier['bossbirth'])){
                if(date('Y-m-d')>=$newbier['bossbirth']){
                    $datetime1 = date_create(date('Y-m-d'));
                    $datetime2 = date_create($newbier['bossbirth']);
                    $interval = date_diff($datetime1, $datetime2); 
                    $newbier['bossbirthcount'] = ceil($interval->format('%a') / 365);
                }else{
                    $newbier['bossbirthcount'] = 0;
                }
            }else{
                $newbier['bossbirthcount'] = "";
            }
            if(checkDateisValid($newbier['hzrq'])){
                if(date('Y-m-d')>=$newbier['hzrq']){
                    $datetime1 = date_create(date('Y-m-d'));
                    $datetime2 = date_create($newbier['hzrq']);
                    $interval = date_diff($datetime1, $datetime2); 
                    $newbier['hzrqcount'] = ceil($interval->format('%a') / 365);
                }else{
                    $newbier['hzrqcount'] = 0;
                }
            }else{
                $newbier['hzrqcount'] = "";
            }

            /*處理沒資料時預設的協同人員*/
            foreach ($crm_cum_pri as $key => $value) {
                if(!isset($newbier[$value['ename']])){
                    $newbier[$value['ename']] = 0;
                }
            }
            
            $return_data['newbier'] = $newbier;

        //特性管理
            $fields_data = $newbier['fields_data'] ? (Array)json_decode($newbier['fields_data']) : [];  
            $crm_property = CustoHelper::get_fields_with_ans('crm_property', $fields_data);
            $return_data['crm_property'] = $crm_property;

        //聯絡人
            $crm_contact = self::get_contacter($get_data['id']);
            $crm_contact_top = $crm_contact ? array_values($crm_contact)[0]: ['cname'=>"", 'position'=>""];
            $return_data['crm_contact'] = $crm_contact;
            $return_data['crm_contact_top'] = $crm_contact_top;
        
        //訪談紀錄
            if($chats_page_count>=0){
                $chats_return_data = self::get_chats($get_data['id'], $page=0, $search_text="", $chats_page_count);
                $return_data['crm_chats'] = $chats_return_data['crm_chats'];
                $return_data['crm_chats_pages'] = $chats_return_data['crm_chats_pages'];
            }else{
                $return_data['crm_chats'] = [];
                $return_data['crm_chats_pages'] = [];
            }
        
        //小事
            $smallthings_all = self::get_smallthings($get_data)['smallthings'];
            $smallthings = [];
            $smallthings_done = [];
            foreach ($smallthings_all as $value) {
                if($value['doevt']==0){
                    array_push($smallthings, $value);
                }else{
                    array_push($smallthings_done, $value);
                }
            }
            $return_data['smallthings'] = $smallthings;
            $return_data['smallthings_done'] = $smallthings_done;

        //備註
            $crm_memo=D('crm_memo cmo')->field('cmo.*, eu.name as user_name')
                                    ->join("LEFT JOIN eip_user eu on eu.id= cmo.eid")
                                    ->where("cmo.cumid =".$get_data['id'])
                                    ->order("cmo.dateline desc")
                                    ->select();
            $return_data['crm_memo'] = $crm_memo;

        //網群
            $crm_website = M("crm_website cw")->field('cw.*, eu.name as user_name')
                                            ->join("LEFT JOIN eip_user eu on eu.id= cw.eid")
                                            ->where("cw.cumid =".$get_data['id'])
                                            ->order('id desc')->select();
            $return_data['crm_website'] = $crm_website;

        //協同人員
            foreach ($crm_cum_pri as $key => $value) {
                $user_id = $return_data['newbier'][$value['ename']]; 
                $user = D('eip_user')->where('is_job=1 && id!='.self::$top_adminid)->find($user_id);
                $crm_cum_pri[$key]['user_name'] = $user ? $user['name'] : '無';
                $crm_cum_pri[$key]['user_id'] = $user ? $user['id'] : '0';
            }
            $return_data['crm_cum_pri'] = $crm_cum_pri;

        return $return_data;
    }
    // 取得預約、對話資料
    static public function get_conversation($post_data, $countOfPage=0){
        $return_data = [];
        
        //dump($post_data);
        $search_level = ($post_data['levelid'] == 'all' || $post_data['levelid'] == '') ? ' and true' : ' and levelid ='.$post_data['levelid'];
        // dump($search_level);

        $daoModel = "Custo_all";
        $acc=D('access')->field($daoModel)->where('id='.session('accessId'))->select()[0];

        $order_sql = "cc.appmdate asc, cc.id asc";

        if(isset($post_data['conversation_type']) ){
            $conversation_type = $post_data['conversation_type'];
            $_GET['date']=time();
            $time=date("Y-m-d");
            if(!isset($_GET['qulid']))
                $_GET['qulid']="%";
            $start=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date']), date("Y",$_GET['date']));
            $end=mktime(0, 0,0,date("m",$_GET['date']),date("d",$_GET['date'])-date("w",$_GET['date'])+6, date("Y",$_GET['date']));
            
            $str_time_today = date("Y-m-d").' 00:00:00';
            $str_time_next_day = date("Y-m-d").' 00:00:00 +1 days';

            $check_latest_chat = "INNER JOIN (
                                    SELECT MAX(id) as id, MAX(dateline) as `dateline` 
                                    FROM `crm_chats` 
                                    WHERE eid=".session('eid')."
                                    GROUP BY cumid 
                                ) a ON a.id = cc.id"; /*比對 你 最新的訪談紀錄*/
            if($conversation_type=="<"){    //預約逾期
                $sqldate="cc.appmdate >= ".strtotime($str_time_today.'-3 years')." and cc.appmdate < ".strtotime($str_time_today); //只顯示3年內的逾期
            }
            else if($conversation_type==">"){ // 預約預約
                $sqldate="cc.appmdate >= ".strtotime($str_time_next_day);
            }
            else if($conversation_type=="="){ // 預約今天
                $sqldate="cc.appmdate >= ".strtotime($str_time_today)." and cc.appmdate < ".strtotime($str_time_next_day);
            }
            else if($conversation_type=="=7"){ // 對話(今日)
                $sqldate="FROM_UNIXTIME(cc.dateline) like '".$time."%'";
                $check_latest_chat = "INNER JOIN (
                    SELECT MAX(id) as id, MAX(dateline) as `dateline` 
                    FROM `crm_chats` 
                    WHERE FROM_UNIXTIME(dateline) like '".$time."%' AND eid=".session('eid')."
                    GROUP BY cumid 
                ) a ON a.id = cc.id"; /*只抓今日最後一筆訪談紀錄*/
            }
            else if( substr($conversation_type, 0, 1)=="=" ){ // 對話(指定日期)
                $sqldate="FROM_UNIXTIME(cc.dateline) like '".substr($conversation_type, 1)."%'";
                $check_latest_chat = "INNER JOIN (
                    SELECT MAX(id) as id, MAX(dateline) as `dateline` 
                    FROM `crm_chats` 
                    WHERE FROM_UNIXTIME(dateline) like '".substr($conversation_type, 1)."%' AND eid=".session('eid')."
                    GROUP BY cumid 
                ) a ON a.id = cc.id"; /*只抓指定日期最後一筆訪談紀錄*/
            }

            // if( in_array($conversation_type, ["<", ">", "="]) ){ //如果是 預約逾期、預約預約、預約今天，才需要判斷屬於公司的協同人員
            //  if(session('eid')!="32" && $acc['custo_all'] == 0){
            //      $sqldate .= " and ( false  
            //              or crm_crm.did = '".session('eid')."' 
            //              or crm_crm.wid = '".session('eid')."' 
            //              or crm_crm.sid = '".session('eid')."' 
            //              or crm_crm.hid1 = '".session('eid')."' 
            //              or crm_crm.hid2 = '".session('eid')."' 
            //              or crm_crm.hid3 = '".session('eid')."'
            //              ) AND cc.eid = '".session('eid')."'
            //          ";
            //  }
            // }

            $cum_list = D('crm_chats cc')->field('
                                    crm_cum_type.name as type_name,
                                    crm_cum_level.name as level_name,
                                    cc.*, cc.id as chat_id, FROM_UNIXTIME(cc.dateline) as dateline_format, FROM_UNIXTIME(cc.appmdate) as appmdate_format, FROM_UNIXTIME(cc.do_time) as do_time_format,
                                    eu.name as user_name, eu2.name as douser_name, 
                                    cco.cname, 
                                    cq.name as cq_name,
                                    crm_crm.id as id, crm_crm.name as crm_name, crm_crm.nick as crm_nick, crm_crm.typeid, crm_crm.bossname');
            if($check_latest_chat){
                $cum_list = $cum_list->join($check_latest_chat);
            }
            $cum_list = $cum_list->join("RIGHT join crm_crm on crm_crm.id= cc.cumid") /*只撈取客戶存在的訪談*/
                                    ->join("LEFT join crm_cum_type on crm_cum_type.id= crm_crm.typeid")
                                    ->join("LEFT join crm_cum_level on crm_cum_level.id= crm_crm.levelid")
                                    ->join("LEFT join eip_user eu on eu.id= cc.eid")
                                    ->join("LEFT join eip_user eu2 on eu2.id= cc.doid")
                                    ->join("LEFT join crm_contact cco on cco.id= cc.lxrid")
                                    ->join("LEFT join crm_chatqulity cq on cq.id= cc.qulid")
                                    ->where('('.$sqldate.') '.$search_level)
                                    ->order($order_sql)
                                    ->group('cc.cumid')
                                    ->select();
            // dump('('.$sqldate.') '.$search_level);exit;
            // dump($cum_list);exit;

            // 處理分頁
            if($countOfPage>0){
                $totalPages = ceil(count($cum_list) / $countOfPage);
                $return_data['totalPages'] = $totalPages==0 ? 1 : $totalPages;
                $startIndex = $post_data['p']-1>0 ? ($post_data['p']-1)*$countOfPage : 0;
                $cum_list = array_slice($cum_list, $startIndex, $countOfPage);
            }else{
                $return_data['totalPages'] = 1;
            }

            foreach ($cum_list as $key => $value) {
                $cum_list[$key]['show_name'] = CustoHelper::get_crm_show_name($value['id']);
                $$cum_list[$key]['contacter'] = $value['lxrid'] == 0 ? $value["bossname"] : $contact['cname'];
            }
            $return_data["cum_list"] = $cum_list;

                
            // 計算各等級的客戶數
            $join_query = "LEFT JOIN crm_chats cc on cc.cumid=crm_crm.id ".$check_latest_chat;
            $level_and_count_data = self::get_level_and_count($post_data, $join_query, $sqldate);
            $return_data['levels'] = $level_and_count_data['levels'];
            $return_data['levels_count'] = $level_and_count_data['levels_count'];
        }
        return $return_data;
    }
    // 取得小事件
    static public function get_smallthings($post_data, $countOfPage=0) {
        if (array_key_exists('page', $post_data) == true && $post_data['page'] == 'index') {
            // I('post.') == $_POST
            $request_post = I('post.');
    
            $query = D('crm_chats cc');

            /*首頁僅處理未處理的小事*/
            $order_arr = [
                'cc.dateline' => 'asc',
                'cc.id' => 'asc'
            ];

            $field_arr = [
                'cc.dateline',
                'cc.content',
                'cc.cumid AS id',
                'cc.id AS chat_id',
                'eu.name AS douser_name',
                'crm_crm.name AS crm_full_name',
                'crm_crm.nick AS crm_nick_name',
            ];

            $allwhere = [
                'FALSE',
                "crm_crm.id = '" . self::$our_company_id . "'"
            ];
            
            $crm_cum_pri = D('crm_cum_pri')->where('status', 1)->select();
            $enames = array_column($crm_cum_pri, 'ename');
            $allwhere[] = implode(' OR ', array_map(function($ename) {
                return "crm_crm.{$ename} IN (" . session('childeid') . ")";
            }, $enames));
            
            $where_arr = [
                'cc.smevt = 1',
                'cc.doevt = 0',
                "(cc.do_time = '' || cc.do_time is null)",
            ];
            // dump($where_arr);
            $smallthings = $query->join('crm_crm ON crm_crm.id = cc.cumid', 'LEFT')
                                 ->join('eip_user eu ON eu.id= cc.doid', 'LEFT')
                                 ->field($field_arr)
                                 ->where($where_arr)
                                 ->order($order_arr)
                                 ->select();

            // 處理分頁
            /*是否須按分頁顯示*/
            if ($countOfPage == 0) {
                $return_data['totalPages'] = 1;
            } else {
                $totalPages = ceil(count($smallthings) / $countOfPage);
                $return_data['totalPages'] = $totalPages == 0 ? 1 : $totalPages;

                $startIndex = $request_post['p'] <= 1 ? 0 : ($request_post['p'] - 1) * $countOfPage;
                $smallthings = array_slice($smallthings, $startIndex, $countOfPage);
            }

            $return_data['smallthings'] = array_map(function($smallthing) {
                $smallthing['dateline_format'] = date('Y-m-d H:i', $smallthing['dateline']);

                $smallthing['delay'] = date('Y-m-d', $smallthing['dateline']) < date('Y-m-d');

                if (empty($smallthing['crm_nick_name']) == false) {
                    $smallthing['show_name'] = $smallthing['crm_nick_name'];
                } else {
                    $smallthing['show_name'] = mb_substr($smallthing['crm_full_name'], 0, 5);
                }

                return $smallthing;
            }, $smallthings);

            return $return_data;
        } else {
            $where = "cc.smevt=1";

            if(isset($post_data['doevt'])){
                if($post_data['doevt']=='1'){ /*看已處理*/
                    $doevt_where = " AND cc.doevt=1";
                    $order_query = "cc.dateline desc, cc.id desc"; /*越近的越前*/
                }else{
                    $doevt_where = " AND cc.doevt=0"; /*看未處理*/
                    $order_query = "cc.dateline asc, cc.id asc"; /*越近的越後*/
                }
            }else{ /*看全部*/
                $doevt_where = "";
                $order_query = "cc.dateline asc, cc.id asc"; /*越近的越後*/
            }
            $where .= $doevt_where;

            $where_count_level = $where;
            if($_POST['levelid']!='all' && $_POST['levelid']!=''){
                $where .= ' AND crm_crm.levelid="'.$_POST['levelid'].'"';
            }

            if($post_data['id']){ /*以客戶id查詢*/
                $where .= ' AND cc.cumid="'.$post_data['id'].'"';
                $where_count_level.= ' AND cc.cumid="'.$post_data['id'].'"';
            }
            else{
                if($post_data['doid']=="mycrm"){ /*看屬於自己客戶的小事*/
                    $acc = Common::get_my_access(); /*取得我的權限*/
                    $return_data = CustoHelper::get_search_query($acc, $post_data, 1);
                    $crm_where = $return_data['all_where'];
                    if(!$crm_where){ 
                        $crm_where = 'false';
                    }
                    $search_user_id = session('eid');
                }
                else if($post_data['doid']){ /*看自己處理的小事*/
                    $crm_where = 'false';
                    $search_user_id = $post_data['doid'];
                }
                $where .= ' AND ( ('.$crm_where.') OR (cc.doid="'.$search_user_id.'" AND cc.doevt=0) OR (cc.doevt=1 AND cc.do_review_time is null AND cc.eid="'.$search_user_id.'"))';
                $where_count_level .= ' AND ( ('.$crm_where.') OR (cc.doid="'.$search_user_id.'" AND cc.doevt=0) OR (cc.doevt=1 AND cc.do_review_time is null AND cc.eid="'.$search_user_id.'"))';
            }
            // dump($where);exit;

            $smallthings = D('crm_chats cc')
                                ->field('
                                    crm_cum_type.name as type_name,
                                    crm_cum_level.name as level_name,
                                    cc.*, cc.id as chat_id, FROM_UNIXTIME(cc.dateline) as dateline_format, FROM_UNIXTIME(cc.appmdate) as appmdate_format, FROM_UNIXTIME(cc.do_time) as do_time_format,
                                    eu.name as user_name, eu2.name as douser_name, 
                                    cco.cname, 
                                    cq.name as cq_name,
                                    crm_crm.id as id, crm_crm.name as crm_name, crm_crm.nick as crm_nick, crm_crm.typeid, crm_crm.bossname')
                                ->join("left join crm_crm on crm_crm.id= cc.cumid")
                                ->join("left join crm_cum_type on crm_cum_type.id= crm_crm.typeid")
                                ->join("left join crm_cum_level on crm_cum_level.id= crm_crm.levelid")
                                ->join("left join eip_user eu on eu.id= cc.eid")
                                ->join("left join eip_user eu2 on eu2.id= cc.doid")
                                ->join("left join crm_contact cco on cco.id= cc.lxrid")
                                ->join("left join crm_chatqulity cq on cq.id= cc.qulid")
                                ->where($where)
                                ->order($order_query)
                                ->select();
            // 處理分頁
            if($countOfPage>0){ /*是否須按分頁顯示*/
                $totalPages = ceil(count($smallthings) / $countOfPage);
                $return_data['totalPages'] = $totalPages==0 ? 1 : $totalPages;
                
                $startIndex = $_POST['p']-1>0 ? ($_POST['p']-1)*$countOfPage : 0;
                $smallthings = array_slice($smallthings, $startIndex, $countOfPage);
            }else{
                $return_data['totalPages'] = 1;
            }

            foreach ($smallthings as $key => $value) {
                $smallthings[$key]['delay'] = date('Y-m-d', $value['dateline']) < date('Y-m-d');
                $smallthings[$key]['show_name'] = CustoHelper::get_crm_show_name($value['id']);
            }
            $return_data['smallthings'] = $smallthings;

            $join_query = "LEFT JOIN `crm_chats` as cc ON cc.`cumid`=`crm_crm`.`id`";
            $level_and_count_data = self::get_level_and_count($post_data, $join_query, $where_count_level);
            $return_data['levels'] = $level_and_count_data['levels'];
            $return_data['levels_count'] = $level_and_count_data['levels_count'];

            return $return_data;
        }
    }
    // 取得訪談紀錄
    static public function get_chats($crmid, $page=0, $search_text="", $countOfPage=0){
        $where = "cc.cumid ='".$crmid."' AND (
            cc.content LIKE '%".$search_text."%' OR 
            cc.do_response LIKE '%".$search_text."%' 
        )";
        $crm_chats = D('crm_chats cc')
                        ->field('cc.*, cc.id AS chat_id, 
                                 FROM_UNIXTIME(cc.dateline) AS dateline_format, 
                                 FROM_UNIXTIME(cc.appmdate) AS appmdate_format, 
                                 FROM_UNIXTIME(cc.do_time) AS do_time_format,
                                 eu.name AS user_name, eu2.name AS douser_name, 
                                 cco.cname, 
                                 cq.name AS cq_name')
                        ->join("left join eip_user eu on eu.id= cc.eid")
                        ->join("left join eip_user eu2 on eu2.id= cc.doid")
                        ->join("left join crm_contact cco on cco.id= cc.lxrid")
                        ->join("left join crm_chatqulity cq on cq.id= cc.qulid")
                        ->where($where)
                        ->order("cc.dateline desc")->select();
        // 處理分頁
        if($countOfPage>0){ /*是否須按分頁顯示*/
            $totalPages = ceil(count($crm_chats) / $countOfPage);
            $chats_pages = [];
            for($i = 1;$i <= $totalPages;$i++){
                $chats_pages[$i - 1] = $i;
            }
            $return_data['crm_chats_pages'] = $chats_pages;

            $startIndex = $page-1>0 ? ($page-1)*$countOfPage : 0;
            $crm_chats = array_slice($crm_chats, $startIndex, $countOfPage);
        }else{
            $return_data['crm_chats_pages'] = [1];
        }

        /*判斷可否編輯*/
        foreach($crm_chats as $key => $vo){
            // $crm_chats[$key]['content'] = str_replace("\n", "<br>", $crm_chats[$key]['content']);
            if($vo['dateline'] >= strtotime(date("Ymd")) && $vo['dateline'] <= (strtotime(date("Ymd")) + 86400)){
                $crm_chats[$key]['save'] = 1;
            }else{
                $crm_chats[$key]['save'] = 0;
            }
        }
        $return_data['crm_chats'] = $crm_chats;

        return $return_data;
    }
}