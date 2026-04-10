<?php

declare(strict_types=1);

namespace Zego\Facades;

use Hyperf\Context\ApplicationContext;
use Zego\ZegoAssistantToken;
use Zego\ZegoManager;
use Zego\ZegoRtcApiClient;
use Zego\ZegoRtcApiResponse;
use Zego\ZegoRtcServerCallback;

/**
 * ZEGO 组件门面：从容器解析 {@see ZegoManager} 并统一转发实例方法；静态工具方法转发到 {@see ZegoManager} 对应实现。
 * 使用示例：<code>Zego::closeRoom('room1');</code>、<code>Zego::generateToken04('uid1', 3600, '{}');</code>
 * @method static ZegoRtcApiClient rtc()
 * @method static ZegoRtcApiResponse closeRoom(string $roomId, ?string $customReason = null, ?bool $roomCloseCallback = null)
 * @method static ZegoRtcApiResponse kickoutUser(string $roomId, array $userIds, ?string $customReason = null)
 * @method static ZegoRtcApiResponse forbidRtcStream(string $streamId, int $sequence)
 * @method static ZegoRtcApiResponse resumeRtcStream(string $streamId, int $sequence)
 * @method static ZegoRtcApiResponse describeSimpleStreamList(string $roomId)
 * @method static ZegoAssistantToken generateToken04(string $userId, int $effectiveTimeInSeconds, string $payload)
 * @method static bool verifyCallbackPayloadWithConfig(array $data, ?string $expectedEvent = null)
 */
class Zego
{
    public static function manager(): ZegoManager
    {
        return ApplicationContext::getContainer()->get(ZegoManager::class);
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        $manager = self::manager();

        return $manager->{$name}(...$arguments);
    }

    /**
     * @see ZegoManager::generateSignature
     */
    public static function generateSignature(int $appId, string $signatureNonce, string $serverSecret, int $timestamp): string
    {
        return ZegoManager::generateSignature($appId, $signatureNonce, $serverSecret, $timestamp);
    }

    /**
     * @param array<string, mixed> $data
     * @see ZegoManager::callbackFromArray
     */
    public static function callbackFromArray(array $data): ZegoRtcServerCallback
    {
        return ZegoManager::callbackFromArray($data);
    }

    /**
     * @see ZegoManager::verifyCallbackSignature
     */
    public static function verifyCallbackSignature(string $receivedSignature, string $timestamp, string $nonce, string $callbackSecret): bool
    {
        return ZegoManager::verifyCallbackSignature($receivedSignature, $timestamp, $nonce, $callbackSecret);
    }

    /**
     * @param array<string, mixed> $data
     * @see ZegoManager::verifyCallbackPayload
     */
    public static function verifyCallbackPayload(array $data, string $callbackSecret, ?string $expectedEvent = null): bool
    {
        return ZegoManager::verifyCallbackPayload($data, $callbackSecret, $expectedEvent);
    }
}
