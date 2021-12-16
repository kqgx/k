<?php

require MODELS.'Content/Comment/extends/D_Member_Comment.php';
 
class Comment extends D_Member_Comment {
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->module(APP_DIR);
	}

}