<?php
/**
 * Same-origin Socket.IO proxy → Fly live-server (WebRTC SFU).
 * Browsers connect to https://dreamlandgh.app/live-socket — forwards to DREAMLAND_LIVE_SIGNALING_URL.
 */
declare(strict_types=1);

const DREAMLAND_LIVE_FLY_DEFAULT = 'https://dreamland-live.fly.dev';

/**
 * Load ~/dreamland/.env on cPanel (standalone PHP does not run Yii bootstrap).
 */
function dreamland_load_env(): void
{
    $candidates = [
        dirname(__DIR__, 2) . '/dreamland/.env',
        dirname(__DIR__, 2) . '/.env',
    ];
    $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
    if ($home !== '') {
        $candidates[] = $home . '/dreamland/.env';
    }

    $envFile = null;
    foreach ($candidates as $path) {
        if (is_file($path)) {
            $envFile = $path;
            break;
        }
    }
    if ($envFile === null) {
        return;
    }

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

function dreamland_live_upstream(): string
{
    dreamland_load_env();

    $upstream = getenv('DREAMLAND_LIVE_SIGNALING_URL')
        ?: getenv('DREAMLAND_LIVE_SERVER_URL')
        ?: DREAMLAND_LIVE_FLY_DEFAULT;
    $upstream = rtrim($upstream, '/');

    // Render has no UDP — WebRTC video always fails through it.
    if (stripos($upstream, 'onrender.com') !== false) {
        $upstream = DREAMLAND_LIVE_FLY_DEFAULT;
    }

    return $upstream;
}

$upstream = dreamland_live_upstream();

$uri = $_SERVER['REQUEST_URI'] ?? '/live-socket/';
$path = '/';
if (preg_match('#/live-socket/?(.*)$#', $uri, $m)) {
    $tail = $m[1] ?? '';
    $tail = strtok($tail, '?') ?: '';
    $path = $tail === '' ? '/' : '/' . ltrim($tail, '/');
}

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = $upstream . $path . ($query !== '' ? '?' . $query : '');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = in_array($method, ['POST', 'PUT', 'PATCH'], true)
    ? file_get_contents('php://input')
    : null;

$forwardHeaders = [];
$headerMap = [
    'HTTP_CONTENT_TYPE' => 'Content-Type',
    'HTTP_ACCEPT' => 'Accept',
    'HTTP_ORIGIN' => 'Origin',
    'HTTP_REFERER' => 'Referer',
    'HTTP_USER_AGENT' => 'User-Agent',
    'HTTP_SEC_WEBSOCKET_KEY' => 'Sec-WebSocket-Key',
    'HTTP_SEC_WEBSOCKET_VERSION' => 'Sec-WebSocket-Version',
    'HTTP_SEC_WEBSOCKET_EXTENSIONS' => 'Sec-WebSocket-Extensions',
    'HTTP_SEC_WEBSOCKET_PROTOCOL' => 'Sec-WebSocket-Protocol',
    'HTTP_CONNECTION' => 'Connection',
    'HTTP_UPGRADE' => 'Upgrade',
];
foreach ($headerMap as $from => $to) {
    if (!empty($_SERVER[$from])) {
        $forwardHeaders[] = $to . ': ' . $_SERVER[$from];
    }
}
$forwardHeaders[] = 'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$forwardHeaders[] = 'X-Forwarded-Proto: ' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

if ($method === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

$ch = curl_init($target);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_HTTPHEADER => $forwardHeaders,
    CURLOPT_POSTFIELDS => $body,
]);

$response = curl_exec($ch);
if ($response === false) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'Live signaling proxy error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$rawHeaders = substr($response, 0, $headerSize);
$payload = substr($response, $headerSize);

$skip = ['transfer-encoding', 'connection', 'content-encoding', 'keep-alive'];
http_response_code($status);
foreach (preg_split('/\r\n/', $rawHeaders) as $line) {
    if ($line === '' || stripos($line, 'HTTP/') === 0) {
        continue;
    }
    $parts = explode(':', $line, 2);
    if (count($parts) !== 2) {
        continue;
    }
    $name = strtolower(trim($parts[0]));
    if (in_array($name, $skip, true)) {
        continue;
    }
    header(trim($parts[0]) . ': ' . trim($parts[1]), $name !== 'set-cookie');
}

header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('X-Dreamland-Live-Upstream: ' . $upstream);

echo $payload;
