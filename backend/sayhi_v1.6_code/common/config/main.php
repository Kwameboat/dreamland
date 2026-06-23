<?php
$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'name'=>$params['siteName'],
    //'timeZone' => 'America/New_York', 
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'urlManagerFrontend' => [
            'class' => 'yii\web\UrlManager',
        	'baseUrl' => '/',
        	'enablePrettyUrl' => true,
        	'showScriptName' => false,
    	],
       'formatter' => [
                'dateFormat' => 'dd/MM/yyyy',
                'decimalSeparator' => ',',
                'thousandSeparator' => ' ',
                'currencyCode' => 'EUR',
                'datetimeFormat' => 'dd/MM/yyyy h:mm a',
                'timeFormat' => 'h:i:s A',
                'timeZone' => 'UTC', 
        ],
        'fileUpload' => [
            'class' => 'common\components\FileUpload'
        ],
        'contentModeration' => [
            'class' => 'common\components\ContentModeration'
        ],
        'pushNotification' => [
            'class' => 'common\components\PushNotification'
        ],
        'dreamlandPush' => [
            'class' => 'common\components\DreamlandPushService',
        ],
        'dreamlandModeration' => [
            'class' => 'common\components\DreamlandModerationAgent',
        ],
        'dreamlandSafety' => [
            'class' => 'common\components\DreamlandSafetyPipeline',
        ],
        'dreamlandAi' => [
            'class' => 'common\components\DreamlandAiService',
        ],
        'sms' => [
            'class' => 'common\components\Sms',
        ],
    ],
    'modules' => [
        'gridview' => ['class' => 'kartik\grid\Module'],
       
   ],
   'params' => $params,
   
];

