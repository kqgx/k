<?php

/* v3.1.0  */
	
class Sysvar extends M_Controller {

    public $type;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->template->assign('menu', $this->get_menu_v3(array(
		    L('全局变量') => array('admin/sysvar/index', 'tumblr'),
		    L('添加') => array('admin/sysvar/add_js', 'plus'),
		)));
        $this->type = array(
            0 => L('逻辑值'),
            1 => L('文本值'),
        );
    }
	
	/**
     * 管理
     */
    public function index() {
	
		if (IS_POST) {
			$ids = $this->input->post('ids', TRUE);
			if (!$ids) {
                $this->msg(0, L('您还没有选择呢'));
            } elseif (!$this->is_auth('admin/sysvar/del')) {
                $this->msg(0, L('您无权限操作'));
            }
            $this->db->where_in('id', $ids)->delete('var');
            $this->system_log('删除全局变量【#'.@implode(',', $ids).'】'); // 记录日志
            $this->cache(1);
			$this->msg(1, L('操作成功，更新缓存生效'));
		}

        $page = max(1, (int)$_GET['page']);
        $total = $_GET['total'] ? $_GET['total'] : $this->db->count_all_results('var');

        $data = $total ? $this->db->order_by('id desc')->limit(SITE_ADMIN_PAGESIZE, SITE_ADMIN_PAGESIZE * ($page - 1))->get('var')->result_array() : array();

		
		$this->template->assign(array(
            'list' => $data,
            'total' => $total,
            'pages' => $this->get_pagination(dr_url('var/index', array('total' => $total)), $total),
        ));
		$this->template->display('sysvar_index.html');
    }
	
	/**
     * 添加
     */
    public function add() {
	
		if (IS_POST) {
			$data = $this->input->post('data');
			if (!$data['name']) {
                $this->msg(0, L('【%s】不能为空', L('名称')), 'name');
            }
			if (!$data['cname']) {
                $this->msg(0, L('只能是字母或者数字'), 'cname');
            }
            if ($this->db->where('cname', $data['cname'])->where('id<>', 0) ->count_all_results('var')) {
                $this->msg(0, L('别名已经存在,不能重复'), 'cname');
            }
            $data['value'] = $data['value'][$data['type']];
			$this->db->insert('var', $data);
            $id = $this->db->insert_id();
			$this->cache(1);
            $this->system_log('添加全局变量【'.$data[1]['type'].'#'.$id.'】'); // 记录日志
			$this->msg(1, L('操作成功，更新缓存生效'), '');
		}

		$this->template->display('sysvar_add.html');
    }

	/**
     * 修改
     */
    public function edit() {
	
		$id = (int)$this->input->get('id');
		$data = $this->db->where('id', $id)->limit(1)->get('var')->row_array();
		if (!$data) {
            exit(L('对不起，数据被删除或者查询不存在'));
        }
		
		if (IS_POST) {
            $data = $this->input->post('data');
            if (!$data['name']) {
                $this->msg(0, L('【%s】不能为空', L('名称')), 'name');
            }
            if (!$data['cname']) {
                $this->msg(0, L('只能是字母或者数字'), 'cname');
            }
            if ($this->db->where('cname', $data['cname'])->where('id<>', $id) ->count_all_results('var')) {
                $this->msg(0, L('别名已经存在,不能重复'), 'cname');
            }
            $data['value'] = $data['value'][$data['type']];
            $this->db->where('id', $id)->update('var', $data);
            $this->cache(1);
            $this->system_log('修改全局变量【'.$data[1]['type'].'#'.$id.'】'); // 记录日志
            $this->msg(1, L('操作成功，更新缓存生效'), '');
		}
		
		$this->template->assign(array(
			'data' => $data,
        ));
		$this->template->display('sysvar_add.html');
    }

    /**
     * 缓存
     */
    public function cache($update = 0) {
        $this->models('system')->sysvar();
        ((int)$_GET['admin'] || $update) or $this->admin_msg(L('操作成功，正在刷新...'), isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', 1);
    }
}