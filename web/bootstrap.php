<?php
define('EXT', '.php'); // PHP文件扩展名
define('SYSDIR', 'system'); // “系统文件夹”的名称
define('BASEPATH', WEBPATH . 'framework/'); // CI框架目录

require CONFPATH.'user_agents.php';

// 客户端判定
$host = strtolower($_SERVER['HTTP_HOST']);
$is_mobile = 0;
if ($mobiles) {
    foreach ($mobiles as $key => $val) {
        if (FALSE !== (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), $key))) {
            // 表示移动端
            $is_mobile = 1;
            break;
        }
    }
}
define('DOMAIN_NAME', $host); // 当前域名
define('PAGE_CACHE_URL', ($is_mobile ? 'mobile-' : '').$host.'/'.ltrim($uri, '/'));

// 解析自定义域名
if (is_file(CONFPATH.'module_domain.php')){
    $domain = require CONFPATH.'module_domain.php';
    if ($domain) {
        $dir = isset($domain[$host]) && $domain[$host] ? $domain[$host] : '';
        if (strpos($dir, 'm_') !== false) {
            $dir  = substr($dir, 2);
            !defined('IS_MOBILE_SELF') && define('IS_MOBILE_SELF', 1);
        }
        if ($dir && is_dir(WEBPATH.'module/'.$dir)) {
            !$_GET['s'] && $_GET['s'] = $dir; // 强制定义为模块
        }
    }
    unset($domain);
}

// 伪静态字符串
$uu = isset($_SERVER['HTTP_X_REWRITE_URL']) || trim($_SERVER['REQUEST_URI'], '/') == SELF ? trim($_SERVER['HTTP_X_REWRITE_URL'], '/') : ($_SERVER['REQUEST_URI'] ? trim($_SERVER['REQUEST_URI'], '/') : NULL);
$uri = strpos($uu, SELF) === 0 || strpos($uu, '?') === 0 ? '' : $uu; // 以index.php或者?开头的uri不做处理

if (defined('DR_UEDITOR')) {
    define('APPPATH', FCPATH . 'module/member/');
    define('APP_DIR', 'member');
    define('ENVIRONMENT', '../../../../config');
} else {
    require FCPATH.'core/M_Rewrite.php';
}

!defined('DR_URI') && define('DR_URI', '');

require FCPATH.'core/M_Common.php';

require BASEPATH . 'core/CodeIgniter.php';