<?php

declare(strict_types=1);

namespace Zego;

/**
 * 实时音视频服务端回调统一解析与签名校验（流创建/关闭、房间创建/关闭、用户进房/退房）。
 * @see https://doc-zh.zego.im/real-time-video-server/callback/receiving-callback
 * @see https://doc-zh.zego.im/real-time-video-server/callback/stream/created
 * @see https://doc-zh.zego.im/real-time-video-server/callback/stream/destroyed
 * @see https://doc-zh.zego.im/real-time-video-server/callback/room/created
 * @see https://doc-zh.zego.im/real-time-video-server/callback/room/destroyed
 * @see https://doc-zh.zego.im/real-time-video-server/callback/room/logged-in
 * @see https://doc-zh.zego.im/real-time-video-server/callback/room/logged-out
 */
final class ZegoRtcServerCallback
{
    public const EVENT_STREAM_CREATE = 'stream_create';
    public const EVENT_STREAM_CLOSE  = 'stream_close';
    public const EVENT_ROOM_LOGIN    = 'room_login';
    public const EVENT_ROOM_LOGOUT   = 'room_logout';
    public const EVENT_ROOM_CREATE   = 'room_create';
    public const EVENT_ROOM_CLOSE    = 'room_close';

    /** @var list<string> */
    public const KNOWN_EVENTS = [
        self::EVENT_STREAM_CREATE,
        self::EVENT_STREAM_CLOSE,
        self::EVENT_ROOM_LOGIN,
        self::EVENT_ROOM_LOGOUT,
        self::EVENT_ROOM_CREATE,
        self::EVENT_ROOM_CLOSE,
    ];

    private function __construct(
        public readonly string $event,
        public readonly string $appid,
        public readonly string $timestamp,
        public readonly string $nonce,
        public readonly string $signature,
        // 流 / 房间通用
        public readonly string $roomId,
        public readonly string $roomSessionId,
        public readonly string $roomSeq,
        // 流
        public readonly string $userId,
        public readonly string $userName,
        public readonly string $channelId,
        public readonly string $streamId,
        public readonly string $streamSid,
        public readonly string $streamAlias,
        public readonly string $streamSeq,
        public readonly string $title,
        public readonly string $streamAttr,
        public readonly string $createTime,
        public readonly string $createTimeMs,
        public readonly string $extraInfo,
        public readonly string $recreate,
        public readonly string $publishId,
        public readonly string $publishName,
        /** @var list<string> */
        public readonly array  $rtmpUrl,
        /** @var list<string> */
        public readonly array  $hlsUrl,
        /** @var list<string> */
        public readonly array  $hdlUrl,
        /** @var list<string> */
        public readonly array  $picUrl,
        /** stream_close 的 type */
        public readonly string $streamCloseType,
        public readonly string $thirdDefineData,
        public readonly string $destroyTimemillis,
        // 房间用户
        public readonly string $roomName,
        public readonly string $userAccount,
        public readonly string $userNickname,
        public readonly string $sessionId,
        public readonly string $loginTime,
        public readonly string $logoutTime,
        public readonly string $reason,
        public readonly string $userRole,
        public readonly string $userUpdateSeq,
        public readonly string $authLevel,
        public readonly string $relogin,
        public readonly string $callbackData,
        // 房间创建 / 关闭
        public readonly string $roomCreateTime,
        public readonly string $idName,
        public readonly string $closeReason,
        public readonly string $roomCloseTime,
    ) {
    }

