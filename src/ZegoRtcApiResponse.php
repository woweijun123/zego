<?php

declare(strict_types=1);

namespace Zego;

/**
 * ZEGO 实时音视频服务端 API 统一返回结构（公共返回参数 Code / Message / RequestId / Data）。
 * @see https://doc-zh.zego.im/real-time-video-server/api-reference/accessing-server-apis
 */
final readonly class ZegoRtcApiResponse
{
    /**
     * @param array<string, mixed> $raw 服务端返回的完整 JSON 对象，便于访问未建模字段或调试
     */
    public function __construct(
        public int    $code,
        public string $message,
        public string $requestId,
        public mixed  $data = null,
        private array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $decoded json_decode(..., true) 结果
     */
    public static function fromDecodedArray(array $decoded): self
    {
        return new self(
            (int)($decoded['Code'] ?? 0),
            (string)($decoded['Message'] ?? ''),
            (string)($decoded['RequestId'] ?? ''),
            $decoded['Data'] ?? null,
            $decoded,
        );
    }

    public function isSuccess(): bool
    {
        return $this->code === 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'Code'      => $this->code,
            'Message'   => $this->message,
            'RequestId' => $this->requestId,
            'Data'      => $this->data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
