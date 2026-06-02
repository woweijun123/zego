<?php

declare(strict_types=1);

namespace Zego;

use Closure;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Hyperf\Context\ApplicationContext;
use Hyperf\Guzzle\ClientFactory;
use InvalidArgumentException;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Random\RandomException;
use RuntimeException;

/**
 * 实时音视频服务端 API 客户端（GET，公共参数见
 * https://doc-zh.zego.im/real-time-video-server/api-reference/accessing-server-apis）
 */
readonly class ZegoRtcApiClient
{
    /**
     * 构造函数
     * @param string           $serverSecret
     * @param string           $baseUrl
     * @param bool|string|null $isTest
     * @param Closure|null     $httpTransport
     */
    public function __construct(
        private int              $appId,
        private string           $serverSecret,
        private string           $baseUrl = 'https://rtc-api.zego.im',
        private bool|string|null $isTest = null,
        private ?Closure         $httpTransport = null
    ) {
    }

    /**
     * Signature = md5(AppId + SignatureNonce + ServerSecret + Timestamp)，小写 hex。
     */
    public static function generateSignature(int $appId, string $signatureNonce, string $serverSecret, int $timestamp): string
    {
        return md5($appId . $signatureNonce . $serverSecret . $timestamp);
    }

    /**
     * 发送 GET 请求
     * @param string $action
     * @param array  $params
     * @param array  $repeatParams
     * @return ZegoRtcApiResponse
     */
    private function requestGet(string $action, array $params = [], array $repeatParams = []): ZegoRtcApiResponse
    {
        return $this->httpGetJson($this->buildSignedUrl($action, $params, $repeatParams));
    }

    /**
     * 构建签名后的 URL
     * @param string $action
     * @param array  $params
     * @param array  $repeatParams
     * @return string
     */
    private function buildSignedUrl(string $action, array $params, array $repeatParams = []): string
    {
        try {
            $signatureNonce = bin2hex(random_bytes(8));
            $timestamp      = time();
            $signature      = self::generateSignature($this->appId, $signatureNonce, $this->serverSecret, $timestamp);
            $query          = [
                'Action'           => $action,
                'AppId'            => $this->appId,
                'SignatureNonce'   => $signatureNonce,
                'Timestamp'        => $timestamp,
                'Signature'        => $signature,
                'SignatureVersion' => '2.0',
            ];
            if ($this->isTest !== null) {
                if (is_bool($this->isTest)) {
                    $query['IsTest'] = $this->isTest ? 'true' : 'false';
                } else {
                    $query['IsTest'] = (string)$this->isTest;
                }
            }
            $query = array_merge($query, $params);
            $qs    = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            foreach ($repeatParams as $key => $values) {
                foreach ($values as $value) {
                    $qs .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
                }
            }

            return rtrim($this->baseUrl, '/') . '/?' . $qs;
        } catch (RandomException $e) {
            throw new RuntimeException('Random exception: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 发送 GET 请求并解析 JSON 响应
     * @param string $url
     * @return ZegoRtcApiResponse
     */
    private function httpGetJson(string $url): ZegoRtcApiResponse
    {
        // 1. 如果有自定义传输层，保持原有逻辑（可选，如果 Guzzle 可以完全替代则移除）
        if ($this->httpTransport !== null) {
            /** @var array{status: int, body: string} $res */
            $res = ($this->httpTransport)($url);

            return $this->processRawResponse((int)($res['status'] ?? 0), (string)($res['body'] ?? ''));
        }

        // 2. 使用 Guzzle Client
        try {
            $options  = [
                RequestOptions::TIMEOUT     => 30, // 30 秒超时
                RequestOptions::HTTP_ERRORS => false, // 禁用自动抛出异常，以便我们手动处理 body 内容
            ];
            $client   = ApplicationContext::getContainer()->get(ClientFactory::class)->create($options);
            $response = $client->get($url);
            $code     = $response->getStatusCode();
            $body     = (string)$response->getBody();

            // 3. 校验状态码
            if ($code < 200 || $code >= 300) {
                throw new RuntimeException('HTTP status ' . $code . ': ' . $body, $code);
            }

            // 4. 解析 JSON
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return ZegoRtcApiResponse::fromDecodedArray($decoded);
        } catch (GuzzleException $e) {
            // 捕获网络连接、超时等异常
            throw new RuntimeException('HTTP request failed: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (JsonException $e) {
            // 捕获 JSON 解析异常
            throw new RuntimeException('Invalid JSON response: ' . ($body ?? 'Empty'), 0, $e);
        } catch (ContainerExceptionInterface $e) {
            // 捕获容器异常
            throw new RuntimeException('Container exception: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 提取公共的响应处理逻辑（可选）
     * @param int    $code
     * @param string $body
     * @return ZegoRtcApiResponse
     */
    private function processRawResponse(int $code, string $body): ZegoRtcApiResponse
    {
        if ($code < 200 || $code >= 300) {
            throw new RuntimeException('HTTP status ' . $code . ': ' . $body, $code);
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return ZegoRtcApiResponse::fromDecodedArray($decoded);
        } catch (JsonException $e) {
            throw new RuntimeException('Invalid JSON response: ' . $body, 0, $e);
        }
    }

    /**
     * 关闭房间 CloseRoom
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/room/close
     * @param string      $roomId
     * @param string|null $customReason
     * @param bool|null   $roomCloseCallback
     * @return ZegoRtcApiResponse
     */
    public function closeRoom(string $roomId, ?string $customReason = null, ?bool $roomCloseCallback = null): ZegoRtcApiResponse
    {
        $params = ['RoomId' => $roomId];
        if ($customReason !== null) {
            $params['CustomReason'] = $customReason;
        }
        if ($roomCloseCallback !== null) {
            $params['RoomCloseCallback'] = $roomCloseCallback ? 'true' : 'false';
        }

        return $this->requestGet('CloseRoom', $params);
    }

    /**
     * 踢出用户 KickoutUser（同一请求最多 5 个 userId）
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/room/kick-out-user
     * @param string       $roomId
     * @param list<string> $userIds
     * @param string|null  $customReason
     * @return ZegoRtcApiResponse
     */
    public function kickoutUser(string $roomId, array $userIds, ?string $customReason = null): ZegoRtcApiResponse
    {
        $n = count($userIds);
        if ($n < 1 || $n > 5) {
            throw new InvalidArgumentException('KickoutUser requires 1..5 user ids');
        }

        $params = ['RoomId' => $roomId];
        if ($customReason !== null) {
            $params['CustomReason'] = $customReason;
        }

        return $this->requestGet('KickoutUser', $params, ['UserId' => $userIds]);
    }

    /**
     * 禁止 RTC 推流 ForbidRTCStream
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/media-service/forbid-rtc-stream
     * @param string $streamId
     * @param string $sequence
     * @return ZegoRtcApiResponse
     */
    public function forbidRtcStream(string $streamId, string $sequence): ZegoRtcApiResponse
    {
        return $this->requestGet('ForbidRTCStream', ['StreamId' => $streamId, 'Sequence' => $sequence]);
    }

    /**
     * 恢复 RTC 推流 ResumeRTCStream
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/media-service/resume-rtc-stream
     * @param string $streamId
     * @param string $sequence
     * @return ZegoRtcApiResponse
     */
    public function resumeRtcStream(string $streamId, string $sequence): ZegoRtcApiResponse
    {
        return $this->requestGet('ResumeRTCStream', ['StreamId' => $streamId, 'Sequence' => $sequence]);
    }

    /**
     * 获取房间内简易流列表 DescribeSimpleStreamList
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/room/describe-simple-streamlist
     * @param string $roomId
     * @return ZegoRtcApiResponse
     */
    public function describeSimpleStreamList(string $roomId): ZegoRtcApiResponse
    {
        return $this->requestGet('DescribeSimpleStreamList', ['RoomId' => $roomId]);
    }

    /**
     * 获取房间用户列表 DescribeUserList
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/room/describe-user-list
     * @param string $roomId 房间 ID
     * @param int|null $mode 排序，0：时间正序，1：时间倒序
     * @param int|null $limit 单次最大返回个数，0-200
     * @param string|null $marker 用户起始位标识（分页）
     * @return ZegoRtcApiResponse
     */
    public function describeUserList(string $roomId, ?int $mode = null, ?int $limit = null, ?string $marker = null): ZegoRtcApiResponse
    {
        $params = array_filter(['RoomId' => $roomId, 'Mode' => $mode, 'Limit' => $limit, 'Marker' => $marker], fn ($v) => $v !== null);
        return $this->requestGet('DescribeUserList', $params);
    }

    /**
     * 获取房间人数 DescribeUserNum
     * @see https://doc-zh.zego.im/real-time-video-server/api-reference/room/describe-user-num
     * @param string|string[] $roomIds 房间 ID，支持批量查询
     * @return ZegoRtcApiResponse
     */
    public function describeUserNum(string|array $roomIds): ZegoRtcApiResponse
    {
        $roomIds = is_array($roomIds) ? $roomIds : [$roomIds];
        return $this->requestGet('DescribeUserNum', [], ['RoomId[]' => $roomIds]);
    }
}
