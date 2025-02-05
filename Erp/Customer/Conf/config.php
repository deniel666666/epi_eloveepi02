<?php
if (!defined('PHOTONICCMS')) die('not in photonicCMS');
$database = require('./db.config.php');
$config	= array(
    // 外掛引擎
	//'TMPL_ENGINE_TYPE' => 'Smarty',
	// 應用
	'MULTI_MODULE' => false,
	'DEFAULT_MODULE' => 'Qhand',
	'MODULE_DENY_LIST' => array(),
	'MODULE_ALLOW_LIST' => array(),
	// URL
	'URL_MODEL' => 1,
	'URL_HTML_SUFFIX' => 'html',
	'URL_DENY_SUFFIX' => 'ico|png|gif|jpg',
	// 常數
	TMPL_PARSE_STRING  => array( 
	    '__PUBLIC__' => '/Public/qhand',   // 更改預設的 __PUBLIC__
	)
);

return array_merge($database, $config);
?>