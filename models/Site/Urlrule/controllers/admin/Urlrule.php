<?php

/* v3.1.0  */
	
class Urlrule extends M_Controller {

	private $type;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->type = array(
			0 => L('自定义页面'),
			1 => L('独立模块'),
			2 => L('共享模块'),
			3 => L('共享栏目'),
			4 => L('站点URL'),
			5 => L('空间黄页'),
			6 => L('会员模块'),
		);
		$this->template->assign('type', $this->type);
		$this->template->assign('menu', $this->get_menu_v3(array(
		    L('URL规则') => array('admin/urlrule/index', 'magnet'),
		    L('添加') => array('admin/urlrule/add', 'plus'),
		    L('伪静态规则') => array('admin/route/index', 'safari'),
		)));
    }
	
	/**
     * 管理
     */
    public function index() {

		if (IS_POST) {
			$ids = $this->input->post('ids', TRUE);
			if (!$ids) {
                $this->msg(0, L('您还没有选择呢'));
            }
			if (!$this->is_auth('admin/urlrule/del')) {
                $this->msg(0, L('您无权限操作'));
            }
            $this->db->where_in('id', $ids)->delete('urlrule');
			$this->cache(1);
            $this->system_log('删除URL规则【#'.@implode(',', $ids).'】'); // 记录日志
			$this->msg(1, L('操作成功，正在刷新...'));
		}

		$this->template->assign(array(
			'list' => $this->db->get('urlrule')->result_array(),
			'color' => array(
				0 => 'default',
				1 => 'info',
				2 => 'success',
				3 => 'warning',
				4 => 'danger',
				5 => '',
				6 => 'primary',
			),
		));
		$this->template->display('urlrule_index.html');
    }

    /**
     * 复制
     */
    public function copy() {

        $id = (int)$this->input->get('id');
        $data = $this->db
                     ->where('id', $id)
                     ->limit(1)
                     ->get('urlrule')
                     ->row_array();
        if ($data) {
            $this->db->insert('urlrule', array(
                'type' => $data['type'],
                'name' => $data['name'].'_copy',
                'value' => $data['value'],
            ));
            $this->cache(1);
            $this->system_log('复制URL规则【#'.$id.'】'); // 记录日志
        }

        $this->msg(1, L('操作成功，正在刷新...'));
    }
	
	/**
     * 添加
     */
    public function add() {

		if (IS_POST) {
		    $name = $this->input->post('name');
			$this->db->insert('urlrule', array(
				'type' => $this->input->post('type'),
				'name' => $name ? $name : '未命名规则',
				'value' => array2string($this->input->post('data')),
			 ));
            $this->system_log('添加URL规则【#'.$this->db->insert_id().'】'.$this->input->post('name')); // 记录日志
            $this->cache(1);
			$this->admin_msg(L('操作成功，正在刷新...'), dr_url('urlrule/index'), 1);
		}

		$this->template->display('urlrule_add.html');
    }

	/**
     * 修改
     */
    public function edit() {

		$id = (int)$this->input->get('id');
		$data = $this->db
					 ->where('id', $id)
					 ->limit(1)
					 ->get('urlrule')
					 ->row_array();
		if (!$data) {
            $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
        }

		if (IS_POST) {
            $name = $this->input->post('name');
			$this->db->where('id', $id)->update('urlrule', array(
                'type' => $this->input->post('type'),
                'name' => $name ? $name : '未命名规则',
				'value' => array2string($this->input->post('data')),
			 ));
			$this->cache(1);
            $this->system_log('修改URL规则【#'.$id.'】'.$this->input->post('name')); // 记录
			$this->admin_msg(L('操作成功，正在刷新...'), dr_url('urlrule/index'), 1);
		}

		$data['value'] = string2array($data['value']);
		$this->template->assign(array(
			'data' => $data,
        ));
		$this->template->display('urlrule_add.html');
    }
	
    /**
     * 缓存
     */
    public function cache($update = 0) {
		$this->models('system')->urlrule();
		((int)$_GET['admin'] || $update) or $this->admin_msg(L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 1);
	}
}