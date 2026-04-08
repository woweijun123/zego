# Hyperf Zego 服务端助手

本项目是基于 Hyperf 框架的 Zego 服务端助手库，提供了生成 Zego 令牌的功能，支持普通令牌和强验证令牌的生成。

## 安装

### 方法一：通过 Composer 安装

1. 在你的 Hyperf 项目中执行以下命令：

```bash
composer require riven/hyperf-zego
```

2. 发布配置文件：

```bash
php bin/hyperf.php vendor:publish riven/hyperf-zego
```

### 方法二：手动安装

1. 拷贝文件包至项目根目录下的 `vendor/riven/hyperf-zego` 目录
2. 执行 `composer dump-autoload` 命令生成自动加载文件
3. 发布配置文件：

```bash
php bin/hyperf.php vendor:publish riven/hyperf-zego
```

## 配置

发布配置文件后，你可以在 `config/autoload/zego.php` 文件中配置 Zego 的参数：

```php
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
```

你也可以在 `.env` 文件中设置环境变量：

```
ZEGO_APP_ID=xxx
ZEGO_SECRET=xxx
ZEGO_RTC_API_BASE_URL=https://rtc-api.zego.im
ZEGO_CALLBACK_SECRET=xxx
```

## 使用

### 普通令牌生成

普通令牌用于服务接口的简单权限验证场景，payload 字段可传空。

```php
<?php

use ZEGO\ZegoErrorCodes;
use ZEGO\ZegoServerAssistant;
use Hyperf\Utils\ApplicationContext;

// 从容器中获取配置
$config = ApplicationContext::getContainer()->get(\Hyperf\Contract\ConfigInterface::class);
$appId = (int) $config->get('zego.app_id');
$secret = $config->get('zego.secret');

$userId = 'demo';
$payload = '';

// 生成令牌，3600 为令牌过期时间，单位：秒
$token = ZegoServerAssistant::generateToken04($appId, $userId, $secret, 3600, $payload);

if ($token->code == ZegoErrorCodes::success) {
    echo "生成的令牌：" . $token->token . "\n";
} else {
    echo "生成令牌失败：" . $token->message . "\n";
}
```

### 强验证令牌生成

强验证令牌用于对房间登录/推拉流权限需要进行强验证的场景，payload 字段需要按照规格生成。

```php
<?php

use ZEGO\ZegoErrorCodes;
use ZEGO\ZegoServerAssistant;
use Hyperf\Utils\ApplicationContext;

// 权限位定义
const PrivilegeKeyLogin   = 1; // 登录
const PrivilegeKeyPublish = 2; // 推流

// 权限开关定义
const PrivilegeEnable     = 1; // 开启
const PrivilegeDisable    = 0; // 关闭

// 从容器中获取配置
$config = ApplicationContext::getContainer()->get(\Hyperf\Contract\ConfigInterface::class);
$appId = (int) $config->get('zego.app_id');
$secret = $config->get('zego.secret');

$userId = 'demo';
$roomId = "demo";

// 构建 payload
$rtcRoomPayLoad = [
    'room_id' => $roomId, // 房间id；用于对接口的房间id进行强验证
    'privilege' => [     // 权限位开关列表；用于对接口的操作权限进行强验证
        PrivilegeKeyLogin => PrivilegeEnable,
        PrivilegeKeyPublish => PrivilegeDisable,
    ],
    'stream_id_list' => [] // 流列表；用于对接口的流id进行强验证；允许为空，如果为空，则不对流id验证
];

$payload = json_encode($rtcRoomPayLoad);

// 生成令牌，3600 为令牌过期时间，单位：秒
$token = ZegoServerAssistant::generateToken04($appId, $userId, $secret, 3600, $payload);

if ($token->code == ZegoErrorCodes::success) {
    echo "生成的令牌：" . $token->token . "\n";
} else {
    echo "生成令牌失败：" . $token->message . "\n";
}
```

## 错误码说明

| 错误码 | 说明 |
|-------|------|
| 0 | 获取鉴权 token 成功 |
| 1 | 调用方法时传入 appID 参数错误 |
| 3 | 调用方法时传入 userID 参数错误 |
| 5 | 调用方法时传入 secret 参数错误 |
| 6 | 调用方法时传入 effectiveTimeInSeconds 参数错误 |

## API 说明

### generateToken04 方法

```php
/**
 * 根据所提供的参数列表生成用于与即构服务端通信的鉴权
 *
 * @param int $appId Zego派发的数字ID, 各个开发者的唯一标识
 * @param string $userId 用户 ID
 * @param string $secret 由即构提供的与 appId 对应的密钥，请妥善保管，切勿外泄
 * @param int $effectiveTimeInSeconds token 的有效时长，单位：秒
 * @param string $payload 业务扩展字段，json串
 * @return ZegoAssistantToken 返回 token 内容，值为ZEGO\ZegoAssistantToken对象
 */
public static function generateToken04(int $appId, string $userId, string $secret, int $effectiveTimeInSeconds, string $payload)
```

### 返回值

返回值为 `ZEGO\ZegoAssistantToken` 对象：

```php
class ZegoAssistantToken {
    public $code;      // 错误码，0 表示成功
    public $message;   // 错误信息
    public $token;     // 生成的令牌
}
```

## 测试

本项目包含测试用例，你可以通过以下命令运行测试：

```bash
./vendor/bin/phpunit
```

测试用例会从配置文件中读取参数，验证令牌生成功能是否正常。

## 注意事项

1. 请妥善保管你的 `secret`，切勿外泄
2. 令牌有过期时间，请注意在过期前重新生成
3. 强验证令牌的 payload 格式必须按照规定生成，否则可能导致验证失败
4. 在生产环境中，建议将配置信息存储在环境变量中，而不是直接硬编码在配置文件中