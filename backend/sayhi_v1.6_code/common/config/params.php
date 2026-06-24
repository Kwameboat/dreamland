<?php
return [
    'adminEmail' => 'admin@dreamland.app',
    'supportEmail' => 'support@dreamland.app',
    'senderEmail' => 'noreply@dreamland.app',
    'senderName' => 'Dreamland',
    'siteMode' => 1, // 1 for live, 2 for testing , 3 demo  
    'siteUrl' => 'https://dreamland.app',
    'siteName' => 'Dreamland',
    'siteTagline' => 'Play, Watch, Earn',
    'enventoPurchaseCode' => 'bypassed',
    'releaseVersion' => '1.0',
    'testOtp' => '111111',
    'apiKey.firebaseCloudMessaging'=> '#################',
    'user.passwordResetTokenExpire' => 3600,
    'bsVersion' => '3.1',
    'bsDependencyEnabled' => false,
    'dreamlandPaystackCallbackUrl' => getenv('DREAMLAND_PAYSTACK_CALLBACK')
        ?: ((($pwaUrl = getenv('DREAMLAND_PWA_URL') ?: getenv('PWA_URL')) && $pwaUrl !== '')
            ? rtrim($pwaUrl, '/') . '/wallet/callback'
            : 'https://dreamlandgh.app/wallet/callback'),
    'dreamlandLiveServerUrl' => (($liveServer = getenv('DREAMLAND_LIVE_SERVER_URL')) && $liveServer !== '')
        ? $liveServer
        : 'http://127.0.0.1:4443',
    'dreamlandLiveSignalingUrl' => (($liveSignal = getenv('DREAMLAND_LIVE_SIGNALING_URL') ?: getenv('DREAMLAND_LIVE_SERVER_URL')) && $liveSignal !== '')
        ? $liveSignal
        : 'http://localhost:4443',
    'dreamlandLiveSecret' => getenv('DREAMLAND_LIVE_SECRET') ?: 'dreamland-live-dev-secret',
    'dreamlandLiveIceServers' => [
        ['urls' => 'stun:stun.l.google.com:19302'],
    ],
    'dreamlandModerationAgentUrl' => 'http://127.0.0.1:4444',
    'dreamlandModerationSecret' => 'dreamland-mod-dev-secret',
    'dreamlandModerationBlockThreshold' => 70,
    'dreamlandModerationReviewThreshold' => 40,
    'dreamlandGeminiModel' => 'gemini-2.0-flash',
    'dreamlandDevMode' => true,
    'db' => [
        'host' => 'localhost',
        'name' => '##########',
        'username' => '###########',
        'password' => '########',
        'charset' => 'utf8',
    ],
    'smtp' => [
        'host' => '############',
        'username' => '############',
        'password' => '##########',
        'port' => '587',
    ],
];
