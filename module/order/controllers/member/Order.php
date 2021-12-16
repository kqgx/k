<?php



class Order extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    // 跳到买家中心
    public function index() {

        $url = '/index.php?s=member&mod==order&c=home&m=index';
        redirect($url, 'refresh');
        exit;
    }

}