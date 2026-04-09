<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\TestDox;
use Zego\ZegoRtcServerCallback;

class ZegoRtcServerCallbackTest extends TestCase
{
    private static function sign(string $callbackSecret, string $timestamp, string $nonce): string
    {
        $tmp = [$callbackSecret, $timestamp, $nonce];
        sort($tmp, SORT_STRING);

        return sha1(implode('', $tmp));
    }

    #[TestDox('事件常量与 KNOWN_EVENTS 包含文档中的六种回调')]
    public function testKnownEventsAndConstants(): void
    {
        $expected = [
            ZegoRtcServerCallback::EVENT_STREAM_CREATE,
            ZegoRtcServerCallback::EVENT_STREAM_CLOSE,
            ZegoRtcServerCallback::EVENT_ROOM_LOGIN,
            ZegoRtcServerCallback::EVENT_ROOM_LOGOUT,
            ZegoRtcServerCallback::EVENT_ROOM_CREATE,
            ZegoRtcServerCallback::EVENT_ROOM_CLOSE,
        ];
        $this->assertSame($expected, ZegoRtcServerCallback::KNOWN_EVENTS);
        $this->assertSame('stream_create', ZegoRtcServerCallback::EVENT_STREAM_CREATE);
        $this->assertSame('stream_close', ZegoRtcServerCallback::EVENT_STREAM_CLOSE);
        $this->assertSame('room_login', ZegoRtcServerCallback::EVENT_ROOM_LOGIN);
        $this->assertSame('room_logout', ZegoRtcServerCallback::EVENT_ROOM_LOGOUT);
        $this->assertSame('room_create', ZegoRtcServerCallback::EVENT_ROOM_CREATE);
        $this->assertSame('room_close', ZegoRtcServerCallback::EVENT_ROOM_CLOSE);
    }

    #[TestDox('验签：与「接收回调」文档 SHA1 示例向量一致则通过')]
    public function testVerifySignatureMatchesReceivingCallbackDoc(): void
    {
        $ok = ZegoRtcServerCallback::verifySignature('5bd59fd62953a8059fb7eaba95720f66d19e4517', '1470820198', '123412', 'secret');
        $this->assertTrue($ok);
    }

    #[TestDox('验签：signature 错误时返回 false')]
    public function testVerifySignatureRejectsWrongSignature(): void
    {
        $this->assertFalse(ZegoRtcServerCallback::verifySignature('0000000000000000000000000000000000000000', '1470820198', '123412', 'secret'));
    }

    #[TestDox('验签：十六进制字母大小写不敏感')]
    public function testVerifySignatureIsCaseInsensitiveForHex(): void
    {
        $this->assertTrue(ZegoRtcServerCallback::verifySignature(strtoupper('5bd59fd62953a8059fb7eaba95720f66d19e4517'), '1470820198', '123412', 'secret'));
    }

    #[TestDox('整体验签：未指定事件时，KNOWN_EVENTS 内且签名正确则通过')]
    public function testVerifyPayloadWithoutExpectedEventAcceptsKnownEvents(): void
    {
        $sig = self::sign('secret', '1470820198', '123412');
        foreach (ZegoRtcServerCallback::KNOWN_EVENTS as $event) {
            $payload = ['event' => $event, 'signature' => $sig, 'timestamp' => '1470820198', 'nonce' => '123412'];
            $this->assertTrue(ZegoRtcServerCallback::verifyPayload($payload, 'secret'), 'event=' . $event);
        }
    }

    #[TestDox('整体验签：未指定事件时，未知 event 返回 false')]
    public function testVerifyPayloadWithoutExpectedEventRejectsUnknownEvent(): void
    {
        $sig     = self::sign('secret', '1470820198', '123412');
        $payload = ['event' => 'unknown_event', 'signature' => $sig, 'timestamp' => '1470820198', 'nonce' => '123412'];
        $this->assertFalse(ZegoRtcServerCallback::verifyPayload($payload, 'secret'));
    }

