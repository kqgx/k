<?php

$config = [
    /***公众号开发基本信息***/
    'appid' => '',
    'app_secret' => '',
    /***服务器消息接口配置***/
    // 公众号帐号
    'account' => '',
    'token' => '',
    // 是否开启消息加密（公众号安全模式为兼容模式时此选项才有效，否则根据公众平台自身设置决定是否加密）
    'encrypt' => true,
    // EncodingAESKey，未开启消息加密时不必填写
    'ase_key' => '',
    /***其他***/
    // 是否启用SSL证书验证，生产环境下建议开启
    'ssl_verify' => true
];
