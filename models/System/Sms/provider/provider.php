<?php

abstract class System_Sms_provider extends CI_Model
{
    abstract public function templateSend($phone, array $params, $template_id = null, $sign_name = null);
}
