<?php

use Qcloud\Sms\SmsSingleSender;

require_once __DIR__ . '/../provider.php';
require_once __DIR__ . '/TencentSMS/index.php';

class System_sms_provider_tencent_model extends System_Sms_provider
{
    private $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = include __DIR__ . '/conf.php';
    }

    public function templateSend($phone, array $params, $template_id = null, $sign_name = null)
    {
        try {
            $sender = new SmsSingleSender($this->config['appid'], $this->config['appkey']);
            $result = $sender->sendWithParam("86", $phone, $template_id ?: $this->config['template_id'], $params,
                $sign_name ?: $this->config['sign'], "", "");
            return json_decode($result, true);
        } catch (\Exception $e) {
            return false;
        }
    }
}
