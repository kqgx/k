<?php

/* v3.1.0  */
	
class Home extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 首页
     */
    public function index() {

		// 登录验证
		$url = dr_member_url('login/index', array('backurl' => urlencode(dr_now_url())));
		!$this->uid && $this->member_msg(L('会话超时，请重新登录').$this->models('member/login')->logout(), $url);

		$this->load->library('dip');
		$this->template->assign(array(
			'index' => 1,
		));
		$this->template->display(IS_AJAX ? 'main.html' : 'index.html');
    }

}