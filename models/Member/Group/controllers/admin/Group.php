<?php

class Group extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->template->assign('menu', $this->get_menu_v3(array(
			L('会员组模型') => array('member/group/index', 'users'),
			L('添加') => array('member/group/add', 'plus')
		)));
    }

    /**
     * 管理
     */
    public function index() {

		if (IS_POST) {
			if ($this->input->post('action') == 'del') {
			    // 删除
                $ids = $this->input->post('ids');
				$this->models('member/group')->delete($ids);
                $this->clear_cache('member');
                $this->system_log('删除会员组【#'.@implode(',', $ids).'】'); // 记录日志
				$this->msg(1, L('操作成功'));
			} elseif ($this->input->post('action') == 'edit') {
			    // 修改
				$_ids = $this->input->post('ids');
				$_data = $this->input->post('data');
				foreach ($_ids as $id) {
					$this->db->where('id', $id)->update('member_group', array('displayorder' => (int)$_data[$id]['displayorder']));
				}
                $this->clear_cache('member');
                $this->system_log('排序会员等级【#'.@implode(',', $_ids).'】'); // 记录日志
				$this->msg(1, L('操作成功'));
			}
		}

		$this->template->assign(array(
			'list' => $this->models('member/group')->get_data(),
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
				$this->models('member/group')->add($data);
                $this->clear_cache('member');
                $this->system_log('添加会员组【'.$data['name'].'】'); // 记录日志
				$this->admin_msg(L('操作成功'), dr_url('admin/member/group/index'), 1);
			}
		}

		$group = $this->get_cache('member', 'group');
		$overdue = array();
		foreach ($group as $t) {
			if ($t['id'] > 2 && $t['price'] == 0) {
				$overdue[] = array(
					'id' => $t['id'],
					'name' => $t['name']
				);
			}
		}

		$template2 = dr_dir_map(VIEWPATH.'pc/member/', 1);

		$this->template->assign(array(
			'page' => $page,
			'error' => $error,
			'theme' => dr_get_theme(),
			'overdue' => $overdue,
			'is_theme' => 0,
			'mtemplate' => $template2,
		));
		$this->template->display();
    }
	
	/**
     * 修改
     */
    public function edit() {

		$id = (int)$this->input->get('id');
		if ($id == 0) {
			$data = $this->models('member')->guest();
			!$data && $data = array(
				'theme' => 'default',
				'template' => 'default',
			);
		} else {
			$data = $this->models('member/group')->get($id);
			!$data && $this->admin_msg(L('对不起，数据被删除或者查询不存在'));
		}

		$page = (int)$this->input->get('page');
		$error = 0;

		if (IS_POST) {
			$page = (int)$this->input->post('page');
			if ($id == 0) {
				$post = $this->input->post('data');
				$this->models('member')->guest($post);
				$this->system_log('修改会员组【'.L('游客').'】'); // 记录日志
				$this->admin_msg(L('操作成功'), dr_url('admin/member/group/index'), 1);
			} else {
				$post = $this->input->post('data', TRUE);
				if (!$data['name']) {
					$error = L('名称必须填写');
				} else {
					$this->models('member/group')->edit($id, $post);
					$this->clear_cache('member');
					$this->system_log('修改会员组【'.$data['name'].'】'); // 记录日志
					$this->admin_msg(L('操作成功'), dr_url('admin/member/group/index'), 1);
				}
			}
		}

		$group = $this->get_cache('member', 'group');
		$overdue = array();
		foreach ($group as $t) {
			$t['id'] > 2 && $t['price'] == 0 && $overdue[] = array(
				'id' => $t['id'],
				'name' => $t['name']
			);
		}

		$template2 = dr_dir_map(VIEWPATH.'pc/member/', 1);

		$this->template->assign(array(
			'page' => $page,
			'data' => $data,
			'error' => $error,
            'group' => $group,
			'theme' => dr_get_theme(),
			'overdue' => $overdue,
			'is_theme' => strpos($data['theme'], 'http://') === 0 ? 1 : 0,
			'mtemplate' => $template2,
		));
		$this->template->display($id ? 'group_add.html' : 'group_guest.html');
    }
	
	/**
     * 删除
     */
    public function del() {
        $id = (int)$this->input->get('id');
		$this->models('member/group')->delete($id);
        $this->clear_cache('member');
        $this->system_log('删除会员组【#'.$id.'】'); // 记录日志
		$this->msg(1, L('操作成功'));
	}

    /**
     * 操作
     */
    public function option() {

        $id = (int)$this->input->get('id');
        $data = $this->models('member/group')->get($id);
        if ($this->input->get('op') == 'apply') {
            $value = $data['allowapply'] ? 0 : 1;
            $this->db->where('id', $id)->update(
                'member_group',
                array('allowapply' => $value)
            );
            $this->system_log('修改会员组【'.$data['name'].'#'.$id.'】申请状态为：'.($value ? '允许申请' : '不可申请')); // 记录日志
        }

        $this->admin_msg(L('操作成功，正在刷新...'), dr_url('admin/member/group/index'), 1);
    }
}