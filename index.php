<?php /***
 * index.php
 ***/

header("Content-type: text/html; charset=UTF-8");
if (!file_exists('./db.config.php')) die('db.config.php 不存在');
date_default_timezone_set('Asia/Taipei');
define('PHOTONICCMS', './Erp');
define('CMS_DATA', './CmsData');
define('UPLOAD_PATH', './Uploads');
define('URL_MODEL', 'PATHINFO');
define('APP_DEBUG', true); // true=debug
define('NO_CACHE_RUNTIME', true); // true=debug
//define('APP_NAME', 'Erp'); //3.0 down version
// Home
define('BIND_MODULE','Trade');
define('APP_PATH', './Erp/');
require "./ThinkPHP/ThinkPHP.php";
//App::run(); //3.0 down version

//require "Erp/ThinkPHP/ThinkPHP.php";

?>