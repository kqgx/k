<?php

class Image extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }
	
    public function index() {
        $id = (int)$this->input->get('id');
        $width = (int)$this->input->get('width');
        $height = (int)$this->input->get('height');
        redirect(thumb_get($id, $width, $height));
	}
	
	public function avatar(){
	    $uid = (int)$this->input->get('uid');
        redirect(thumb_get($uid));
	}
	
	public function qrcode(){
	    $string = $this->input->get('string');
        redirect(qrcode_get($string, 1, 1, 1, 1));
	}
}