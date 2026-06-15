<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$serveUpload = static function (string $relativePath): bool {
    $relativePath = str_replace(['..', '\\'], '', $relativePath);
    $uploadFile = dirname(__DIR__, 2) . '/frontend/web/uploads/' . $relativePath;
    if (!is_file($uploadFile)) {
        return false;
    }
    $mime = mime_content_type($uploadFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    readfile($uploadFile);
    return true;
};

if (preg_match('#^/uploads/(.+)$#', $path, $matches) && $serveUpload($matches[1])) {
    return true;
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require __DIR__ . '/index.php';
