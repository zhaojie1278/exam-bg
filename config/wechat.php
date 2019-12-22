<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

return [
    // 微信开放平台接口
    'service_url' => 'https://demo.thinkadmin.top',
    // 小程序支付参数
    'miniapp'     => [
        //'appid'      => 'wx42801a4f1f7f79ac',
        //'appsecret'  => '8ad3e958c62ed6c789534fbabe09e01a',
        'appid'      => 'wx3ce434c0beed5864',
        'appsecret'  => '02eb4d0598cf790e163427db4a3baeca',
        'mch_id'     => '1332187001',
        'mch_key'    => 'A82DC5BD1F3359081049C568D8502BC5',
        'ssl_p12'    => __DIR__ . '/cert/1332187001_20181030_cert.p12',
        'cache_path' => env('runtime_path') . 'wechat' . DIRECTORY_SEPARATOR,
    ],
];
