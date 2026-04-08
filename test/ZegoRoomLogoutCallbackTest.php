<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\TestDox;
use Zego\ZegoRoomLogoutCallback;

class ZegoRoomLogoutCallbackTest extends TestCase
{
    #[TestDox('退出房间回调：EVENT 常量值为 room_logout')]
    public function testEventConstant(): void
    {
        $this->assertSame('room_logout', ZegoRoomLogoutCallback::EVENT);
    }

    #[TestDox('回调验签：与「接收回调」文档中的 SHA1 示例向量一致则通过')]
    public function testVerifySignatureMatchesReceivingCallbackDoc(): void
    {
        $ok = ZegoRoomLogoutCallback::verifySignature('5bd59fd62953a8059fb7eaba95720f66d19e4517', '1470820198', '123412', 'secret');
        $this->assertTrue($ok);
    }

    #[TestDox('回调验签：signature 与按密钥计算结果不一致时返回 false')]
    public function testVerifySignatureRejectsWrongSignature(): void
    {
        $this->assertFalse(ZegoRoomLogoutCallback::verifySignature('0000000000000000000000000000000000000000', '1470820198', '123412', 'secret'));
    }

    #[TestDox('回调验签：对比 signature 时十六进制字母大小写不敏感')]
    public function testVerifySignatureIsCaseInsensitiveForHex(): void
    {
        $this->assertTrue(ZegoRoomLogoutCallback::verifySignature(strtoupper('5bd59fd62953a8059fb7eaba95720f66d19e4517'), '1470820198', '123412', 'secret'));
    }

    #[TestDox('整体验签：event 为 room_logout 且签名正确通过；event 错误则失败')]
    public function testVerifyPayloadRequiresEventAndSignature(): void
    {
        $payload = ['event' => 'room_logout', 'signature' => '5bd59fd62953a8059fb7eaba95720f66d19e4517', 'timestamp' => '1470820198', 'nonce' => '123412',];
        $this->assertTrue(ZegoRoomLogoutCallback::verifyPayload($payload, 'secret'));
        $this->assertFalse(ZegoRoomLogoutCallback::verifyPayload(['event' => 'other'], 'secret'));
    }

    #[TestDox('整体验签：event 不是 room_logout 时返回 false')]
    public function testVerifyPayloadRejectsWrongEvent(): void
    {
        $payload = ['event' => 'room_login', 'signature' => '5bd59fd62953a8059fb7eaba95720f66d19e4517', 'timestamp' => '1470820198', 'nonce' => '123412',];
        $this->assertFalse(ZegoRoomLogoutCallback::verifyPayload($payload, 'secret'));
    }

    #[TestDox('整体验签：event 正确但 signature 错误时返回 false')]
    public function testVerifyPayloadRejectsBadSignatureWithCorrectEvent(): void
    {
        $payload = ['event' => 'room_logout', 'signature' => 'bad', 'timestamp' => '1470820198', 'nonce' => '123412',];
        $this->assertFalse(ZegoRoomLogoutCallback::verifyPayload($payload, 'secret'));
    }

    #[TestDox('载荷解析：fromArray 将 JSON 下划线字段映射到只读属性')]
    public function testFromArrayMapsSnakeCaseFields(): void
    {
        $row = ZegoRoomLogoutCallback::fromArray(
            [
                'event'           => 'room_logout',
                'appid'           => '1',
                'timestamp'       => '1499676978',
                'nonce'           => '350176',
                'signature'       => 'x',
                'room_id'         => 'rid1',
                'room_seq'        => '6085791336856668982',
                'user_account'    => 'u1',
                'user_nickname'   => 'n1',
                'session_id'      => '792462300429684736',
                'login_time'      => '1663740623660',
                'logout_time'     => '1663740627986',
                'reason'          => '0',
                'user_role'       => '1',
                'user_update_seq' => '13',
            ]
        );
        $this->assertSame('room_logout', $row->event);
        $this->assertSame('rid1', $row->roomId);
        $this->assertSame('u1', $row->userAccount);
    }

    #[TestDox('载荷解析：fromArray 在缺少字段时用空字符串填充')]
    public function testFromArrayUsesEmptyStringForMissingKeys(): void
    {
        $row = ZegoRoomLogoutCallback::fromArray([]);
        $this->assertSame('', $row->event);
        $this->assertSame('', $row->roomId);
        $this->assertSame('', $row->signature);
    }
}
