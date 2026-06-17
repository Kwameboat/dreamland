<?php
$api = 'https://dreamland-t1ck.onrender.com/v1';
function get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_TIMEOUT=>60]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $raw];
}
function post($url, $json) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
        CURLOPT_POSTFIELDS=>json_encode($json),
        CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_TIMEOUT=>60,
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $raw];
}

foreach ([
    'health' => "$api/health",
    'categories' => "$api/dreamland-meta/categories",
    'feed' => "$api/posts/search-post?is_reel=1&is_ai_feed=1&page=1",
] as $label => $url) {
    [$c, $r] = get($url);
    echo "=== $label HTTP $c ===\n" . substr($r, 0, 800) . "\n\n";
}

[$c, $r] = post("$api/users/login", [
    'email' => 'viewer@dreamland.app', 'password' => 'demo123', 'device_type' => '3', 'login_ip' => '127.0.0.1',
]);
echo "=== login HTTP $c ===\n" . substr($r, 0, 1200) . "\n\n";

[$c, $r] = post("$api/dreamland-auth/register", [
    'username' => 'smoketest' . time(), 'email' => 'smoke' . time() . '@example.com',
    'password' => 'demo123', 'account_type' => 'creator', 'device_type' => '3', 'name' => 'Smoke',
]);
echo "=== register HTTP $c ===\n" . substr($r, 0, 1200) . "\n";
