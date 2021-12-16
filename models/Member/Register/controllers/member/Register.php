<?php

class Register extends M_Controller
{
    /**
     * 注册
     */
    public function index()
    {
        // 会员配置
        $MEMBER = $this->get_cache('MEMBER');
        $groupid = (int) $this->input->get('groupid');
        $groupid && $groupid == 3 && ($groupid = 0);
        if (!$MEMBER['setting']['register']) {
            $this->member_msg(L('站点已经关闭了会员注册'));
        } elseif ($groupid && !$MEMBER['group'][$groupid]['allowregister']) {
            $this->member_msg(L('此会员组模型（%s）系统不允许注册', $groupid));
        } elseif ($this->member) {
            $this->member_msg(L('您已经登录了，不能注册'));
        }
        if (IS_POST) {
            $data = $this->input->post('data', TRUE);
            $backurl = urldecode($this->input->get('backurl'));
            if (!$backurl) {
                $backurl = dr_member_url('home/index');
            }
            if ($MEMBER['setting']['regcode'] && !$this->check_captcha('code')) {
                $error = L('验证码不正确');
            } elseif (@in_array('username', $MEMBER['setting']['regfield']) && ($result = $this->is_username($data['username']))) {
                $error = $result;
            } elseif (!$data['password']) {
                $error = L('密码不能为空');
            } elseif ($data['password'] != $data['confirm']) {
                $error = L('两次密码输入不一致');
            } elseif (@in_array('email', $MEMBER['setting']['regfield']) && ($result = is_email($data['email']))) {
                $error = array('name' => 'email', 'msg' => $result);
            } else {
                $code = $this->models('member/register')->add($data, $groupid);
                if ($code > 0) {
                    $this->json(1, L('注册成功'));
                } else {
                    $error = $this->models('member/register')->error_msg($code);
                }
            }
            $this->json(0, $error);
        }
        $this->render(array('code' => $MEMBER['setting']['regcode'], 'backurl' => $backurl, 'regfield' => $MEMBER['setting']['regfield'], 'meta_title' => L('会员注册')), 'register.html');
    }
    
    public function phone()
    {
        $phone = trim($_POST['phone']);
        $sms_code = trim($_POST['sms_code']);
        $password = trim($_POST['password']);
        $invite = trim($_POST['invite']);
        // 会员配置
        $MEMBER = $this->get_cache('MEMBER');
        $groupid = (int) $this->input->get('groupid');
        $groupid && $groupid == 3 && ($groupid = 0);
        if (!$MEMBER['setting']['register']) {
            $this->json(0, '站点已经关闭了会员注册');
        } elseif ($groupid && !$MEMBER['group'][$groupid]['allowregister']) {
            $this->json(0, L('此会员组模型（%s）系统不允许注册', $groupid));
        } elseif ($this->member) {
            $this->json(0, '您已经登录了，不能注册');
        } elseif (!$phone || !$sms_code || !$password) {
            $this->json(0, '请完整填写注册信息');
        } elseif (strlen($password) < 6) {
            $this->json(0, '请使用6位以上的密码');
        } elseif ($this->models('system/sms')->getCaptcha($phone, 'register') != $sms_code) {
            $this->json(0, '短信验证码错误');
        } elseif ($this->models('member')->getByPhone($phone)) {
            $this->json(0, '手机号已被注册');
        } else {
            $code = $this->models('member/register')->add(['phone' => $phone, 'password' => $password, 'invite' => $invite], $groupid);
            if ($code > 0) {
                $this->models('system/sms')->deleteCaptcha($phone, 'register');
                $this->json(1, L('注册成功'));
            } else {
                $this->json(0, $this->models('member/register')->error_msg($code));
            }
        }
    }
    /**
     * 验证会员名称
     *
     * @param    string $username
     * @return    NULL
     */
    protected function is_username($username)
    {
        if (!$username) {
            return L('请填写登录账号');
        }
        $setting = $this->get_cache('member', 'setting');
        if ($setting['regnamerule'] && !preg_match($setting['regnamerule'], $username)) {
            return L('会员名称格式不正确');
        }
        if ($setting['regnotallow'] && @in_array($username, explode(',', $setting['regnotallow']))) {
            return L('该会员名称系统禁止注册');
        }
        return NULL;
    }
}