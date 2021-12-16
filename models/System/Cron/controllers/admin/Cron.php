<?php

/* v3.1.0  */
	
class Cron extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->template->assign('menu', $this->get_menu_v3(array(
		    L('任务队列') => array('admin/cron/index', 'forward')
		)));
    }
	
	/**
     * 管理
     */
    public function index() {

        if (IS_POST) {
            $ids = $this->input->post('ids');
            $ids && $this->db->where_in('id', $ids)->delete('cron_queue');
        }

		$param = array();
		if ($this->input->get('search') == 1) {
            $param['search'] = 1;
        }

		list($list, $param)	= $this->models('system/cron')->limit_page($param, max((int)$this->input->get('page'), 1), (int)$this->input->get('total'));

		$this->template->assign(array(
			'list' => $list,
			'total' => (int)$param['total'],
			'type' => $this->models('system/cron')->get_type(),
			'pages'	=> $this->get_pagination(dr_url('cron/index', $param), $param['total']),
			'param' => $param,
		));
		$this->template->display('cron_index.html');
    }
	
	/**
     * 查看值
     */
    public function show() {
	
		$id = (int)$this->input->get('id');
		$data = $this->db
					 ->where('id', $id)
					 ->limit(1)
					 ->get('cron_queue')
					 ->row_array();
		if (!$data) {
            exit(L('对不起，数据被删除或者查询不存在'));
        }
		echo '<pre style="width:500px;max-height:400px;overflow:auto;margin-bottom:10px;">';
		print_r(string2array($data['value']));
		echo '</pre>';
    }

	/**
     * 执行
     */
    public function execute() {
	
		$id = (int)$this->input->get('id');
		$data = $this->db->where('id', $id)->limit(1)->get('cron_queue')->row_array();
		if (!$data) {
            $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
        }
		$this->models('system/cron')->execute($data);
        $this->system_log('执行任务【#'.$id.'】'); // 记录日志
		$this->admin_msg(L('操作成功，正在刷新...'), dr_url('cron/index'), 1);
	}
}