<?php

declare(strict_types=1);

namespace Zego;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;

class ZegoRtcApiClientFactory
{
    public function __invoke(Container $container): ZegoRtcApiClient
    {
        $config = $container->get(ConfigInterface::class);
        return new ZegoRtcApiClient(
            (int)$config->get('zego.app_id', 0),
            (string)$config->get('zego.secret', ''),
            (string)$config->get('zego.rtc_api_base_url', 'https://rtc-api.zego.im'),
            $config->get('zego.is_test'),
            null,
        );
    }
}
