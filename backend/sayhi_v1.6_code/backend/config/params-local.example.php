<?php
/**
 * Admin app-local params. Copy to params-local.php (gitignored locally).
 */
return [
    'siteUrl' => getenv('SITE_URL') ?: getenv('RENDER_EXTERNAL_URL') ?: 'https://dreamland-admin-450i.onrender.com',
];
