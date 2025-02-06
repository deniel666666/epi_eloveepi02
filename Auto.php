<?php
// Cpanel 自動執行設定處: Cron Jobs
// 請設定『每小時』執行自動程式，參考Cron命令：/usr/local/bin/php /home/erp2000/eip.erp2000.com/Auto.php
// 以上命令需修改二處：
// 1. /usr/local/bin/php需改成Cpanel中「PHP command examples」所寫的那段，對應到主機的php路徑
// 2. /home/erp2000/eip.erp2000.com/Auto.php 需改成此主機放置此網站的Auto.php的路徑
$main_url = "https://eip01.eloveeip.com"; /*EIP主網址(需修改，且注意是否有強制跳轉https，須設定正確協議方式)*/

/*自動出貨，每日執行一次*/
    $autoTime_create_sale = '09:00:00'; /*預設每日凌晨1點*/
    auto_act($main_url."/customer.php/Ajax/auto_contract_sale", $autoTime_create_sale);

/*未付款款提醒，每日執行一次*/
    $autoTime_not_pay_remind = '10:00:00'; /*預設每個整點*/
    auto_act($main_url."/customer.php/Ajax/auto_not_pay_remind", $autoTime_not_pay_remind);

/*自動給特休，每日執行一次*/
    $autoTime_set_special_rest = '02:00:00'; /*(預設每日凌晨2點)*/
    auto_act($main_url."/customer.php/Ajax/auto_set_special_rest", $autoTime_set_special_rest);



/*
$url->執行網址
$act_time->執行時間，預設每小時的0分0秒
*/
function auto_act($url, $act_time='H:00:00')
{
    if(!$url){ return; } /* 沒網址就不執行 */
    
    $act_time = str_replace('H', date('H'), $act_time);
    $act_time = str_replace('i', date('i'), $act_time);
    $act_time = date('Y-m-d ').$act_time;
    // echo $act_time."\n";
    $act_time = strtotime($act_time);
    $diff_min = abs((time() - $act_time) / 60);
    // echo $diff_min."\n";
    if($diff_min>3){ return; } /* 差異大於3分鐘就不執行 */
    echo "執行:".$url."\n";

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
    curl_close($curl);  
    echo $output;
}