    /**
     * @param array<string, mixed> $data UrlDecode 后的 JSON 对象字段
     */
    public static function fromArray(array $data): self
    {
        return new self(
            event            : self::stringScalar($data, 'event'),
            appid            : self::stringScalar($data, 'appid'),
            timestamp        : self::stringScalar($data, 'timestamp'),
            nonce            : self::stringScalar($data, 'nonce'),
            signature        : self::stringScalar($data, 'signature'),
            roomId           : self::stringScalar($data, 'room_id'),
            roomSessionId    : self::stringScalar($data, 'room_session_id'),
            roomSeq          : self::stringScalar($data, 'room_seq'),
            userId           : self::stringScalar($data, 'user_id'),
            userName         : self::stringScalar($data, 'user_name'),
            channelId        : self::stringScalar($data, 'channel_id'),
            streamId         : self::stringScalar($data, 'stream_id'),
            streamSid        : self::stringScalar($data, 'stream_sid'),
            streamAlias      : self::stringScalar($data, 'stream_alias'),
            streamSeq        : self::stringScalar($data, 'stream_seq'),
            title            : self::stringScalar($data, 'title'),
            streamAttr       : self::stringScalar($data, 'stream_attr'),
            createTime       : self::stringScalar($data, 'create_time'),
            createTimeMs     : self::stringScalar($data, 'create_time_ms'),
            extraInfo        : self::stringScalar($data, 'extra_info'),
            recreate         : self::stringScalar($data, 'recreate'),
            publishId        : self::stringScalar($data, 'publish_id'),
            publishName      : self::stringScalar($data, 'publish_name'),
            rtmpUrl          : self::stringList($data, 'rtmp_url'),
            hlsUrl           : self::stringList($data, 'hls_url'),
            hdlUrl           : self::stringList($data, 'hdl_url'),
            picUrl           : self::stringList($data, 'pic_url'),
            streamCloseType  : self::stringScalar($data, 'type'),
            thirdDefineData  : self::stringScalar($data, 'third_define_data'),
            destroyTimemillis: self::stringScalar($data, 'destroy_timemillis'),
            roomName         : self::stringScalar($data, 'room_name'),
            userAccount      : self::stringScalar($data, 'user_account'),
            userNickname     : self::stringScalar($data, 'user_nickname'),
            sessionId        : self::stringScalar($data, 'session_id'),
            loginTime        : self::stringScalar($data, 'login_time'),
            logoutTime       : self::stringScalar($data, 'logout_time'),
            reason           : self::stringScalar($data, 'reason'),
            userRole         : self::stringScalar($data, 'user_role'),
            userUpdateSeq    : self::stringScalar($data, 'user_update_seq'),
            authLevel        : self::stringScalar($data, 'auth_level'),
            relogin          : self::stringScalar($data, 'relogin'),
            callbackData     : self::stringScalar($data, 'callback_data'),
            roomCreateTime   : self::stringScalar($data, 'room_create_time'),
            idName           : self::stringScalar($data, 'id_name'),
            closeReason      : self::stringScalar($data, 'close_reason'),
            roomCloseTime    : self::stringScalar($data, 'room_close_time'),
        );
    }

    /**
     * 与文档一致：对 callbacksecret、timestamp、nonce 按字典序排序后拼接，再 SHA1（十六进制小写比较）。
     */
    public static function verifySignature(string $receivedSignature, string $timestamp, string $nonce, string $callbackSecret): bool
    {
        $tmp = [$callbackSecret, $timestamp, $nonce];
        sort($tmp, SORT_STRING);
        $calc = sha1(implode('', $tmp));

        return hash_equals(strtolower($calc), strtolower($receivedSignature));
    }

    /**
     * @param array<string, mixed>  $data
     * @param non-empty-string|null $expectedEvent 为 null 时仅允许 {@see KNOWN_EVENTS} 内事件
     */
    public static function verifyPayload(array $data, string $callbackSecret, ?string $expectedEvent = null): bool
    {
        $event = (string)($data['event'] ?? '');
        if ($expectedEvent !== null) {
            if ($event !== $expectedEvent) {
                return false;
            }
        } elseif (!in_array($event, self::KNOWN_EVENTS, true)) {
            return false;
        }

        return self::verifySignature(
            (string)($data['signature'] ?? ''),
            (string)($data['timestamp'] ?? ''),
            (string)($data['nonce'] ?? ''),
            $callbackSecret
        );
    }

    public function isStreamCreate(): bool
    {
        return $this->event === self::EVENT_STREAM_CREATE;
    }

    public function isStreamClose(): bool
    {
        return $this->event === self::EVENT_STREAM_CLOSE;
    }

    public function isRoomLogin(): bool
    {
        return $this->event === self::EVENT_ROOM_LOGIN;
    }

    public function isRoomLogout(): bool
    {
        return $this->event === self::EVENT_ROOM_LOGOUT;
    }

    public function isRoomCreate(): bool
    {
        return $this->event === self::EVENT_ROOM_CREATE;
    }

    public function isRoomClose(): bool
    {
        return $this->event === self::EVENT_ROOM_CLOSE;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function stringScalar(array $data, string $key): string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return '';
        }
        $v = $data[$key];
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_array($v)) {
            return '';
        }

        return (string)$v;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private static function stringList(array $data, string $key): array
    {
        $v = $data[$key] ?? null;
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach ($v as $item) {
            $out[] = is_scalar($item) || $item instanceof \Stringable ? (string)$item : '';
        }

        return $out;
    }
}
