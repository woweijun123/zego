<?php

declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\Attributes\TestDox;
use Zego\ZegoRtcApiClient;

class ZegoRtcApiClientTest extends TestCase
{
    private const APP_ID = 1234567890;

    private const SERVER_SECRET = 'fa94dd0f974cf2e293728a526b028271';

    #[TestDox('服务端 API 签名：generateSignature 与官方文档 MD5 示例向量一致')]
    public function testGenerateSignatureMatchesDocExample(): void
    {
        $sig = ZegoRtcApiClient::generateSignature(12345, '4fd24687296dd9f3', '9193cc662a4c0ec135ec71fb57194b38', 1615186943);
        $this->assertSame('43e5cfcca828314675f91b001390566a', $sig);
    }

    #[TestDox('CloseRoom：请求含 Action/RoomId/公共参数且 Signature 与规则一致，并解析 JSON 响应')]
    public function testCloseRoomBuildsSignedUrlAndReturnsJson(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(self::APP_ID, self::SERVER_SECRET, 'https://rtc-api.zego.im', null, function (string $url) use (&$captured): array {
            $captured = $url;

            return ['status' => 200, 'body' => '{"Code":0,"Message":"success","RequestId":"rid1"}'];
        },);

        $out = $client->closeRoom('roomAbc');

        $this->assertSame(0, $out['Code']);
        $this->assertNotNull($captured);
        $q = $this->parseQuery($captured);
        $this->assertSame('CloseRoom', $q['Action']);
        $this->assertSame((string)self::APP_ID, $q['AppId']);
        $this->assertSame('2.0', $q['SignatureVersion']);
        $this->assertSame('roomAbc', $q['RoomId']);
        $this->assertArrayNotHasKey('CustomReason', $q);
        $this->assertArrayNotHasKey('RoomCloseCallback', $q);
        $this->assertUrlSignatureValid($q);
    }

    #[TestDox('CloseRoom：传入关闭原因与 RoomCloseCallback 时 query 含 CustomReason 与布尔字符串')]
    public function testCloseRoomWithOptionalParams(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $client->closeRoom('r1', 'reason space', true);
        $q = $this->parseQuery($captured);
        $this->assertSame('reason space', $q['CustomReason']);
        $this->assertSame('true', $q['RoomCloseCallback']);
    }

    #[TestDox('KickoutUser：多个用户时 query 中重复出现 UserId 键且签名校验通过')]
    public function testKickoutUserRepeatsUserIdQueryKeys(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $client->kickoutUser('room1', ['u1', 'u2'], 'kicked');
        $query = (parse_url($captured, PHP_URL_QUERY) ?? '');
        $this->assertSame(2, preg_match_all('/(^|&)UserId=/', $query));
        $q = $this->parseQuery($captured);
        $this->assertSame('KickoutUser', $q['Action']);
        $this->assertSame('room1', $q['RoomId']);
        $this->assertUrlSignatureValid($q);
    }

    #[TestDox('KickoutUser：一次传入 5 个 userId 时 query 中含 5 个 UserId')]
    public function testKickoutUserAcceptsFiveUserIds(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $ids = ['a', 'b', 'c', 'd', 'e'];
        $client->kickoutUser('r', $ids);
        $query = (string)(parse_url($captured, PHP_URL_QUERY) ?? '');
        $this->assertSame(5, preg_match_all('/(^|&)UserId=/', $query));
    }

    #[TestDox('KickoutUser：userId 列表为空时抛出 InvalidArgumentException')]
    public function testKickoutUserRejectsEmptyUserList(): void
    {
        $client = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static fn(): array => ['status' => 200, 'body' => '{}',]
        );

