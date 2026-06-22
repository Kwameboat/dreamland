<?php
/**
 * Yii admin app when served from https://yourdomain.com/admin/
 */
return [
    'components' => [
        'request' => [
            'baseUrl' => '/admin',
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'dreamland-cpanel-set-cookie-key',
            'trustedHosts' => [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                '127.0.0.1',
                '::1',
                'dreamlandgh.app' => [
                    'X-Forwarded-For',
                    'X-Forwarded-Host',
                    'X-Forwarded-Proto',
                    'Front-End-Https',
                ],
                'any' => [
                    'X-Forwarded-For',
                    'X-Forwarded-Host',
                    'X-Forwarded-Proto',
                    'Front-End-Https',
                ],
            ],
        ],
        'session' => [
            'savePath' => dirname(__DIR__, 3) . '/backend/runtime',
        ],
        'assetManager' => [
            'basePath' => dirname(__DIR__, 3) . '/backend/web/assets',
            'baseUrl' => '/admin/assets',
            'linkAssets' => false,
            'appendTimestamp' => true,
        ],
    ],
];
