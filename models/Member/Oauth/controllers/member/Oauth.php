<?php

class Oauth extends M_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('OAuth2');
    }


	public function login() {
	    $type = $this->input->get('type');
	    $this->_login($type);
	}

    public function _login($type) {
        switch ($type) {
            case 'wxapp':

                break;

            default:
                // code...
                break;
        }
        $oauth = $this->models('member/oauth')->get($openid, $provider);
        if ($oauth) {
            $user = $this->models('member')->get($oauth['uid']);
            if($user){
                $this->json();
            } else {
            }
        } else {

        }
    }

    public function oauth_appid() {
        $id = $this->input->get('id');
        if ($id) {
            $config = require CONFPATH.'oauth.php';
            if ($config[$id] && $config[$id]['key']) {
                $this->json(1, ['appid' => $config[$id]['key']]);
            } else {
                $this->json(0, '不支持的授权方式');
            }
        } else {
            $this->json(0, '参数错误');
        }
    }

    public function register() {
        $id = @['sinaweibo' => 'sina'][$_POST['provider']] ?: $_POST['provider'];
        $auth_access = json_decode($_POST['authAccess'], true);
        if (!$id || !is_array($auth_access)) {
            $this->json(0, '参数错误');
        }
        $config = @include CONFPATH.'oauth.php';
        $config	= $config[$id];
        if (!$config) {
            $this->json(0, '不支持的授权方式');
        }
        $oauth = $this->oauth2->provider($id, $config);
        try {
            $user = $oauth->get_user_info($oauth->tokenFactory('access', $auth_access));
            if (is_array($user) && $user['oid']) {
                $member = null;
                $this->models('member/oauth')->oauth($id, $user, $member);
                if (!$member) {
                    $this->json(0, '授权失败，请重试');
                } else {
                    $this->json(1, $this->models('member/login')->authcode($member['uid']), '授权成功');
                }
            } else {
                $this->json(0, '页面已过期，请重新授权');
            }
        } catch (OAuth2_Exception $e) {
            $this->json(0, $auth_access, '获取授权信息失败，请重试:' . $e->getMessage());
        } catch (Exception $e) {
            $this->json(0, $e->getMessage());
        }
    }
}
