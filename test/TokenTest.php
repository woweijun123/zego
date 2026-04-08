<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\TestDox;
use Zego\ZegoErrorCodes;
use Zego\ZegoServerAssistant;

use function Hyperf\Config\config;

class TokenTest extends TestCase
{
    // 权限位定义
    const PrivilegeKeyLogin   = 1;                                                                                                          // 登录的权限
    const PrivilegeKeyPublish = 2;                                                                                                          // 推流的权限

    // 权限开关定义
    const PrivilegeEnable  = 1;                                                                                                             // 开启
    const PrivilegeDisable = 0;                                                                                                             // 关闭

    #[TestDox('基础鉴权：按配置用空 payload 生成 Token04 且返回成功')]
    public function testGenerateToken(): void
    {
        // 从配置文件中获取参数
        $appId        = (int)config('app_id');
        $serverSecret = config('secret');

        // 请将 userId 修改为用户的 userId
        $userId = 'user1';

        // 生成基础鉴权 token 时，payload 要设为空字符串
        $payload = '';

        // 3600 为 token 过期时间，单位：秒
        $token = ZegoServerAssistant::generateToken04($appId, $userId, $serverSecret, 3600, $payload);

        $this->assertEquals(ZegoErrorCodes::success, $token->code);
    }

    #[TestDox('房间强校验：按配置用房间权限 payload 生成 Token04 且返回成功')]
    public function testGenerateRtcRoomToken(): void
    {
        // 从配置文件中获取参数
        $appId        = (int)config('app_id');
        $serverSecret = config('secret');

        // 请将 userId 修改为用户的 userId
        $userId = 'user1';

        $roomId = "room1";

        $rtcRoomPayLoad = [
            // 房间id；用于对接口的房间id进行强验证
            'room_id'        => $roomId,
            // 权限位开关列表；用于对接口的操作权限进行强验证
            'privilege'      => [
                self::PrivilegeKeyLogin   => self::PrivilegeEnable, // 表示允许登录
                self::PrivilegeKeyPublish => self::PrivilegeDisable,// 表示不允许推流
            ],
            'stream_id_list' => [], // 流列表；用于对接口的流id进行强验证；允许为空，如果为空，则不对流id验证
        ];

        $payload = json_encode($rtcRoomPayLoad);

        // 3600 为 token 过期时间，单位：秒
        $token = ZegoServerAssistant::generateToken04($appId, $userId, $serverSecret, 3600, $payload);

        $this->assertEquals(ZegoErrorCodes::success, $token->code);
    }
}
