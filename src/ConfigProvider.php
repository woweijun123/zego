<?php

declare(strict_types=1);

namespace ZEGO;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
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
