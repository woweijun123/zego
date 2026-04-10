<?php

declare(strict_types=1);

namespace Zego;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ZegoRtcApiClient::class => ZegoRtcApiClientFactory::class,
                ZegoManager::class      => ZegoManager::class,
            ],
            'listeners'    => [
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'aspects'      => [
            ],
            'publish'      => [
                [
                    'id'          => 'config',
                    'description' => 'The config for zego.',
                    'source'      => __DIR__ . '/../publish/zego.php',
                    'destination' => BASE_PATH . '/config/autoload/zego.php',
                ],
            ],
        ];
    }
}
