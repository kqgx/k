<?php

class System_Sms_model extends CI_Model
{

    public function provider($provider)
    {
        $filename = __DIR__ . '/provider/' . ucfirst($provider) . '/model.php';
        if (is_file($filename)) {
            require_once $filename;
            $class_name = 'System_sms_provider_' . $provider . '_model';
            return new $class_name;
        } else {
            return false;
        }
    }

    public function getCaptcha($phone, $type)
    {
        return @$this->cache->file->get("captcha-$type-$phone")['code'];
    }

    public function createCaptcha($phone, $type, $limit = 60, $ttl = 300)
    {
        $old_data = $this->cache->file->get("captcha-$type-$phone");
        if ($old_data && SYS_TIME < $old_data['next_send_time']) {
            return false;
        } else {
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->cache->file->save("captcha-$type-$phone", [
                'code' => $code,
                'next_send_time' => SYS_TIME + $limit
            ], $ttl);
            return $code;
        }
    }

    public function deleteCaptcha($phone, $type)
    {
        return $this->cache->file->delete("captcha-$type-$phone");
    }
}
