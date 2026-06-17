<?php
/**
 * Admin app-local components. Copy to main-local.php (gitignored locally).
 */
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'dreamland-admin-set-cookie-key-in-render',
            'trustedHosts' => [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                '127.0.0.1',
                '::1',
            ],
            'secureHeaders' => [
                'X-Forwarded-For',
                'X-Forwarded-Host',
                'X-Forwarded-Proto',
                'Front-End-Https',
            ],
        ],
        'assetManager' => [
            'basePath' => dirname(__DIR__) . '/web/assets',
            'baseUrl' => 'assets',
            'linkAssets' => false,
            'appendTimestamp' => true,
            'bundles' => [
                'backend\assets\AdminLteAsset' => [
                    'skin' => 'skin-black',
                ],
            ],
        ],
        'session' => [
            'cookieParams' => [
                'httponly' => true,
                'secure' => (bool) preg_match('/^https:/i', getenv('SITE_URL') ?: getenv('RENDER_EXTERNAL_URL') ?: ''),
                'sameSite' => 'Lax',
            ],
        ],
    ],
];
