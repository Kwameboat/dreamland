<?php
/**
 * Admin app-local components. Copy to main-local.php (gitignored locally).
 */
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'dreamland-admin-set-cookie-key-in-render',
        ],
    ],
];
