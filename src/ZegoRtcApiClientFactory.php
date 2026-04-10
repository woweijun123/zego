<?php

declare(strict_types=1);

namespace Zego;

use Hyperf\Contract\ConfigInterface;

class ZegoRtcApiClientFactory
{
    public function __invoke(ConfigInterface $config): ZegoRtcApiClient
    {
        return new ZegoRtcApiClient(
            (int)$config->get('zego.app_id', 0),
            (string)$config->get('zego.secret', ''),
            (string)$config->get('zego.rtc_api_base_url', 'https://rtc-api.zego.im'),
            $config->get('zego.is_test'),
            null,
        );
    }
}
