<?php

declare(strict_types=1);

namespace Zego;

use Hyperf\Contract\ConfigInterface;

/**
 * ZEGO 组件统一入口：RTC 服务端 API、Token04、服务端回调解析与验签。
 */
class ZegoManager
{
    public function __construct(
        protected ZegoRtcApiClient $rtcApiClient,
        protected ConfigInterface  $config,
    ) {
    }

    public function rtc(): ZegoRtcApiClient
    {
        return $this->rtcApiClient;
    }

    /**
     * @see ZegoRtcApiClient::closeRoom
     */
    public function closeRoom(string $roomId, ?string $customReason = null, ?bool $roomCloseCallback = null): ZegoRtcApiResponse
    {
        return $this->rtcApiClient->closeRoom($roomId, $customReason, $roomCloseCallback);
    }

    /**
     * @param list<string> $userIds
     * @see ZegoRtcApiClient::kickoutUser
     */
    public function kickoutUser(string $roomId, array $userIds, ?string $customReason = null): ZegoRtcApiResponse
    {
        return $this->rtcApiClient->kickoutUser($roomId, $userIds, $customReason);
    }

    /**
     * @see ZegoRtcApiClient::forbidRtcStream
     */
    public function forbidRtcStream(string $streamId, int $sequence): ZegoRtcApiResponse
    {
        return $this->rtcApiClient->forbidRtcStream($streamId, $sequence);
    }

    /**
     * @see ZegoRtcApiClient::resumeRtcStream
     */
    public function resumeRtcStream(string $streamId, int $sequence): ZegoRtcApiResponse
    {
        return $this->rtcApiClient->resumeRtcStream($streamId, $sequence);
    }

    /**
     * @see ZegoRtcApiClient::describeSimpleStreamList
     */
    public function describeSimpleStreamList(string $roomId): ZegoRtcApiResponse
    {
        return $this->rtcApiClient->describeSimpleStreamList($roomId);
    }

    /**
     * 使用配置中的 app_id、secret 生成 Token04。
     * @see ZegoServerAssistant::generateToken04
     */
    public function generateToken04(string $userId, int $effectiveTimeInSeconds, string $payload): ZegoAssistantToken
    {
        $appId  = (int)$this->config->get('zego.app_id', 0);
        $secret = (string)$this->config->get('zego.secret', '');

        return ZegoServerAssistant::generateToken04($appId, $userId, $secret, $effectiveTimeInSeconds, $payload);
    }

    /**
     * 服务端 API 公共参数签名：md5(AppId + SignatureNonce + ServerSecret + Timestamp)，小写 hex。
     * @see ZegoRtcApiClient::generateSignature
     */
    public static function generateSignature(int $appId, string $signatureNonce, string $serverSecret, int $timestamp): string
    {
        return ZegoRtcApiClient::generateSignature($appId, $signatureNonce, $serverSecret, $timestamp);
    }

    /**
     * @param array<string, mixed> $data UrlDecode 后的回调 JSON 字段
     * @see ZegoRtcServerCallback::fromArray
     */
    public static function callbackFromArray(array $data): ZegoRtcServerCallback
    {
        return ZegoRtcServerCallback::fromArray($data);
    }

    /**
     * @see ZegoRtcServerCallback::verifySignature
     */
    public static function verifyCallbackSignature(string $receivedSignature, string $timestamp, string $nonce, string $callbackSecret): bool
    {
        return ZegoRtcServerCallback::verifySignature($receivedSignature, $timestamp, $nonce, $callbackSecret);
    }

    /**
     * @param array<string, mixed> $data
     * @see ZegoRtcServerCallback::verifyPayload
     */
    public static function verifyCallbackPayload(array $data, string $callbackSecret, ?string $expectedEvent = null): bool
    {
        return ZegoRtcServerCallback::verifyPayload($data, $callbackSecret, $expectedEvent);
    }

    /**
     * 使用配置中的 callback_secret 校验回调负载。
     * @param array<string, mixed> $data
     */
    public function verifyCallbackPayloadWithConfig(array $data, ?string $expectedEvent = null): bool
    {
        $secret = (string)$this->config->get('zego.callback_secret', '');

        return self::verifyCallbackPayload($data, $secret, $expectedEvent);
    }
}
