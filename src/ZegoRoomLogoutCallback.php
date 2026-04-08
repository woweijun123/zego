<?php

declare(strict_types=1);

namespace Zego;

/**
 * 退出房间回调 room_logout：签名校验与载荷解析
 * @see https://doc-zh.zego.im/real-time-video-server/callback/room/logged-out
 * @see https://doc-zh.zego.im/real-time-video-server/callback/receiving-callback
 */
final class ZegoRoomLogoutCallback
{
    public const EVENT = 'room_logout';

    private function __construct(
        public readonly string $event,
        public readonly string $appid,
        public readonly string $timestamp,
        public readonly string $nonce,
        public readonly string $signature,
        public readonly string $roomId,
        public readonly string $roomSeq,
        public readonly string $userAccount,
        public readonly string $userNickname,
        public readonly string $sessionId,
        public readonly string $loginTime,
        public readonly string $logoutTime,
        public readonly string $reason,
        public readonly string $userRole,
        public readonly string $userUpdateSeq
    ) {
    }

    /**
     * @param array<string, mixed> $data UrlDecode 后的 JSON 对象字段
     */
    public static function fromArray(array $data): self
    {
        return new self(
            event        : (string)($data['event'] ?? ''),
            appid        : (string)($data['appid'] ?? ''),
            timestamp    : (string)($data['timestamp'] ?? ''),
            nonce        : (string)($data['nonce'] ?? ''),
            signature    : (string)($data['signature'] ?? ''),
            roomId       : (string)($data['room_id'] ?? ''),
            roomSeq      : (string)($data['room_seq'] ?? ''),
            userAccount  : (string)($data['user_account'] ?? ''),
            userNickname : (string)($data['user_nickname'] ?? ''),
            sessionId    : (string)($data['session_id'] ?? ''),
            loginTime    : (string)($data['login_time'] ?? ''),
            logoutTime   : (string)($data['logout_time'] ?? ''),
            reason       : (string)($data['reason'] ?? ''),
            userRole     : (string)($data['user_role'] ?? ''),
            userUpdateSeq: (string)($data['user_update_seq'] ?? ''),
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
     * @param array<string, mixed> $data
     */
    public static function verifyPayload(array $data, string $callbackSecret): bool
    {
        if (($data['event'] ?? '') !== self::EVENT) {
            return false;
        }

        return self::verifySignature((string)($data['signature'] ?? ''), (string)($data['timestamp'] ?? ''), (string)($data['nonce'] ?? ''), $callbackSecret);
    }
}
