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
        ],
    ],
];
