<?php
$config = [
    // 通用配置
    'global' => [
        // 请求选项(可选)
        'http' => [
            'timeout' => 5,
            'connect_timeout' => 5,
            'verify' => true // ssl验证，默认为true。生产环境下应开启
        ],
        'mode' => 'normal', // 设置为dev可进入沙箱模式
    ],
    // 支付宝配置
    'alipay' => [
        'app_id' => '',
        'ali_public_key' => '', // 支付宝公钥文本
        'private_key' => '', // 开发者私钥文本
        'return_url' => SITE_URL . 'pay/pay_return/type/alipay',
        'notify_url' => SITE_URL . 'pay/notify/type/alipay',
        // 日志选项(可选)
        'log' => [
            'file' => LOGPATH . 'pay/alipay.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30 // optional, 当 type 为 daily 时有效，默认 30 天
        ]
    ],
    // 微信支付配置
    'wechat' => [
        'appid' => 'wx0b29f21130b5a205', // 开放平台APPID
        'app_id' => 'wx0b29f21130b5a205', // 公众平台APPID
        'miniapp_id' => 'wx0b29f21130b5a205', // 小程序APPID
        'mch_id' => '1611737279',
        'key' => '810c79f59c2bbed149b668784b66b9a1',
        'cert_client' => '',
        'cert_key' => '',
        'notify_url' => SITE_URL . 'pay/notify/type/wechat',
        // 日志选项(同支付宝)
        'log' => [
            'file' => LOGPATH . 'pay/wechat.log',
            'level' => 'info',
            'type' => 'single',
            'max_file' => 30
        ]
    ]
];
