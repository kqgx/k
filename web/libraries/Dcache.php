<?php

class Dcache
{
    public $cache_dir;
    
    public function __construct()
    {
        $this->cache_dir = DATAPATH . 'web/';
    }
    
    public function set_dir($dir = null)
    {
        $this->cache_dir = $dir ? '' : DATAPATH . 'web/';
    }
    
    private function parse_cache_file($file_name, $dir = null)
    {
        return ($dir ? DATAPATH . 'web/' . $dir . '/' : $this->cache_dir) . md5($file_name);
    }
    
    public function set($key, $value, $dir = null)
    {
        if (!$key) {
            return false;
        }
        $cache_file = $this->parse_cache_file($key, $dir);
        $value = !is_array($value) ? serialize($value) : serialize($value);
        $cache_dir = $dir ? DATAPATH . 'web/' . $dir . '/' : $this->cache_dir;
        if (!is_dir($cache_dir)) {
            file_mkdirs($cache_dir, 0777);
        } else {
            if (!is_writeable($cache_dir)) {
                @chmod($cache_dir, 0777);
            }
        }
        return @file_put_contents($cache_file, $value, LOCK_EX) ? true : false;
    }

    public function get($key, $dir = null)
    {
        if (!$key) {
            return false;
        }
        $cache_file = $this->parse_cache_file($key, $dir);
        return is_file($cache_file) ? @unserialize(@file_get_contents($cache_file)) : false;
    }

    public function delete($key)
    {
        if (!$key) {
            return true;
        }
        $cache_file = $this->parse_cache_file($key);
        return is_file($cache_file) ? @unlink($cache_file) : true;
    }
}