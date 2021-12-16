<?php

require MODELS.'Content/Comment/Extends_Comment_Admin.php';
 
class Comment extends Extends_Comment_Admin {
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->module(APP_DIR);
	}

}