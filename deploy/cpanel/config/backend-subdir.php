<?php
/**
 * Yii admin app when served from https://yourdomain.com/admin/
 */
return [
    'components' => [
        'request' => [
            'baseUrl' => '/admin',
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'dreamland-cpanel-set-cookie-key',
        ],
        'assetManager' => [
            'baseUrl' => '/admin/assets',
            'linkAssets' => false,
            'appendTimestamp' => true,
        ],
    ],
];
