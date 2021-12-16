<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, X-Token, X-Sign");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// 显示错误提示
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT);
function_exists('ini_set') && ini_set('display_errors', true);
function_exists('ini_set') && ini_set('memory_limit', '1024M');
function_exists('set_time_limit') && set_time_limit(30);

define('MTBASE', __DIR__ . '/');
define('FILEPATH', MTBASE . 'file/');
define('WEBPATH', MTBASE . '../');
define('DATAPATH', WEBPATH . 'data/');
define('LOGPATH', DATAPATH . 'log/');
define('CACHEPATH', WEBPATH . 'cache/');
define('MODELS', WEBPATH . 'models/');
define('FCPATH', WEBPATH . 'web/');
define('CONFPATH', FCPATH . 'config/');
define('LIBRARIES', FCPATH . 'libraries/');
define('VIEWPATH', FCPATH . 'views/');
define('STATICS', MTBASE . 'statics/');

!defined('SELF') && define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
!defined('IS_ADMIN') && define('IS_ADMIN', false);


// 执行主程序
require FCPATH . 'bootstrap.php';
