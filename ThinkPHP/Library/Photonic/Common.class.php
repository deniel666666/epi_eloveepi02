<?php
/***
 * Edcode
 * 對密碼加密類
 *
 * 對密碼加密閉免被有心人盜取
 *
 ***/
namespace Photonic;
use Think\Controller;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class Common extends Controller
{
    function _initialize(){
    }

    public static function instance(){
        return new Common();
    }

    static public function get_lang_menu(){
        $lang_id = D('system_parameter')->where('id=1')->find()['data'];
        $filename = $_SERVER['DOCUMENT_ROOT'].'/lang/'.$lang_id.'/system_parameter.json';
        if(!file_exists($filename)){
            dump('語言版檔案不存在:'.$filename);exit;
        }
        $content = file_get_contents($filename);
        $lang_menu = $content ? json_decode($content, true) : [];
        return $lang_menu;
    }

    static public function getArgs($name){
        if (empty($name) == true) {
            throw new \Exception('参数不能为空！');
        } else {
            if (isset($_REQUEST[$name]) == false) {
                return null;
            }else{
                return addslashes(trim($_REQUEST[$name]));
            }
        }
    }

    //求月份最後一天
    static public function getCurMonthLastDay($date) {
        return date('Y-m-d', strtotime(date('Y-m-01', strtotime($date)) . ' +1 month -1 day'));
    }

    // 自動更改排序
    public function auto_change_orders($table, $column, $order_num, $primary_key, $primary_value, $filter_where=false){
        if(!$table) $this->error('操作失敗：請提供更改的資料表');
        if(!$column) $this->error('操作失敗：請提供更改的欄位');
        if(!$primary_key) $this->error('操作失敗：請提供改目標資料表的主鍵');
        if(!$primary_value) $this->error('操作失敗：請提供改目標資料表的主鍵值');

        $order_num = $order_num ? $order_num : 0;// 未提供排序，預設為0

        $filter_where = $filter_where !== false ? $filter_where : 'true = true';

        // 利用篩選條件檢查此次設定的排序 是否已被設定 且是 別人
        $order_num_isset =D($table)->where($filter_where.' and '.$column.' = '.$order_num.' and '.$primary_key.' != '.$primary_value)->select();
        if(count($order_num_isset)>0){
            // 被設定走了，開始自動修改排序

            // 利用篩選條件找出要一起檢查排序的資料
            $rows = D($table)->where($filter_where)->select();
            foreach ($rows as $key => $value) {
                // 如果該資料排序等於或大於此次設定的排序
                if($value[$column] >= $order_num){
                    $new_order = $value[$column]+1; // 排序自動+1
                    D($table)->where($primary_key.' = '.$value[$primary_key])->data([ $column=>$new_order ])->save();
                }
            }
        }

        // 根據目標篩選，修改目標資料排序
        D($table)->where($primary_key.' = '.$primary_value)->data([ $column=>$order_num ])->save();
    }

    /*上傳base64檔案*/
    static public function uploadFile($path, $file, $fileName = ""){
        /*取得檔案附檔類型*/
        $getType = explode(";", $file)[0];
        $getType = explode(":", $getType)[1];
        // dump($getType);exit;
        if(in_array($getType, ['text/plain'])){
            $getType = 'txt';
        }
        else if(in_array($getType, ['application/octet-stream'])){
            $getType = 'docx';
        }
        else if(in_array($getType, ['application/vnd.openxmlformats-officedocument.presentationml.presentation'])){
            $getType = 'pptx';
        }
        else if(in_array($getType, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])){
            $getType = 'xlsx';
        }
        else{
            $getType = explode("/", $getType)[1];
        }

        /*設定檔案名稱*/
        if (!$fileName) {
            /*無給定*/
            $t = time();
            $gethash = self::geraHash(8);
            $fileName = $t . $gethash . '.' . $getType;
        } else {
            /*有給定*/
            $fileName = $fileName . '.' . $getType;
        }

        /*上傳絕對目錄路徑*/
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $path . '/' . $fileName;
        // dump($filePath);exit();

        /*處理檔案、上傳主機*/
        $fileData = substr($file, strpos($file, ",") + 1);
        $decodedData = base64_decode($fileData);
        file_put_contents($filePath, $decodedData);

        /*回傳檔案相對路徑*/
        return $path . '/' . $fileName.'?'.rand(0, 10000000000);
    }
    
    /*回傳指定長度亂數*/
    static public function geraHash($qtd){
        $Caracteres = 'ABCDEFGHIJKLMOPQRSTUVXWYZ0123456789';
        $QuantidadeCaracteres = strlen($Caracteres);
        $QuantidadeCaracteres--;
        $Hash = null;
        for ($x = 1; $x <= $qtd; $x++) {
            $Posicao = rand(0, $QuantidadeCaracteres);
            $Hash .= substr($Caracteres, $Posicao, 1);
        }
        return $Hash;
    }

    /*取得自己的權限*/
    static public function get_my_access($user_id=0){
        $user_id = $user_id ? $user_id : session('eid');
        $user = D('eip_user')->where('id="'.$user_id.'" and is_job=1')->find();
        if(!$user){ return []; }
        
        $acc = self::get_access_by_access_id($user['usergroupid']);
        return $acc;
    }
    static public function get_access_by_access_id($access_id){
        $acc = D('access')->where('id='.$access_id)->select()[0];
        if(!$acc){ return []; }

        /*處理KM權限*/
        $km_types = D('km_types')->where("status=1")->select(); /*KM選單*/
        if($user['usergroupid']==self::$admin_access_id){
            foreach ($km_types as $key => $value) {
                $acc[strtolower($value['codenamed']).'_new'] = 1;
                $acc[strtolower($value['codenamed']).'_red'] = 1;
                $acc[strtolower($value['codenamed']).'_edi'] = 1;
                $acc[strtolower($value['codenamed']).'_hid'] = 1;
                $acc[strtolower($value['codenamed']).'_del'] = 1;
                $acc[strtolower($value['codenamed']).'_all'] = 1;
            }
        }
        else{
            foreach ($km_types as $key => $value) {
                $acc[strtolower($value['codenamed']).'_new'] = 0;
                $acc[strtolower($value['codenamed']).'_red'] = 0;
                $acc[strtolower($value['codenamed']).'_edi'] = 0;
                $acc[strtolower($value['codenamed']).'_hid'] = 0;
                $acc[strtolower($value['codenamed']).'_del'] = 0;
                $acc[strtolower($value['codenamed']).'_all'] = 0;
            }
            $km_access = $acc['km_access'] ? json_decode($acc['km_access'], true) : []; 
            $km_access = $km_access ? $km_access : [];
            foreach ($km_access as $key => $value) {
                $acc[$key] = $value;
            }
        }

        return $acc;
    }

    /*發送請求*/
    static public function http_request($url, $data = null){
        $curl = curl_init();  
        curl_setopt($curl, CURLOPT_URL, $url);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (! empty($data)) {  
            curl_setopt($curl, CURLOPT_POST, 1);  
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  
        }  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        if($output === false){
            dump(curl_error($curl));
        }

        curl_close($curl);  
        return $output;  
    }

    static public function index_set($table, $where="true",$word="name",$rname=true,$order=NULL){
        // var_dump($order);
        if($order){
            $result=D($table)->where('status=1 and '.$where)->order($order);
        }else{
            $result=D($table)->where('status=1 and '.$where);
        }
        if($rname==true){
            if($word){
                $result = $result->field('*, '.$word.' as rname');
            }
            $result = $result->index('id');
        }
        $result = $result->select();

        if($table == 'eip_user'){
            $un['id']='0';
            $un['name']='無';
            $un['is_job']='1';
            $un['rname']= $un[$word] ?? '';
            if($rname==true){
                $result[0] = $un;
            }else{
                array_unshift($result, $un);
            }
        }

        return $result;
    }

    /*新增操作紀錄*/
    static public function error_log($log){
        $data->create_time=time();
        $data->function=CONTROLLER_NAME.'/'.ACTION_NAME;
        $data->log=$log;
        $data->eid=session('eid');
        $data->ip=self::get_clinet_ip();
        D('error_log')->data($data)->add();
    }
    static public function get_clinet_ip(){
        $ips = [];
        if (!empty($_SERVER["HTTP_CLIENT_IP"])){
            array_push($ips,$_SERVER["HTTP_CLIENT_IP"]);
        }else{
            if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
                array_push($ips,$_SERVER["HTTP_X_FORWARDED_FOR"]);
            }else{
                if(!empty($_SERVER["HTTP_X_FORWARDED"])){
                    array_push($ips,$_SERVER["HTTP_X_FORWARDED"]);
                }else{
                    if(!empty($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])){
                        array_push($ips,$_SERVER["HTTP_X_CLUSTER_CLIENT_IP"]);
                    }else{
                        if(!empty($_SERVER["HTTP_FORWARDED_FOR"])){
                            array_push($ips,$_SERVER["HTTP_FORWARDED_FOR"]);
                        }else{
                            if(!empty($_SERVER["HTTP_FORWARDED"])){
                                array_push($ips,$_SERVER["HTTP_FORWARDED"]);
                            }else{
                                if(!empty($_SERVER["REMOTE_ADDR"])){ /*(真實 IP 或是 Proxy IP)*/
                                    array_push($ips,$_SERVER["REMOTE_ADDR"]);
                                }else{
                                    if(!empty($_SERVER["HTTP_VIA"])){ /*(參考經過的 Proxy)*/
                                        array_push($ips,$_SERVER["HTTP_VIA"]);
                                    }else{
                                        array_push($ips,'');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $ip = join('/', $ips);
        return $ip;
    }

    /*取得可見選單(依人員)*/
    static public function get_can_see_menu($user_id=0, $read_count=false){
        // dump($read_count);exit;
        $acc = self::get_my_access($user_id);
        // dump($acc);exit;

        $read_ck = []; $dis_id = [];
        $file_share_num = 0; //共用文件未讀
        $publish_count = 0; //你需要發布的事件
        $getlist_ck=0;//輪到你可執行的事件
        $runing_ck=0;//輪到你可執行的事件
        if($read_count){
            // 找出未讀文章
                // 處理共用文件
                $file_share = self::get_child_file($fileNumber="FI", -1);
                $read_ck = array_merge($read_ck, array_filter($file_share, function($v, $k){
                    return $v['read_ck']==1;
                }, ARRAY_FILTER_USE_BOTH));
                foreach ($file_share as $k_t => $v_t) {
                    $file_share_num += self::not_read_check($v_t);
                }

                /*第一階層*/
                $file1 = self::get_child_file($fileNumber="", 0);
                $read_ck = array_merge($read_ck, array_filter($file1, function($v, $k) {
                    return $v['read_ck']==1;
                }, ARRAY_FILTER_USE_BOTH));
                $parent_ids_layer_1 = [];
                foreach ($file1 as $k_t => $v_t) {
                    if($v_t['file_layer']!=0){ /*是階層*/
                        array_push($parent_ids_layer_1, $v_t['id']);
                    }
                }
                /*第二階層*/
                $file2 = self::get_child_file($fileNumber="", -1, $parent_ids_layer_1);
                $parent_ids_layer_2 = [];
                foreach ($file2 as $k_s => $v_s) {
                    if($v_s['file_layer']!=0){ /*是階層*/
                        array_push($parent_ids_layer_2, $v_s['id']);
                    }
                }
                $read_ck = array_merge($read_ck, array_filter($file2, function($v, $k) {
                    return $v['read_ck']==1;
                }, ARRAY_FILTER_USE_BOTH));
                /*第三階層*/
                $file3 = self::get_child_file($fileNumber="", -1, $parent_ids_layer_2);
                $read_ck = array_merge($read_ck, array_filter($file3, function($v, $k) {
                    return $v['read_ck']==1;
                }, ARRAY_FILTER_USE_BOTH));
        }

        /*找出選單階層數*/
        $powercat_level = D('powercat')->field('level')->group('level')->order('level desc')->select();
        $menu_childs = [];
        foreach ($powercat_level as $level) {
            $powercat = D('powercat')->where('`level`='.$level['level'].' && `status`=1')->order('orders asc')->select();
            /*併入KM選單*/
                $km_types = D('km_types kt')->field('kt.*')
                                ->join("JOIN powercat on powercat.id=kt.parent_id")
                                ->where('kt.`status`=1 AND powercat.`level`='.($level['level']-1)) /*掛於當前階層選單的前一階*/
                                ->order('kt.orders asc, kt.id desc')->select();
                foreach ($km_types as $k_km_type => $v_km_type) {
                    $km_types[$k_km_type]['type'] = 'km';
                    $km_types[$k_km_type]['id'] = 'km_'.$v_km_type['id'];
                    $km_types[$k_km_type]['link'] = U('km/index', ['type'=>$v_km_type['description']]);
                }
                $powercat = array_merge($powercat, $km_types);
                usort($powercat, 'sort_by_orders');
            // dump($powercat);
            foreach ($powercat as $key => $vo) {
                /*檢查瀏覽權限*/
                if($acc['id']==self::$admin_access_id){ /*如果使用的是管理員權限，一率全看*/
                }
                else if( $vo['type']=='km' ){ /*KM管理的選單*/
                    if( isset($acc[strtolower($vo['codenamed'])."_red"]) ){ /*系統有定義指定瀏覽的權限*/
                        if($acc[strtolower($vo['codenamed'])."_red"]!='1'){ /*指定瀏覽權限沒勾選*/
                            continue; /*略過選單*/
                        }
                    }else{ /*視為未開權限*/
                        continue; /*略過選單*/
                    }
                }
                else if( $vo['id']==158){
                    $bind_codenamed = 'Custo'; /*供應商列表(綁定權限於客戶列表)*/
                    if( isset($acc[strtolower($bind_codenamed)."_red"]) ){ /*系統有定義指定瀏覽的權限*/
                        if($acc[strtolower($bind_codenamed)."_red"]!='1'){ /*指定瀏覽權限沒勾選*/
                            continue; /*略過選單*/
                        }
                    }
                }
                else{ /*一般選單*/
                    if( isset($acc[strtolower($vo['codenamed'])."_red"]) ){ /*系統有定義指定瀏覽的權限*/
                        if($acc[strtolower($vo['codenamed'])."_red"]!='1'){ /*指定瀏覽權限沒勾選*/
                            continue; /*略過選單*/
                        }
                    }
                }

                $vo['read_ck'] = 0;
                if($read_count){
                    //文章未讀計數
                    if($vo['description']){ /*有設定描述(用於紀錄文章編號)*/
                        foreach($read_ck as $key2 =>$vo2){
                            if( strtolower($vo['description']) == strtolower($vo2['number']) ){
                                $vo['read_ck']++;
                            }
                        }
                    }

                    //事件簿需處理計數
                    if($vo['title'] == "事件簿"){
                        // 待發佈
                        $publish_count=D('eve_events')->where("eid='".session('eid')."' and result IN(-1,0,1) and step_flow='0' ")->count();
                        $vo['read_ck']=$publish_count;

                        // 找出需處理的事件的步驟
                        $dis_id=D('eve_steps')->where("user_id='".session('adminId')."' AND status!=1")->select();//檢查事件簿
                        // dump($dis_id);exit;
                        $eve_ids = [0];
                        foreach ($dis_id as $key => $value) {
                            array_push($eve_ids, $value['eve_id']);
                        }
                        $eve_events=D('eve_events')->where("id IN (".implode(',', $eve_ids).") AND result NOT IN(4,6,7,8,9,10)")->index('id')->select();//檢查事件簿
                        // dump($eve_events);exit;
                        foreach($dis_id as $key2 =>$vo2){
                            if(isset($eve_events[$vo2['eve_id']])){
                                $eve_event = $eve_events[$vo2['eve_id']];
                                if($vo2['is_make'] != 1 && $vo2['orders'] == $eve_event['step_flow']){
                                    $vo['read_ck']++;
                                    $getlist_ck++;//輪到你可執行的事件
                                }
                                if(($vo2['step_id'] == 3 || $vo2['step_id'] == 5 ) && $vo2['is_make'] == 1 && $vo2['orders']==$eve_event['step_flow']){
                                    $vo['read_ck']++;
                                    $runing_ck++;//驗收未過修改中
                                }
                            }
                        }
                        // exit;
                    }
                }

                /*調整內容*/
                if(isset($menu_childs[$vo['id']])){ /*有紀錄自己的子選單*/
                    $vo['sub_menu'] = $menu_childs[$vo['id']];
                    $vo['has_sub_menu'] = 1;
                }else{
                    $vo['sub_menu'] = [];
                    $vo['has_sub_menu'] = 0;
                }
                if($vo['link']){ /*有指定連結*/
                    $vo['url'] = $vo['link'];
                }else if($vo['codenamed']!=''){ /*有站內連結*/
                    $vo['url'] = '/index.php/'.$vo['codenamed'].'/index'; /*處理連結*/
                }else if($vo['has_sub_menu']){ /*有子選單*/
                    $vo['url'] = $vo['sub_menu'][0]['url'];
                }else{
                    $vo['url'] = '###';
                }
                

                if(!isset($menu_childs[$vo['parent_id']])){ $menu_childs[$vo['parent_id']] = []; }
                if( $vo['url'] != '###' ){ /*有設定連結(可能是指定連結、外聯、子選單連結)*/
                    array_push($menu_childs[$vo['parent_id']], $vo);
                }
            }
        }
        // dump($menu_childs);exit;
        $menu = $menu_childs[0];
        // dump($menu);

        $list = self::merge_sub_menu_to_list($menu); /*非樹狀的可視選單*/
        // dump($list);exit;

        return [
            'arranged'=>$menu, 'list'=>$list, 
            'file_share_num'=>$file_share_num,
            'publish_count'=>$publish_count, 'getlist_ck'=>$getlist_ck, 'runing_ck'=>$runing_ck
        ];
    }
    /*取得子階層文章*/
    static public function get_child_file($fileNumber="", $parent_id=-1, $parent_ids=[]){
        // $acc=D('access')->field($daoModel)->where('id='.session('accessId'))->select()[0];
        if($_SESSION['eid'] != self::$top_adminid){
            $access = " AND (
                                f.creater = '".$_SESSION['eid']."' OR 
                                f.access_type = 'all' OR (
                                    f.access_type = 'on' AND 
                                    f.apart like '%\"".$_SESSION['apartId']."\"%') OR (
                                        (
                                            f.access_type = 'on' OR 
                                            f.access_type = 'own'
                                        ) AND 
                                        f.access like '%\"".$_SESSION['eid']."\"%'
                                    )
                                )";
        }
        else{
            $access = " AND true";
        }

        $where = $fileNumber ? "f.number = '".$fileNumber."' AND " : "";
        $where .= "f.showtime!='stop' AND 
                   f.status = '1' AND 
                   f.start_time <= ".time()." AND 
                   f.end_time >=".time()." ".$access;
        if($fileNumber=='FI'){ /*是我的文件(含共用文件)*/
            if($parent_id==-1){ /*不以階層查看*/
                $where .= " AND f.creater!='".$_SESSION['adminId']."'"; /*看分享的*/
            }else{
                $where .= " AND f.creater='".$_SESSION['adminId']."'"; /*看自己的*/
            }
        }
        if($parent_id!=-1){
            $where .= " AND f.parent_id='".$parent_id."'";
        }
        if(count($parent_ids)>0){
            $where .= " AND f.parent_id in (".implode(',', $parent_ids).")";
        }
        $files = D('file f')->field('f.id, f.title, f.file_layer, f.number, f.order_id, f.status, f.read_person')
                            ->where($where)
                            ->order('f.order_id asc, id desc')->select();
        /*未讀標記*/
        foreach($files as $key => $value){
            $files[$key]['read_ck'] = self::not_read_check($value);
        }
        return $files;
    }
    /*未讀標記*/
    static public function not_read_check($file){
        if($file['status']==1 && $file['file_layer']==0){ /*文件未刪除  且 文件是文章*/
            if($file['read_person']===null || !preg_match("/\"".$_SESSION['adminId']."\"/i", $file['read_person'])){
                return 1;
            }
        }
        return 0;
    }
    static public function merge_sub_menu_to_list($menu){
        $list = [];
        foreach ($menu as $vo) {
            array_push($list, $vo);
            if($vo['sub_menu']){
                $list = array_merge($list, self::merge_sub_menu_to_list($vo['sub_menu']));
            }
        }
        return $list;
    }


    static public function send_notification_to_user($user_id, $payload){
        if(!$user_id){ return; }
        if(!isset($payload['title'])){ $payload['title'] = '系統提示'; }
        if(!isset($payload['msg'])){ $payload['msg'] = '系統訊息'; }
        // if(!isset($payload['open_url'])){ $payload['open_url'] = ''; }

        $subscription = D('eip_user e')->field('e.*, s.endpoint, s.expirationTime, s.auth, s.p256dh')
                                       ->join('LEFT JOIN subscription s ON s.user_id=e.id')
                                       ->where('e.id="'.$user_id.'"')->select();
        foreach ($subscription as $key => $value) {
            $subscription_data = [
                'contentEncoding'   => 'aes128gcm',
                'endpoint'          => $value['endpoint'],
                'expirationTime'    => $value['expirationTime'],
                'keys'              => [
                                        'auth'      => $value['auth'],
                                        'p256dh'    => $value['p256dh']
                                    ]
            ];
            $result = self::do_send($subscription_data, $payload);
        }
        return $result;
    }
    /*發送推播通知*/
    static public function do_send($subscription_data, $payload){
        if(!$subscription_data['endpoint']){ return; }

        $subscription = Subscription::create($subscription_data);

        $auth = array(
            'VAPID' => array(
                'subject'   => 'https://github.com/Minishlink/web-push-php-example/',
                'publicKey' => C('NOTIFICATION_PUBKEY'), // don't forget that your public key also lives in app.js
                'privateKey'=> C('NOTIFICATION_PRIKEY'), // in the real world, this would be in a secret file
            ),
        );

        if(!isset($payload['notification_id'])){
            $payload['notification_id'] = self::geraHash(32);
        }

        $webPush = new WebPush($auth);
        $report = $webPush->sendOneNotification(
            $subscription,
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        // handle eventual errors here, and remove the subscription from your server if it is expired
        $endpoint = $report->getRequest()->getUri()->__toString();

        if ($report->isSuccess()) {
            return "[v] Message sent successfully for subscription {$endpoint}.";
        } else {
            return "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
        }
    }

    /**
     * 難度型加解密
     * @param string $string：需要加密解密的字符串
     * @param string $operation：E表示加密，D表示解密
     * @param string $key：密匙
     */
    public static function encrypt($string, $operation, $key = '@Photonic')
    {
        $key = md5($key);
        $key_length = strlen($key);
        $string = $operation == 'D' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'D') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }  
}

?>