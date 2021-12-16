<?php

use Yuanshe\WeChatSDK\WeChat;

trait WeChatInstance
{
    /**
     * @var WeChat
     */
    protected $wechat;
    private $wechatConfig;

    public function __construct()
    {
        parent::__construct();
        $ci = get_instance();
        $ci->config->load('wechat', true);
        $this->wechatConfig = $ci->config->item('wechat');
        require_once __DIR__ . '/WeChatCache.php';
        $this->wechat = new WeChat($this->wechatConfig, WeChatCache::class);
    }

    public function getConfig(string $name = null)
    {
        return $name ? ($this->wechatConfig[$name] ?? null) : $this->wechatConfig;
    }
}
