<?php

require_once __DIR__ . '/../provider.php';
require_once __DIR__ . '/Mdysms.php';

class System_sms_provider_alidayu_model extends System_Sms_provider
{
    private $mdysms;

    public function __construct()
    {
        parent::__construct();
        $this->mdysms = new Mdysms();
    }

    public function templateSend($phone, array $params, $template_id = null, $sign_name = null)
    {
        // TODO: Implement sendTemplate() method.
        return $this->mdysms->sendSms($phone, $params, $template_id, $sign_name = null);
    }
}
