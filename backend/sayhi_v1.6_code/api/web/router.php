<?php
// PHP built-in server router for Yii2 API + local upload media
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: *');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    return true;
}

$serveUpload = static function (string $relativePath): bool {
    $relativePath = str_replace(['..', '\\'], '', $relativePath);
    $uploadFile = dirname(__DIR__, 2) . '/frontend/web/uploads/' . $relativePath;
    if (!is_file($uploadFile)) {
        return false;
    }
    $mime = mime_content_type($uploadFile) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Access-Control-Allow-Origin: *');
    header('Accept-Ranges: bytes');
    readfile($uploadFile);
    return true;
};

if (preg_match('#^/frontend/web/uploads/(.+)$#', $path, $matches)) {
    if ($serveUpload($matches[1])) {
        return true;
    }
    http_response_code(404);
    echo 'Upload not found';
    return true;
}

// Yii FileUpload URLs use /uploads/{folder}/{file} on local dev.
if (preg_match('#^/uploads/(.+)$#', $path, $matches)) {
    if ($serveUpload($matches[1])) {
        return true;
    }
    http_response_code(404);
    echo 'Upload not found';
    return true;
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
