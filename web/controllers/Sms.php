<?php

class Sms extends M_Controller
{
    public function send_captcha()
    {
        $this->dcache->set();
        $type = $_POST['type'];
        $phone = $_POST['phone'];

        $types = ['register', 'change_password', 'login'];
        if (!preg_match('/^1\d{10}$/', $phone)) {
            $this->json(0, '请输入正确的手机号');
        } elseif (!in_array($type, $types)) {
            $this->json(0, '验证码类型不支持');
        } elseif (
            $captcha = $this->models('system/sms')->createCaptcha($phone, $type)
            and $this->models('system/sms')->provider('tencent')->templateSend($phone, [$captcha])
        ){
            $this->json(1, '发送成功');
        } else {
            $this->json(0, '操作繁忙');
        }
    }
}
