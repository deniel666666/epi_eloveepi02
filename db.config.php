<?php
/*** 
 * db.config.php
 * 資料庫資訊
 *
 * 資料庫的配置與基本的 ThinkPHP 配置
 ***/

if (!defined('PHOTONICCMS')) exit();

/////// Mail 設定///////
	define('MAIL_FROM_TITLE', 'EIP系統提醒');
	define('MAIL_HOST', 'mail.erp2000.com');
	define('MAIL_FROM_ADDRESS', 'admin@erp2000.com');
	define('MAIL_FROM_PASSWROD', '');

/////// 稅率 設定///////
	define('TAX_RATE', 0.05);

/////// 電子發票 設定///////
	define('PlatformID', '');

	define('Invoice_Url', 	'https://einvoice-stage.ecpay.com.tw/');	/*請求環境(測試)*/
	define('MerchantID', 	'2000132');									/*特店代號(測試)*/
	define('HashKey', 		'ejCk326UnaZWKisg');						/*HashKey(測試)*/
	define('HashIV', 		'q9jcZX8Ib9LM8wYk');						/*HashIV(測試)*/
	// define('Invoice_Url', 	'https://einvoice.ecpay.com.tw/');	/*請求環境(正式)*/
	// define('MerchantID', 	'');								/*特店代號(正式)*/
	// define('HashKey', 		'');								/*HashKey(正式)*/
	// define('HashIV', 		'');								/*HashIV(正式)*/

/////// 電話串接 設定(port 8080 需請聯陽開通IP)///////
	define('UCRM_SERVER_HTTP_DOMAIN', 'https://202.55.239.36:8443');
	define('UCRM_SERVER_APIKEY', '');


return array(
/////// 資料庫 erp_qhand ///////
    // 資料庫類型
	'DB_TYPE' => 'mysql',
	// Database Server 位址
	'DB_HOST' => 'localhost',
	// 使用的資料庫名稱
	'DB_NAME' => 'eip_test',
	// 登入 SQL 的用戶帳號
	'DB_USER' => 'root',
	'DB_PWD' => '',
	// 登入資料庫使用的 port
	'DB_PORT' => '3306',
	// 資料庫的名稱前綴
	'DB_PREFIX' => '',
	
/////// 資料庫 seo排名 ///////
	'DB_SEO_RANK' => 'mysql://root:@localhost:3306/eip_seo_key_rank', /* 使用者:密碼@localhost:3306/資料庫 */
	
/////// 其他 ///////
	// old 'f2fffc5622aa7245d15bcceb2a679093'
	'ADMIN_ACCESS' => '61d6357d687a74ee3427a734860881ce',
	'URL_ROUTER_ON' => false,
	'URL_DISPATCH_ON' => true,
	'DEFAULT_THEME' => 'default',
	'TOKEN_ON' => false,
	'TOKEN_NAME' => '__hash__',
	'TOKEN_TYPE' => 'md5',
	'TMPL_CACHE_ON' => false,
	'TMPL_CACHE_TIME' => -1,
	'DB_FIELDS_CACHE' => false,

/////// Session ///////
	'SESSION_AUTO_START' =>true,//系统启动Session
	'SESSION_OPTIONS'=>array(
			'expire'=>0,//设置过期时间session
	),

/////// 推播通知 無設定NOTIFICATION_PUBKEY視為不用///////
	'NOTIFICATION_PUBKEY' => 'BHAWEuTvLrhhDk8-3ItMsN65M6FOOYWt0yYy_mIgKm75r5Gxp7Ox1O6D314ef2ZH1TjUwZrGwL6avFHuHqinWaI',
	'NOTIFICATION_PRIKEY' => 'CDRJNkbBu8O4ZMfP0YEPd35Db4nc6MHK1ZWfthwVbks',
/////// GOOGLE儲存空間 桶名///////
	'GOOGLE_STORAGE_BUCKET_NAME' => '', // 可指定，不然則用網站域名去除「.」後的字串
);
?>
