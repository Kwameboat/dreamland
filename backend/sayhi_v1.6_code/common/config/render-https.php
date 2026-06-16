<?php
/**
 * Render / Cloudflare terminate TLS at the edge; Apache inside the container sees HTTP.
 * Yii must treat the client connection as HTTPS or it redirects to http:// and breaks
 * admin login (CSRF cookies) and triggers Chrome "not secure" warnings.
 */
$forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO']
    ?? $_SERVER['HTTP_X_FORWARDED_PROTOCOL']
    ?? '';

if (is_string($forwardedProto) && stripos($forwardedProto, 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}
