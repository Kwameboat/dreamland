<?php
/**
 * Production / Supabase config template.
 * Copy relevant sections into common/config/params-local.php on the API server.
 * Set env vars from dreamland/env.example — never commit params-local.php.
 */
return array_merge(
    require __DIR__ . '/params-local.messages.php',
    [
        'siteUrl' => getenv('SITE_URL') ?: 'https://your-api.example.com',
        'dreamlandPwaUrl' => getenv('DREAMLAND_PWA_URL') ?: getenv('PWA_URL') ?: 'https://your-pwa.vercel.app',
        'dreamlandAdminUrl' => getenv('DREAMLAND_ADMIN_URL') ?: getenv('ADMIN_URL') ?: 'https://your-admin.onrender.com',
        'dreamlandPaystackCallbackUrl' => getenv('DREAMLAND_PAYSTACK_CALLBACK') ?: (getenv('DREAMLAND_PWA_URL') ?: 'https://your-pwa.vercel.app') . '/wallet/callback',
        'dreamlandDevMode' => filter_var(getenv('DREAMLAND_DEV_MODE') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'db' => [
            'driver' => getenv('DB_DRIVER') ?: 'pgsql',
            'host' => getenv('DB_HOST') ?: getenv('SUPABASE_DB_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_PORT') ?: getenv('SUPABASE_DB_PORT') ?: 5432),
            'name' => getenv('DB_NAME') ?: getenv('SUPABASE_DB_NAME') ?: 'postgres',
            'username' => getenv('DB_USER') ?: getenv('SUPABASE_DB_USER') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: getenv('SUPABASE_DB_PASSWORD') ?: '',
            'charset' => getenv('DB_DRIVER') === 'mysql' ? 'utf8mb4' : 'utf8',
        ],
        'smtp' => [
            'host' => getenv('SMTP_HOST') ?: 'localhost',
            'username' => getenv('SMTP_USER') ?: '',
            'password' => getenv('SMTP_PASSWORD') ?: '',
            'port' => getenv('SMTP_PORT') ?: '587',
        ],
    ]
);
