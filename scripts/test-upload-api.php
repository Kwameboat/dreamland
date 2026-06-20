<?php
$api = getenv('DREAMLAND_API') ?: 'https://dreamland-t1ck.onrender.com/v1';
$email = $argv[1] ?? 'creator@dreamland.app';
$password = $argv[2] ?? 'demo123';

function req(string $url, array $opts = []): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $opts['method'] ?? 'GET',
        CURLOPT_HTTPHEADER => $opts['headers'] ?? [],
        CURLOPT_POSTFIELDS => $opts['body'] ?? null,
        CURLOPT_TIMEOUT => 120,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

echo "API: $api\n";
$login = req("$api/users/login", [
    'method' => 'POST',
    'headers' => ['Content-Type: application/json'],
    'body' => json_encode(['email' => $email, 'password' => $password, 'device_type' => 1]),
]);
echo "Login HTTP {$login['status']}\n";
$data = $login['json']['data'] ?? $login['json'];
$token = $data['auth_key']
    ?? $data['token']
    ?? ($data['user']['auth_key'] ?? null)
    ?? ($data['data']['token'] ?? null);
if (!$token) {
    echo $login['body'] . "\n";
    exit(1);
}

$auth = ['Authorization: Bearer ' . $token, 'Content-Type: application/json'];

$profile = req("$api/users/profile-update", [
    'method' => 'POST',
    'headers' => $auth,
    'body' => json_encode([
        'name' => 'Test Creator',
        'username' => 'dreamcreator',
        'bio' => 'bio test',
        'description' => 'about test',
    ]),
]);
echo "Profile update HTTP {$profile['status']}: {$profile['body']}\n";

// Tiny PNG 1x1
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$tmp = tempnam(sys_get_temp_dir(), 'dl');
file_put_contents($tmp, $png);

$ch = curl_init("$api/users/update-profile-image");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    CURLOPT_POSTFIELDS => [
        'imageFile' => new CURLFile($tmp, 'image/png', 'avatar.png'),
    ],
    CURLOPT_TIMEOUT => 120,
]);
$imgBody = curl_exec($ch);
$imgStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);
echo "Profile image HTTP $imgStatus: $imgBody\n";
