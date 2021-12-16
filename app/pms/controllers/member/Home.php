<?php
	
class Home extends M_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
		$this->load->model('pm_model');
    }

    /**
     * 消息管理
     */
    public function index() {
		
		if (IS_POST) {
			if ($this->input->post('action') == 'read') {
				$this->pm_model->set_read($this->uid, $this->input->post('ids'));
				$this->msg(1, L('操作成功，正在刷新...'));
			} else {
				$this->pm_model->deletes($this->uid, $this->input->post('ids'));
				$this->msg(1, L('操作成功，正在刷新...'));
			}
		}
		
		if ($this->input->get('action') == 'more') {
		    // ajax更多数据
			list($touid, $list) = $this->pm_model->limit_page($this->uid, max(1, (int)$this->input->get('page')));
			if (!$list) {
                exit('null');
            }
			$this->template->assign(array(
				'list' => $list
			));
			$this->template->display('pm_data.html');
			exit;
		}
		
		$list = $this->pm_model->limit_page($this->uid, max(1, (int)$this->input->get('page')));
		
		$this->template->assign(array(
			'list' => $list,
			'searchurl' => 'index.php?s=member&app=pms&c='.$this->router->class.'&m='.$this->router->method.'&action=more',
            'meta_name' => L('短消息'),
		));
		$this->template->display('pm_index.html');
    }
	
	/**
     * 发送消息
     */
    public function send() {
	
		$data['username'] = $this->input->get('username', TRUE);
		
		if (IS_POST) {
			$data = $this->input->post('data', TRUE);
			$error = $this->pm_model->send($this->uid, $this->member['username'], $data);
			if ($error === NULL) {
				$this->member_msg(L('操作成功，正在刷新...'), dr_member_url('pms/home/index'), 1);
			}
            if (IS_AJAX || IS_API_AUTH) {
                $this->msg(0, $error);
            }
		}
	
		$this->template->assign(array(
			'data' => $data,
            'meta_name' => L('短消息'),
			'result_error' => $error,
		));
		$this->template->display('pm_send.html');
    }
	
	/**
     * 阅读消息页
     */
    public function read() {
		
		$uid = (int)$this->input->get('uid');
		
		if ($this->input->get('action') == 'more') {
		    // ajax更多数据
			list($touid, $list) = $this->pm_model->read_limit_page($uid, max(1, (int)$this->input->get('page')));
			if (!$list) {
                exit('null');
            }
			$this->template->assign(array(
				'list' => $list
			));
			$this->template->display('pm_read_data.html');
			exit;
		}
		
		list($touid, $list) = $this->pm_model->read_limit_page($uid, max(1, (int)$this->input->get('page')));
		$username = get_member_value($touid);
		
		if (IS_POST) {
			$data = $this->input->post('data', TRUE);
			$data['username'] = $username;
			$error = $this->pm_model->send($this->uid, $this->member['username'], $data);
			if ($error === NULL) {
				$this->member_msg(L('操作成功，正在刷新...'), dr_member_url('pms/home/read', array('uid' => $uid)), 1);
			}
            if (IS_AJAX || IS_API_AUTH) {
                $this->msg(0, $error);
            }
		}
		
		$this->template->assign(array(
			'list' => $list,
			'username' => $username,
			'searchurl' => 'member.php?app=pms&c='.$this->router->class.'&m='.$this->router->method.'&uid='.$uid.'&plid='.$plid.'&action=more',
            'meta_name' => L('短消息'),
            'result_error' => $error,
		));
		$this->template->display('pm_read.html');
    }
	

}