    #[TestDox('整体验签：指定 expectedEvent 时 event 不一致则失败')]
    public function testVerifyPayloadWithExpectedEventRejectsMismatch(): void
    {
        $sig     = self::sign('secret', '1470820198', '123412');
        $payload = ['event' => 'room_login', 'signature' => $sig, 'timestamp' => '1470820198', 'nonce' => '123412'];
        $this->assertFalse(ZegoRtcServerCallback::verifyPayload($payload, 'secret', ZegoRtcServerCallback::EVENT_ROOM_LOGOUT));
    }

    #[TestDox('整体验签：指定 expectedEvent 且匹配、签名正确则通过')]
    public function testVerifyPayloadWithExpectedEventWhenMatch(): void
    {
        $sig     = self::sign('secret', '1470820198', '123412');
        $payload = ['event' => 'room_logout', 'signature' => $sig, 'timestamp' => '1470820198', 'nonce' => '123412'];
        $this->assertTrue(ZegoRtcServerCallback::verifyPayload($payload, 'secret', ZegoRtcServerCallback::EVENT_ROOM_LOGOUT));
    }

    #[TestDox('整体验签：签名错误时返回 false')]
    public function testVerifyPayloadRejectsBadSignature(): void
    {
        $payload = ['event' => 'room_logout', 'signature' => 'bad', 'timestamp' => '1470820198', 'nonce' => '123412'];
        $this->assertFalse(ZegoRtcServerCallback::verifyPayload($payload, 'secret'));
    }

    #[TestDox('fromArray：退出房间回调字段映射与 isRoomLogout')]
    public function testFromArrayRoomLogout(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
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
        $this->assertTrue($row->isRoomLogout());
        $this->assertFalse($row->isRoomLogin());
        $this->assertSame('room_logout', $row->event);
        $this->assertSame('rid1', $row->roomId);
        $this->assertSame('u1', $row->userAccount);
        $this->assertSame('1663740627986', $row->logoutTime);
    }

