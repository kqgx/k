<?php

class Home extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 配置应用
     */
    public function index() {
		$this->admin_msg('配置成功', $this->url('excel/index'), 2);
    }
	
	/**
     * 安装应用
     */
    public function install() {
		$id = $this->_admin_install(); // 返回应用id
		$this->admin_msg('安装成功', $this->url('home/index'), 2);
    }
	
	/**
     * 卸载应用
     */
    public function uninstall() {
		$this->_admin_uninstall();
    	$this->admin_msg(L('操作成功，正在刷新...'), dr_url('application/index'), 1);
    }
	
	/**
	 * 应用缓存
	 *
	 * @return	NULL
	 */
	public function cache() {
		$this->_admin_cache();
		if ($this->input->get('todo')) {
			$this->admin_msg(L('应用(%s)缓存', APP_DIR), $this->url('home/index'), 1);
		}
		$this->input->get('admin') or $this->admin_msg(L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 1);
	}
	
}