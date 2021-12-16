<?php

class Notice extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    public function index() {
		$this->_notice($type = (int)$this->input->get('type'));
    }
	
	/**
     * 提醒查看
     */
    private function _notice($type = 1) {

        $name = array(
            1 => L('系统提醒'),
            2 => L('互动提醒'),
            3 => L('模块提醒'),
            4 => L('应用提醒'),
        );
        
        $result = $this->models('member/notice')->get($this->uid, $type);
        
        // 标记所有已读
        $this->models('member/notice')->set_read_all($this->uid, $type);

        $this->render(array(
            'list' => $result['list'],
            'pages'	=> $this->models('member/notice')->pages(url_build("member/notice/index/type/{$type}"), $result['total']),
            'meta_name' => $name[$type],
        ), 'notice_index.html');
	}
}