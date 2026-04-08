<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'app_id'           => env("ZEGO_APP_ID", '1234567890'),
    /** Token04 与实时音视频服务端 API 共用的 ServerSecret（控制台项目配置） */
    'secret'           => env("ZEGO_SECRET", 'fa94dd0f974cf2e293728a526b028271'),
    /** 服务端 API 接入域名，见「调用方式」接入地址表 */
    'rtc_api_base_url' => env('ZEGO_RTC_API_BASE_URL', 'https://rtc-api.zego.im'),
    /**
     * 是否测试环境 IsTest。老项目需填 true/false；新项目可保持 null 表示不传该参数。
     * @var bool|string|null
     */
    'is_test'          => null,
    /** 服务端回调校验密钥 callbacksecret（退出房间等回调 SHA1 验签） */
    'callback_secret'  => env('ZEGO_CALLBACK_SECRET', ''),
];
