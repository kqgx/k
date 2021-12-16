<?php

use Yuanshe\WeChatSDK\WeChat;

class System_Wechat_model extends CI_Model {

    protected $wechat;

    public function __construct()
    {
        parent::__construct();
    }

    public function wechat(): Wechat
    {
        return $this->wechat;
    }
}