    #[TestDox('fromArray：登录房间回调与 isRoomLogin')]
    public function testFromArrayRoomLogin(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
            [
                'event'           => 'room_login',
                'appid'           => '1',
                'timestamp'       => '1499676978',
                'nonce'           => '350176',
                'signature'       => 'x',
                'room_id'         => 'rid_1242649',
                'room_name'       => 'room#123',
                'room_seq'        => '6085791336856668982',
                'user_account'    => '888120154',
                'user_nickname'   => '888120154',
                'session_id'      => '792148503087288320',
                'login_time'      => '1499676978027',
                'user_role'       => '2',
                'auth_level'      => '4',
                'relogin'         => '0',
                'user_update_seq' => '1',
                'callback_data'   => 'user login',
            ]
        );
        $this->assertTrue($row->isRoomLogin());
        $this->assertSame('room#123', $row->roomName);
        $this->assertSame('4', $row->authLevel);
        $this->assertSame('user login', $row->callbackData);
    }

    #[TestDox('fromArray：流创建回调、拉流 URL 列表与 isStreamCreate')]
    public function testFromArrayStreamCreate(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
            [
                'event'           => 'stream_create',
                'appid'           => '1',
                'timestamp'       => '1687981272',
                'nonce'           => '7254119327986670314',
                'signature'       => 'x',
                'room_id'         => 'room1',
                'room_session_id' => '1234567',
                'user_id'         => 'user1',
                'user_name'       => 'user1_name',
                'channel_id'      => '0xb-0x1',
                'stream_id'       => 'stream_id',
                'stream_sid'      => 's-115205136669740000000000104',
                'title'           => 'title',
                'stream_alias'    => 'aaa',
                'stream_attr'     => '{"cid":0}',
                'stream_seq'      => '01',
                'create_time'     => '1687981272',
                'create_time_ms'  => '1687981272742',
                'extra_info'      => 'extra',
                'recreate'        => '0',
                'publish_id'      => 'publish',
                'publish_name'    => 'publish_name',
                'rtmp_url'        => ['rtmp://a/x', 'rtmp://b/x'],
                'hls_url'         => ['http://hls/a.m3u8'],
                'hdl_url'         => [],
                'pic_url'         => ['http://pic/a.jpg'],
            ]
        );
        $this->assertTrue($row->isStreamCreate());
        $this->assertSame(['rtmp://a/x', 'rtmp://b/x'], $row->rtmpUrl);
        $this->assertSame(['http://hls/a.m3u8'], $row->hlsUrl);
        $this->assertSame([], $row->hdlUrl);
        $this->assertSame(['http://pic/a.jpg'], $row->picUrl);
        $this->assertSame('aaa', $row->streamAlias);
    }

    #[TestDox('fromArray：流关闭回调、type 映射 streamCloseType 与 isStreamClose')]
    public function testFromArrayStreamClose(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
            [
                'event'              => 'stream_close',
                'appid'              => '1',
                'timestamp'          => '1666786067',
                'nonce'              => '7266888922840654370',
                'signature'          => 'x',
                'room_id'            => 'room1',
                'room_session_id'    => '123456789',
                'user_id'            => 'user1',
                'user_name'          => 'user1_name',
                'channel_id'         => '0xb-0x1',
                'type'               => '0',
                'stream_alias'       => 'aaa',
                'stream_id'          => 'stream_id',
                'stream_sid'         => 's-115205136669740000000000104',
                'stream_seq'         => '700',
                'third_define_data'  => '{"u":"x"}',
                'create_time_ms'     => '1666786067353',
                'destroy_timemillis' => '1666986067248',
            ]
        );
        $this->assertTrue($row->isStreamClose());
        $this->assertSame('0', $row->streamCloseType);
        $this->assertSame('1666986067248', $row->destroyTimemillis);
    }

    #[TestDox('fromArray：房间创建回调、数值型 appid/timestamp/session 转字符串与 isRoomCreate')]
    public function testFromArrayRoomCreate(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
            [
                'event'            => 'room_create',
                'appid'            => 1,
                'timestamp'        => 1499676978,
                'nonce'            => '350176',
                'signature'        => 'x',
                'room_id'          => 'rid_1242649',
                'room_session_id'  => 858012925204410400,
                'room_create_time' => '1499676978027',
                'id_name'          => 'id123',
            ]
        );
        $this->assertTrue($row->isRoomCreate());
        $this->assertSame('1', $row->appid);
        $this->assertSame('1499676978', $row->timestamp);
        $this->assertSame('858012925204410400', $row->roomSessionId);
        $this->assertSame('id123', $row->idName);
    }

    #[TestDox('fromArray：房间关闭回调与 isRoomClose')]
    public function testFromArrayRoomClose(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
            [
                'event'           => 'room_close',
                'appid'           => 1,
                'timestamp'       => 1499676989,
                'nonce'           => '350176',
                'signature'       => 'x',
                'room_id'         => 'rid_1242649',
                'room_session_id' => 858012925204410400,
                'close_reason'    => 1,
                'room_close_time' => '1499676989909',
            ]
        );
        $this->assertTrue($row->isRoomClose());
        $this->assertSame('1', $row->closeReason);
        $this->assertSame('1499676989909', $row->roomCloseTime);
    }

    #[TestDox('fromArray：缺少字段时用空字符串或空数组填充')]
    public function testFromArrayUsesDefaultsForMissingKeys(): void
    {
        $row = ZegoRtcServerCallback::fromArray([]);
        $this->assertSame('', $row->event);
        $this->assertSame('', $row->roomId);
        $this->assertSame('', $row->signature);
        $this->assertSame([], $row->rtmpUrl);
        $this->assertFalse($row->isStreamCreate());
    }

    #[TestDox('fromArray：布尔与 null 标量字段按约定转换')]
    public function testFromArrayBooleanAndNullScalars(): void
    {
        $row = ZegoRtcServerCallback::fromArray(
            [
                'event'     => 'room_close',
                'relogin'   => true,
                'room_name' => null,
            ]
        );
        $this->assertSame('1', $row->relogin);
        $this->assertSame('', $row->roomName);
    }

    #[TestDox('fromArray：URL 列表字段非数组时得到空列表')]
    public function testFromArrayStringListIgnoresNonArray(): void
    {
        $row = ZegoRtcServerCallback::fromArray(['rtmp_url' => 'not-array']);
        $this->assertSame([], $row->rtmpUrl);
    }
}
