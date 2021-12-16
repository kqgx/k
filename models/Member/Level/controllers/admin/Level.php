<?php

class Level extends M_Controller {

	public $groupid;
	
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->groupid = (int)$this->input->get('gid');
		!$this->groupid && $this->admin_msg(L('会员组不存在'));
		$this->groupid < 2 && $this->admin_msg(L('该会员组无等级功能'));
		$this->template->assign(array(
			'menu' => $this->get_menu_v3(array(
				L('会员组模型') => array('member/group/index', 'users'),
				L('等级管理') => array('member/level/index/gid/'.$this->groupid, 'signal'),
				L('添加') => array('member/level/add/gid/'.$this->groupid, 'plus'),
			)),
			'groupid' => $this->groupid
		));
    }

    /**
     * 管理
     */
    public function index() {
		if (IS_POST) {
			if ($this->input->post('action') == 'del') { // 删除
                $ids = $this->input->post('ids');
				$this->models('member/level')->delete($ids);
                $this->clear_cache('member');
                $this->system_log('删除会员等级【#'.@implode(',', $ids).'】'); // 记录日志
				$this->msg(1, L('操作成功'));
			} elseif ($this->input->post('action') == 'edit') { // 修改

			}
		}
		$this->template->assign(array(
			'list' => $this->models('member/level')->get_data(),
		));
		$this->template->display();
    }
	
	/**
     * 添加
     */
    public function add() {
		$page = (int)$this->input->get('page');
		$error = 0;
		if (IS_POST) {
			$data = $this->input->post('data', TRUE);
			$page = (int)$this->input->post('page');
			if (!$data['name']) {
				$error = L('名称必须填写');
			} else {
				$this->models('member/level')->add($data);
                $this->clear_cache('member');
                $this->system_log('添加会员等级【'.$data['name'].'】'); // 记录日志
				$this->admin_msg(L('操作成功'), dr_url('admin/member/level/index', array('gid' => $this->groupid)), 1);
			}
		}
		$this->template->assign(array(
			'page' => $page,
			'error' => $error,
		));
		$this->template->display();
    }
	
	/**
     * 修改
     */
    public function edit() {
		
		$id = (int)$this->input->get('id');
		$data = $this->models('member/level')->get($id);
		!$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
		
		$page = (int)$this->input->get('page');
		$error = 0;
		if (IS_POST) {
			$_data = $data;
			$data = $this->input->post('data', TRUE);
			$page = (int)$this->input->post('page');
			if (!$data['name']) {
				$error = L('名称必须填写');
			} else {
				$this->models('member/level')->edit($_data, $data);
                $this->clear_cache('member');
                $this->system_log('修改会员等级【'.$data['name'].'】'); // 记录日志
				$this->admin_msg(L('操作成功'), dr_url('admin/member/level/index', array('gid' => $this->groupid)), 1);
			}
		}
		
		$this->template->assign(array(
			'page' => $page,
			'data' => $data,
			'error' => $error
		));
		$this->template->display();
    }
	
	/**
     * 删除
     */
    public function del() {
        $id = (int)$this->input->get('id');
		$this->models('member/level')->delete($id);
        $this->system_log('删除会员等级【#'.$id.'】'); // 记录日志
        $this->clear_cache('member');
		$this->msg(1, L('操作成功'));
	}
}