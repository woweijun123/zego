<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\TestDox;
use Zego\ZegoAssistantToken;
use Zego\ZegoErrorCodes;
use Zego\ZegoServerAssistant;

/**
 * {@see ZegoServerAssistant::generateToken04} 各分支，便于调试鉴权 token 生成问题
 */
class ZegoServerAssistantGenerateToken04Test extends TestCase
{
    private const VALID_SECRET = 'fa94dd0f974cf2e293728a526b028271';

    #[TestDox('Token04：空 payload 时生成成功且 token 以 04 开头')]
    public function testSuccessWithEmptyPayload(): void
    {
        $t = ZegoServerAssistant::generateToken04(1, 'user1', self::VALID_SECRET, 3600, '');
        $this->assertInstanceOf(ZegoAssistantToken::class, $t);
        $this->assertSame(ZegoErrorCodes::success, $t->code);
        $this->assertIsString($t->token);
        $this->assertStringStartsWith('04', $t->token);
        $this->assertGreaterThan(20, strlen($t->token));
    }

    #[TestDox('Token04：appId 为 0 时返回 appIDInvalid')]
    public function testAppIdZeroReturnsAppIDInvalid(): void
    {
        $t = ZegoServerAssistant::generateToken04(0, 'u', self::VALID_SECRET, 3600, '');
        $this->assertSame(ZegoErrorCodes::appIDInvalid, $t->code);
        $this->assertStringContainsString('appID', $t->message);
    }

    #[TestDox('Token04：userId 为空字符串时返回 userIDInvalid')]
    public function testEmptyUserIdReturnsUserIDInvalid(): void
    {
        $t = ZegoServerAssistant::generateToken04(1, '', self::VALID_SECRET, 3600, '');
        $this->assertSame(ZegoErrorCodes::userIDInvalid, $t->code);
    }

    #[TestDox('Token04：secret 长度不是 32 字节时返回 secretInvalid')]
    public function testSecretWrongLengthReturnsSecretInvalid(): void
    {
        $t = ZegoServerAssistant::generateToken04(1, 'u', 'tooshort', 3600, '');
        $this->assertSame(ZegoErrorCodes::secretInvalid, $t->code);
    }

    #[TestDox('Token04：有效时长为 0 或负数时返回 effectiveTimeInSecondsInvalid')]
    public function testEffectiveTimeNonPositiveReturnsInvalid(): void
    {
        $t = ZegoServerAssistant::generateToken04(1, 'u', self::VALID_SECRET, 0, '');
        $this->assertSame(ZegoErrorCodes::effectiveTimeInSecondsInvalid, $t->code);

        $t2 = ZegoServerAssistant::generateToken04(1, 'u', self::VALID_SECRET, -1, '');
        $this->assertSame(ZegoErrorCodes::effectiveTimeInSecondsInvalid, $t2->code);
    }

    #[TestDox('Token04：传入房间权限类 JSON payload 时生成成功')]
    public function testSuccessWithJsonPayload(): void
    {
        $payload = json_encode(['room_id' => 'r1', 'privilege' => [1 => 1], 'stream_id_list' => []], JSON_THROW_ON_ERROR);
        $t       = ZegoServerAssistant::generateToken04(1, 'user1', self::VALID_SECRET, 60, $payload);
        $this->assertSame(ZegoErrorCodes::success, $t->code);
        $this->assertStringStartsWith('04', (string)$t->token);
    }
}
