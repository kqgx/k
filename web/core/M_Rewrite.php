<?php
/**
 * 通过自定义url地址解析控制器及模块
 * 根据路由来匹配S变量
 */
if ($uri) {
    define('DR_URI', $uri);
    include CONFPATH.'routes.php';
    $rewrite = require CONFPATH.'rewrite.php';
    $routes = $rewrite && is_array($rewrite) && count($rewrite) > 0 ? array_merge($routes, $rewrite) : $routes;
    // 正则匹配路由规则
    $value = $u = '';
    foreach ($routes as $key => $val) {
        $match = array();
        if ($key == $uri || @preg_match('/^'.$key.'$/U', $uri, $match)) {
            unset($match[0]);
            $u = $val;
            $value = $match;
            break;
        }
    }
    // 同时使用pathinfo和query两种模式，配置项优先
    if(!$u && strpos($uri, '/') !== FALSE){
        // 过滤?后参数
        if(($end = strpos($uri, '?')) !== FALSE){
            $uri = substr($uri, 0, $end);
        }
        // 判断是否为api请求
        if(substr($uri, 0, 3) == 'api'){
            define('IS_API', TRUE);
            $uri = substr($uri, 4);
        }
        // 判断是否是会员入口
        if(substr($uri, 0, 6) == 'member'){
            define('IS_MEMBER', TRUE);
            $_GET['d'] = 'member';
            $u = substr($uri, 7);
        } else if(substr($uri, 0, 5) == 'admin') {
            // 判断是否是后台入口
            define('IS_ADMIN', TRUE);
            $_GET['d'] = 'admin';
            $u = substr($uri, 6);
        } else {
            $u = $uri;
        }
    }
    if ($u) {
        if (strpos($u, 'index.php?') === 0) {
            // URL参数模式
            $_GET = array();
            $queryParts = explode('&', str_replace('index.php?', '', $u));
            foreach ($queryParts as $param) {
                $item = explode('=', $param);
                $_GET[$item[0]] = $item[1];
                if (strpos($item[1], '$') !== FALSE) {
                    $id = (int)substr($item[1], 1);
                    $_GET[$item[0]] = isset($match[$id]) ? $match[$id] : $item[1];
                }
            }
        } elseif (strpos($u, '/') !== FALSE) {
            // URI分段模式
            $array = explode('/', $u);
            $s = array_shift($array);
            if( 'pay' == $s ){
                // var_dump($s);var_dump(array_shift($array));die;
                $_GET['c'] = $s;
                $_GET['m'] = array_shift($array);
            } elseif (is_dir(WEBPATH.'module/'.$s) || is_dir(WEBPATH.'app/'.$s)) {
                $_GET['s'] = $s;
                $_GET['c'] = array_shift($array);
                $_GET['m'] = array_shift($array);
            } else {
                if(IS_MEMBER === TRUE){
                    if (is_file(WEBPATH.'module/member/controllers/member/'.ucfirst($s).'.php')) {
                        $_GET['c'] = $s;
                        $_GET['m'] = array_shift($array);
                    }
                } else if(IS_ADMIN === TRUE){
                    if (is_file(FCPATH.'controllers/admin/'.ucfirst($s).'.php')) {
                        $_GET['c'] = $s;
                        $_GET['m'] = array_shift($array);
                    }
                } else {
                    if (is_file(FCPATH.'controllers/'.ucfirst($s).'.php')) {
                        $_GET['c'] = $s;
                        $_GET['m'] = array_shift($array);
                    }
                }
            }
            if ($array) {
                foreach ($array as $k => $t) {
                    $i%2 == 0 && $_GET[str_replace('$', '_', $t)] = isset($array[$k+1]) ? $array[$k+1] : '';
                    $i ++;
                }
                if ($value) {
                    foreach ($_GET as $k => $v) {
                        if (strpos($v, '$') !== FALSE) {
                            $id = (int)substr($v, 1);
                            $_GET[$k] = isset($value[$id]) ? $value[$id] : $v;
                        }
                    }
                }
            }
        } else {
            $_GET['c'] = $u;
        }
    }
}

if (isset($_GET['s']) && preg_match('/^[a-z]+$/i', $_GET['s'])) {
    if (is_dir(WEBPATH . 'module/' . $_GET['s'])) { // 模块
        define('APPPATH', WEBPATH . 'module/' . $_GET['s'] . '/');
    } elseif (is_dir(WEBPATH . 'app/' . $_GET['s'] . '/')) { // 应用
        define('APPPATH', WEBPATH . 'app/' . $_GET['s'] . '/');
    }
    define('APP_DIR', $_GET['s']); // 识别目录名称
} else if (IS_MEMBER === TRUE){
    define('APPPATH', WEBPATH . 'module/member/');
    define('APP_DIR', 'member');
} else {
    // 系统主目录
    define('APPPATH', FCPATH . '/');
    define('APP_DIR', '');
}

!isset($_GET['c']) && $_GET['c'] = 'home';
!isset($_GET['m']) && $_GET['m'] = 'index';

!defined('IS_MEMBER') && define('IS_MEMBER', FALSE);
!defined('IS_API') && define('IS_API', FALSE);