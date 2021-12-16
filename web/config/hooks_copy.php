<?php

/**
 * 钩子定义配置
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// 加载应用的钩子配置文件
//if(is_file(CONFPATH.'app_hooks.php')){
//    require_once CONFPATH.'app_hooks.php';
//}


// 加载自定义钩子配置文件
if (is_file(CONFPATH.'my_hooks.php')) {
    require_once CONFPATH.'my_hooks.php';
}

// 加载当前模块的钩子配置文件
if (is_file(APPPATH.'config/my_hooks.php')) {
    require_once APPPATH.'config/my_hooks.php';
}

/*
$hook['钩子名称'][] = array(
    'class' => '类名称',
    'function' => '方法名称',
    'filename' => '钩子文件.php',
    'filepath' => 'hooks',
);
 */