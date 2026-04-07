<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use ZEGO\ZegoServerAssistant;
use ZEGO\ZegoErrorCodes;

class GenerateBasicTokenTest extends TestCase
{
    public function testGenerateToken()
    {
        // 请将 appID 修改为你的 appId，appid 为 数字
        // 举例：1234567890
        $appId = 1234567890;

        // 请将 serverSecret 修改为你的 serverSecret，serverSecret 为 string
        // 举例：'fa94dd0f974cf2e293728a526b028271'
        $serverSecret = 'fa94dd0f974cf2e293728a526b028271';

        // 请将 userId 修改为用户的 userId
        $userId = 'user1';

        // 生成基础鉴权 token 时，payload 要设为空字符串
        $payload = '';

        // 3600 为 token 过期时间，单位：秒
        $token = ZegoServerAssistant::generateToken04($appId, $userId, $serverSecret, 3600, $payload);

        $this->assertEquals(ZegoErrorCodes::success, $token->code);
    }
}
