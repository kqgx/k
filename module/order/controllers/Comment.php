<?php

require MODELS.'Content/Comment/Extends_Comment.php';
 
class Comment extends Extends_Comment {
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->module(APP_DIR);
	}

}