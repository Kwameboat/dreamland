<?php
/**
 * Serve uploaded media on cPanel before Yii boots (router.php is not used by index.php).
 */
function dreamland_try_serve_upload(): bool
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!preg_match('#/frontend/web/uploads/(.+)$#', $path, $matches)) {
        return false;
    }

    $relativePath = str_replace(['..', '\\'], '', $matches[1]);
    $root = dirname(__DIR__, 2);
    $roots = [
        $root . '/api/runtime/uploads/' . $relativePath,
        $root . '/frontend/web/uploads/' . $relativePath,
    ];

    $override = getenv('DREAMLAND_UPLOAD_DIR');
    if (is_string($override) && $override !== '') {
        $roots[] = rtrim($override, '/\\') . '/' . $relativePath;
    }

    foreach ($roots as $uploadFile) {
        if (!is_file($uploadFile)) {
            continue;
        }

        $mime = mime_content_type($uploadFile) ?: 'application/octet-stream';
        if (strpos($mime, 'video/') === 0 || strpos($mime, 'audio/') === 0) {
            header('Accept-Ranges: bytes');
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($uploadFile));
        header('Cache-Control: public, max-age=86400');
        header('Access-Control-Allow-Origin: *');
        readfile($uploadFile);
        return true;
    }

    return false;
}
