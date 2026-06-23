<?php
/**
 * Yii API when served from https://yourdomain.com/api/
 */
return [
    'components' => [
        'request' => [
            'baseUrl' => '/api',
        ],
        'urlManager' => [
            'baseUrl' => '/api',
            'showScriptName' => false,
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'rules' => [
                'GET v1/health' => 'v1/health/index',
                'GET v1/wallet/packages' => 'v1/wallet/packages',
                'GET v1/dreamland-meta/settings' => 'v1/dreamland-meta/settings',
            ],
        ],
    ],
];
