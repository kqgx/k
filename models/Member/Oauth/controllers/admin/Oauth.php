<?php

class OAuth extends M_Controller {
    
	public function setting() {

		$oauth = array('qq' => 'QQ', 'sina' => '微博', 'weixin' => '微信'); //
		$this->load->library('dconfig');
		$config = require CONFPATH.'oauth.php';

		if (IS_POST) {
			$cfg = array();
			$data = $this->input->post('data');
			foreach ($oauth as $i => $name) {
				$cfg[$i] = array(
					'key' => trim($data['key'][$i]),
					'use' => isset($data['use'][$i]) ? 1 : 0,
					'name' => $config[$i]['name'] ? $config[$i]['name'] : $name,
					'icon' => $config[$i]['icon'] ? $config[$i]['icon'] : $i,
					'secret' => trim($data['secret'][$i])
				);
			}
			$this->dconfig->file(CONFPATH.'oauth.php')->note('OAuth2授权登录')->to_require($cfg);
			$config = $cfg;
            $this->system_log('快捷登录配置'); // 记录日志
			$this->template->assign('result', L('配置文件更新成功'));
		}

		$this->template->assign(array(
			'menu' => $this->get_menu_v3(array(
				'Oauth' => array('member/oauth/setting', 'weibo'),
			)),
			'data' => $config,
			'Oauth' => $oauth
		));
		$this->template->display();
	}

}