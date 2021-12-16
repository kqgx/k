<?php

use Yuanshe\WeChatSDK\CacheInterface;

class WeChatCache implements CacheInterface
{
    protected $prefix;
    protected $ci;

    /**
     * CacheAbstract constructor.
     * @param string $prefix 缓存名称的前缀，请务必确保前缀的唯一性，避免与项目中其他缓存冲突
     */
    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
        $this->ci = get_instance();
    }

    /**
     * 获取一条缓存数据
     * @param string $name 缓存名称
     * @return mixed 返回缓存数据，若缓存不存在或已过期则返回null
     */
    public function get(string $name)
    {
        return $this->ci->cache->get($name);
    }

    /**
     * 写入一条缓存
     * @param string $name 缓存名称
     * @param mixed $value 缓存内容
     * @param int $seconds 有效时长（秒）
     * @return bool 成功返回true，失败返回false
     */
    public function put(string $name, $value, int $seconds): bool
    {
        return $this->ci->cache->save($name, $value, $seconds);
    }

    /**
     * 删除一条缓存
     * @param string $name 缓存名称
     * @return bool 成功返回true，失败返回false
     */
    public function del(string $name): bool
    {
        return $this->ci->cache->delete($name);
    }

    protected function getFullName(string $name): string
    {
        return "wechat-sdk-$this->prefix-$name";
    }
}