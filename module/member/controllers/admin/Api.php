<?php
 
class Api extends M_Controller {
    
     // 登录授权
    public function ologin() {

        $uid = (int)$this->input->get('uid');

        // 注销上一个会员
        if ($this->session->userdata('member_auth_uid')) {
            $this->session->set_userdata('member_auth_uid', 0);
            redirect(dr_url('admin/member/api/ologin', array('uid' => $uid)), 'refresh');
        }

        // 未登录的情况下
        !$this->member && $this->admin_msg(L('务必要在会员中心登录一次,才能进行授权登录'), MEMBER_URL);

        // 非管理员无权操作
        $this->member['adminid'] != 1 && $this->admin_msg($this->member['username'].'：'.L('您无权限操作'));

        $this->uid != $uid && $this->session->set_userdata('member_auth_uid', $uid);

        $go = $this->input->get('go');
        $go = $go ? $go : MEMBER_URL;
        $this->template->assign('meta_name', L('登录成功'));

        $this->admin_msg(L('授权登录成功，正在跳转到会员中心，请稍后...'), $go, 2);
    }   
    
	public function info() {

        $uid = str_replace('author_', '', $this->input->get('uid'));
        ($uid == 'guest' || !$uid) && exit('<div style="padding-top:50px;color:blue;font-size:14px;text-align:center">'.L('游客').'</div>');
        
        $data = is_numeric($uid) ? $this->db->where('uid', (int)$uid)->limit(1)->get('member')->row_array() : $this->db->where('username', $uid)->limit(1)->get('member')->row_array();

        !$data && exit('(#'.$uid.')'.L('对不起，该会员不存在！'));

        $this->load->library('dip');
        $data['address'] = $this->dip->address($data['regip']);

		$this->template->assign(array(
			'data' => $data,
		));
		$this->template->display('member_info.html');
	}
}