<?php

/**
 * 钩子定义配置
 */
defined('BASEPATH') OR exit('No direct script access allowed');

// 加载当前模块的钩子配置文件
if (is_file(APPPATH.'config/my_hooks.php')) {
    require_once APPPATH.'config/my_hooks.php';
}