<?php

require MODELS.'Module/Tag/Extends_Tag.php';

class Tag extends Extends_Tag {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
	
	/**
     * tag列表
     */
    public function index() {
        $this->_tag();
    }
	
}