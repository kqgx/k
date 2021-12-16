<?php
define('IS_API', TRUE);
if($_GET['entry'] == 'member'){
    define('IS_MEMBER', TRUE);
    $_GET['d'] = 'member';
} else if ($_GET['entry'] == 'admin') {
    define('IS_ADMIN', TRUE);
    $_GET['d'] = 'admin';
} 
require('index.php'); // 引入主文件
