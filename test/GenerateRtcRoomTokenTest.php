<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use ZEGO\ZegoServerAssistant;
use ZEGO\ZegoErrorCodes;

class GenerateRtcRoomTokenTest extends TestCase
{
    // 权限位定义
    const PrivilegeKeyLogin   = 1; // 登录的权限
    const PrivilegeKeyPublish = 2; // 推流的权限

    // 权限开关定义
    const PrivilegeEnable     = 1; // 开启
    const PrivilegeDisable    = 0; // 关闭

    public function testGenerateRtcRoomToken()
    {
        // 请将 appID 修改为你的 appId，appid 为 数字
        // 举例：1234567890
        $appId = 1234567890;

        // 请将 serverSecret 修改为你的 serverSecret，serverSecret 为 string
        // 举例：'fa94dd0f974cf2e293728a526b028271'
        $serverSecret = 'fa94dd0f974cf2e293728a526b028271';

        // 请将 userId 修改为用户的 userId
        $userId = 'user1';

        $roomId = "room1";

        $rtcRoomPayLoad = [
            'room_id' => $roomId, //房间id；用于对接口的房间id进行强验证
            'privilege' => [     //权限位开关列表；用于对接口的操作权限进行强验证
                self::PrivilegeKeyLogin => self::PrivilegeEnable, //表示允许登录
                self::PrivilegeKeyPublish => self::PrivilegeDisable,//表示不允许推流
            ],
            'stream_id_list' => [] //流列表；用于对接口的流id进行强验证；允许为空，如果为空，则不对流id验证
        ];

        $payload = json_encode($rtcRoomPayLoad);

        // 3600 为 token 过期时间，单位：秒
        $token = ZegoServerAssistant::generateToken04($appId, $userId, $serverSecret, 3600, $payload);

        $this->assertEquals(ZegoErrorCodes::success, $token->code);
    }
}
