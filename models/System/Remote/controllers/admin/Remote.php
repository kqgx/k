<?php

/* v3.1.0  */
	
class Remote extends M_Controller {

    public $type;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->type = array(
            1 => 'FTP',
            3 => '阿里云存储OSS',
            2 => '百度云存储BCS',
            4 => '腾讯云存储COS',
        );
		$this->template->assign('menu', $this->get_menu_v3(array(
		    L('远程附件') => array('admin/remote/index', 'upload'),
		    L('添加') => array('admin/remote/add', 'plus')
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
			if (!$this->is_auth('admin/remote/del')) {
                $this->msg(0, L('您无权限操作'));
            }
            $this->db->where_in('id', $ids)->delete(SITE_ID.'_remote');
			$this->cache(1);
            $this->system_log('删除远程附件配置【#'.@implode(',', $ids).'】'); // 记录日志
			$this->msg(1, L('操作成功，更新缓存生效.'));
		}

		$this->template->assign(array(
			'list' => $this->db->get(SITE_ID.'_remote')->result_array(),
		));
		$this->template->display('remote_index.html');
    }
	
	/**
     * 添加
     */
    public function add() {

        $error = '';

		if (IS_POST) {
            $data = $this->input->post('data');
            $data['type'] = $this->input->post('type');
            $data['value']['host'] = $data['value']['host'.$data['type']];
            if (!$data['name'] || !$data['url']) {
                $error = L('名称或地址不能为空');
            } else if (!$data['exts']) {
                $error = L('扩展名不能为空');
            } else {
                $exts = @explode(',', $data['exts']);
                if (!$exts) {
                    $error = L('扩展名不能为空');
                } else {
                    foreach ($exts as $e) {
                        if ($e && $row = $this->db->where('`exts` LIKE "%,'.$e.',%"')->get(SITE_ID.'_remote')->row_array()) {
                            $error = L('扩展名【%s】已经存在于【%s】之中了，确保扩展名的唯一性', $e, $row['name']);
                            break;
                        }
                    }
                    $data['exts'] = ','.implode(',', $exts).',';
                }
            }
            if (!$error) {
                $data['value'] = array2string($data['value']);
                $this->db->insert(SITE_ID.'_remote', $data);
                $this->system_log('添加远程附件配置【#'.$this->db->insert_id().'】'.$data['name']); // 记录日志
                $this->cache(1);
                $this->admin_msg(L('操作成功，更新缓存生效'), dr_url('remote/index'), 1);
            }
            $data['exts'] = trim($data['exts'], ',');
		} else {
            $data = array(
                'type' => 1,
                'value' => array(
                    'port' => 21,
                )
            );
        }

        $this->template->assign(array(
            'data' => $data,
            'error' => $error,
        ));
		$this->template->display('remote_add.html');
    }

	/**
     * 修改
     */
    public function edit() {

		$id = (int)$this->input->get('id');
		$data = $this->db->where('id', $id)->limit(1)->get(SITE_ID.'_remote')->row_array();
		if (!$data) {
            $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
        }

		if (IS_POST) {
            $data = $this->input->post('data');
            $data['type'] = $this->input->post('type');
            $data['value']['host'] = $data['value']['host'.$data['type']];
            if (!$data['name'] || !$data['url']) {
                $error = L('名称或地址不能为空');
            } else if (!$data['exts']) {
                $error = L('扩展名不能为空');
            } else {
                $exts = @explode(',', $data['exts']);
                if (!$exts) {
                    $error = L('扩展名不能为空');
                } else {
                    foreach ($exts as $e) {
                        if ($e && $row = $this->db->where('id<>'.$id)->where('`exts` LIKE "%,'.$e.',%"')->get(SITE_ID.'_remote')->row_array()) {
                            $error = L('扩展名【%s】已经存在于【%s】之中了，确保扩展名的唯一性', $e, $row['name']);
                            break;
                        }
                    }
                    $data['exts'] = ','.implode(',', $exts).',';
                }
            }
            if (!$error) {
                $data['value'] = array2string($data['value']);
                $this->db->where('id', $id)->update(SITE_ID.'_remote', $data);
                $this->system_log('修改远程附件配置【#'.$id.'】'.$data['name']); // 记录日志
                $this->cache(1);
                $this->admin_msg(L('操作成功，更新缓存生效'), dr_url('remote/index'), 1);
            }
		} else {
            $data['value'] = string2array($data['value']);
        }
        $data['exts'] = trim($data['exts'], ',');

		$this->template->assign(array(
			'data' => $data,
            'error' => $error,
        ));
		$this->template->display('remote_add.html');
    }
}