<?php

/* v3.1.0  */
 
class Home extends M_Controller {


    /**
     * 首页
     */
    public function index() {
        $this->_indexc();
    }

    // 在线留言
    public function feedback()
    {
        $post = $this->input->post();
        if(empty($post['name']) || empty($post['content'])) {
            x_json(0, '请完善信息');
        }
        if(!is_valid('phone', $post['phone'])) {
            x_json(0, '手机号格式不正确');
        }

        if(!is_valid('email', $post['email'])) {
            x_json(0, '邮箱格式不正确');
        }
        $data['uid'] = 0;
        $data['author'] = '游客';
        $data['title'] = $post['name'];
        $data['phone'] = $post['phone'];
        $data['mailbox'] = $post['email'];
        $data['content'] = $post['content'];
        $data['inputip'] = $this->input->ip_address();
        $data['inputtime'] = SYS_TIME;
        $this->models('site/form')->addc('feedback', $data);
        x_json(1, '提交成功');
    }
	
	public function test()
	{
		$this->hooks->call_hook('a', [1,2,3,4,5]);
	}
}