        $this->expectException(\InvalidArgumentException::class);
        $client->kickoutUser('room1', []);
    }

    #[TestDox('KickoutUser：userId 超过 5 个时抛出 InvalidArgumentException')]
    public function testKickoutUserRejectsMoreThanFiveUsers(): void
    {
        $client = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static fn(): array => ['status' => 200, 'body' => '{}',]
        );

        $this->expectException(\InvalidArgumentException::class);
        $client->kickoutUser('room1', ['1', '2', '3', '4', '5', '6']);
    }

    #[TestDox('ForbidRTCStream：query 含 StreamId、Sequence 且 Signature 正确')]
    public function testForbidRtcStream(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $client->forbidRtcStream('streamX', 1_704_000_000);
        $q = $this->parseQuery($captured);
        $this->assertSame('ForbidRTCStream', $q['Action']);
        $this->assertSame('streamX', $q['StreamId']);
        $this->assertSame('1704000000', $q['Sequence']);
        $this->assertUrlSignatureValid($q);
    }

    #[TestDox('ResumeRTCStream：query 含 StreamId、Sequence 与正确 Action')]
    public function testResumeRtcStream(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $client->resumeRtcStream('streamY', 99);
        $q = $this->parseQuery($captured);
        $this->assertSame('ResumeRTCStream', $q['Action']);
        $this->assertSame('streamY', $q['StreamId']);
        $this->assertSame('99', $q['Sequence']);
    }

    #[TestDox('DescribeSimpleStreamList：query 含 RoomId，响应中 Data.StreamList 可解析')]
    public function testDescribeSimpleStreamList(): void
    {
        $httpTransport = static function (string $url) use (&$captured): array {
            $captured = $url;

            return ['status' => 200, 'body' => '{"Code":0,"Data":{"StreamList":[]}}'];
        };
        $client        = new ZegoRtcApiClient(self::APP_ID, self::SERVER_SECRET, 'https://rtc-api.zego.im', null, $httpTransport);

        $out = $client->describeSimpleStreamList('rid');
        $q   = $this->parseQuery($captured);
        $this->assertSame('DescribeSimpleStreamList', $q['Action']);
        $this->assertSame('rid', $q['RoomId']);
        $this->assertSame([], $out['Data']['StreamList'] ?? null);
    }

    #[TestDox('公共参数 IsTest：构造为 bool true 时 query 中为字符串 true')]
    public function testIsTestBooleanSerializedInQuery(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            true,
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $client->describeSimpleStreamList('r');
        $q = $this->parseQuery($captured);
        $this->assertSame('true', $q['IsTest']);
    }

    #[TestDox('公共参数 IsTest：传入字符串时原样出现在 query 中')]
    public function testIsTestStringPassedThrough(): void
    {
        $captured = null;
        $client   = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            'false',
            static function (string $url) use (&$captured): array {
                $captured = $url;

                return ['status' => 200, 'body' => '{"Code":0}'];
            },
        );

        $client->closeRoom('r');
        $q = $this->parseQuery($captured);
        $this->assertSame('false', $q['IsTest']);
    }

    #[TestDox('HTTP 注入：状态码非 2xx 时抛出 RuntimeException')]
    public function testHttpTransportNon2xxThrows(): void
    {
        $client = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static fn(): array => ['status' => 503, 'body' => 'busy',]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP status 503');
        $client->closeRoom('r');
    }

    #[TestDox('HTTP 注入：200 但 body 非合法 JSON 时抛出 RuntimeException')]
    public function testHttpTransportInvalidJsonThrows(): void
    {
        $client = new ZegoRtcApiClient(
            self::APP_ID,
            self::SERVER_SECRET,
            'https://rtc-api.zego.im',
            null,
            static fn(): array => ['status' => 200, 'body' => 'not-json',]
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON response');
        $client->closeRoom('r');
    }

    /**
     * @return array<string, string>
     */
    private function parseQuery(string $url): array
    {
        $qs = parse_url($url, PHP_URL_QUERY);
        if ($qs === null || $qs === false || $qs === '') {
            return [];
        }
        $out = [];
        parse_str($qs, $out);
        $flat = [];
        foreach ($out as $k => $v) {
            $flat[(string)$k] = is_scalar($v) ? (string)$v : '';
        }

        return $flat;
    }

    /**
     * @param array<string, string> $q
     */
    private function assertUrlSignatureValid(array $q): void
    {
        $expected = ZegoRtcApiClient::generateSignature(self::APP_ID, $q['SignatureNonce'], self::SERVER_SECRET, (int)$q['Timestamp']);
        $this->assertSame($expected, $q['Signature']);
    }
}
