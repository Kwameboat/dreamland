<?php
$envFile = dirname(__DIR__, 4) . '/.env.supabase';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$serveUpload = static function (string $relativePath): bool {
    $relativePath = str_replace(['..', '\\'], '', $relativePath);
    $uploadFile = dirname(__DIR__, 2) . '/frontend/web/uploads/' . $relativePath;
    if (!is_file($uploadFile)) {
        return false;
    }
    $mime = mime_content_type($uploadFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=86400');
    readfile($uploadFile);
    return true;
};

if (preg_match('#^/frontend/web/uploads/(.+)$#', $path, $matches) && $serveUpload($matches[1])) {
    return true;
}

if (preg_match('#^/uploads/(.+)$#', $path, $matches) && $serveUpload($matches[1])) {
    return true;
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require __DIR__ . '/index.php';
