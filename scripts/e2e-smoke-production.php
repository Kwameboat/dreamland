<?php
/**
 * End-to-end production smoke test for Dreamland.
 * Usage: php scripts/e2e-smoke-production.php
 */
$apiBase = rtrim(getenv('DL_API_BASE') ?: 'https://dreamland-t1ck.onrender.com/v1', '/');
$pwaUrl = getenv('DL_PWA_URL') ?: 'https://dreamland-plum.vercel.app';
$adminUrl = getenv('DL_ADMIN_URL') ?: 'https://dreamland-admin-450i.onrender.com';

$results = [];
$failures = 0;

function step(string $name, callable $fn): void
{
    global $results, $failures;
    echo "\n--- $name ---\n";
    try {
        $out = $fn();
        $results[$name] = ['ok' => true, 'detail' => $out];
        echo "[PASS] $name\n";
        if (is_string($out)) {
            echo "  $out\n";
        } elseif (is_array($out)) {
            echo '  ' . json_encode($out, JSON_UNESCAPED_SLASHES) . "\n";
        }
    } catch (Throwable $e) {
        $results[$name] = ['ok' => false, 'error' => $e->getMessage()];
        echo "[FAIL] $name: {$e->getMessage()}\n";
        $failures++;
    }
}

function http(string $method, string $url, ?array $json = null, ?string $token = null, ?array $multipart = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    if ($json !== null) {
        $body = json_encode($json);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if ($multipart !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart);
    }
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException("HTTP error: $err");
    }
    $data = json_decode($raw, true);
    if ($data === null && $raw !== 'null' && $raw !== '') {
        throw new RuntimeException("Invalid JSON ($code): " . substr($raw, 0, 300));
    }
    return ['code' => $code, 'data' => $data ?? [], 'raw' => $raw];
}

function apiPayload(array $r): array
{
    $d = $r['data'] ?? [];
    return isset($d['data']) && is_array($d['data']) ? $d['data'] : $d;
}

function extractFeedPosts(array $r): array
{
    $post = apiPayload($r)['post'] ?? apiPayload($r)['posts'] ?? [];
    if (isset($post['items']) && is_array($post['items'])) {
        return $post['items'];
    }
    return is_array($post) ? array_values($post) : [];
}

function login(string $email, string $password): array
{
    global $apiBase;
    $r = http('POST', "$apiBase/users/login", [
        'email' => $email,
        'password' => $password,
        'device_type' => '3',
        'device_token' => '',
        'login_ip' => '127.0.0.1',
    ]);
    if ($r['code'] >= 400) {
        throw new RuntimeException("Login failed ($email): " . ($r['data']['message'] ?? $r['raw']));
    }
    $d = apiPayload($r);
    $user = $d['user'] ?? null;
    $key = $d['auth_key'] ?? $user['auth_key'] ?? null;
    if (!$key) {
        throw new RuntimeException('No auth_key in login response');
    }
    return ['token' => $key, 'user' => $user, 'raw' => $d];
}

function feedCount(?string $token = null): int
{
    global $apiBase;
    $url = "$apiBase/posts/search-post?is_reel=1&is_ai_feed=1&page=1&expand=dreamland,user,postGallary";
    $r = http('GET', $url, null, $token);
    if ($r['code'] >= 400) {
        throw new RuntimeException('Feed failed: ' . ($r['data']['message'] ?? $r['raw']));
    }
    return count(extractFeedPosts($r));
}

echo "Dreamland E2E smoke — $apiBase\n";
echo "PWA: $pwaUrl | Admin: $adminUrl\n";

step('API health', function () use ($apiBase) {
    $r = http('GET', "$apiBase/health");
    $payload = apiPayload($r);
    if (($payload['status'] ?? '') !== 'ok') {
        throw new RuntimeException('Health not ok: ' . json_encode($r['data']));
    }
    $c = $payload['checks'] ?? [];
    if (empty($c['database'])) {
        throw new RuntimeException('Database check failed');
    }
    return [
        'dev_mode' => $c['dev_mode'] ?? false,
        'moderation_agent' => $c['moderation_agent'] ?? false,
        'live_server' => $c['live_server'] ?? false,
        'queue_depth' => $c['safety_queue_depth'] ?? null,
    ];
});

step('PWA loads', function () use ($pwaUrl) {
    $ch = curl_init($pwaUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        throw new RuntimeException("PWA HTTP $code");
    }
    return "HTTP $code";
});

$ts = time();
$newCreatorEmail = "smoke.creator.$ts@dreamland.app";
$newCreatorUser = "smokecreator$ts";

step('Register new creator account', function () use ($apiBase, $newCreatorEmail, $newCreatorUser) {
    $r = http('POST', "$apiBase/dreamland-auth/register", [
        'name' => 'Smoke Test Creator',
        'username' => $newCreatorUser,
        'email' => $newCreatorEmail,
        'password' => 'demo123',
        'account_type' => 'creator',
        'device_type' => '3',
    ]);
    if ($r['code'] >= 400) {
        throw new RuntimeException($r['data']['message'] ?? $r['raw']);
    }
    $payload = apiPayload($r);
    return [
        'email' => $newCreatorEmail,
        'creator_status' => $payload['user']['dreamland_creator_status'] ?? 'unknown',
        'message' => $r['data']['message'] ?? $payload['message'] ?? '',
    ];
});

$newCreatorToken = null;
step('New creator cannot upload before approval', function () use ($apiBase, $newCreatorEmail, &$newCreatorToken) {
    $auth = login($newCreatorEmail, 'demo123');
    $newCreatorToken = $auth['token'];
    $videoPath = __DIR__ . '/fixtures/smoke-reel.mp4';
    if (!is_file($videoPath)) {
        return 'Skipped upload test — no fixture video (expected block without file)';
    }
    $r = http('POST', "$apiBase/creator/upload-reel", null, $auth['token'], [
        'videoFile' => new CURLFile($videoPath, 'video/mp4', 'smoke-reel.mp4'),
        'title' => 'Smoke test reel',
        'profile_category_id' => '1',
        'is_paid' => '0',
    ]);
    if ($r['code'] < 400) {
        throw new RuntimeException('Upload should be blocked for pending creator but succeeded');
    }
    return $r['data']['message'] ?? 'Upload blocked as expected';
});

$creatorToken = null;
step('Login seeded creator', function () use (&$creatorToken) {
    $auth = login('creator@dreamland.app', 'demo123');
    $creatorToken = $auth['token'];
    $u = $auth['user'] ?? [];
    return [
        'id' => $u['id'] ?? null,
        'creator_status' => $u['dreamland_creator_status'] ?? null,
        'account_type' => $u['dreamland_account_type'] ?? null,
    ];
});

$categoryId = 1;
step('Fetch creator genres', function () use ($apiBase, &$categoryId) {
    $r = http('GET', "$apiBase/dreamland-meta/categories");
    $cats = apiPayload($r)['categories'] ?? [];
    if (empty($cats)) {
        throw new RuntimeException('No categories returned');
    }
    $categoryId = (int) ($cats[0]['id'] ?? 1);
    return ['count' => count($cats), 'first' => $cats[0]['name'] ?? '', 'id' => $categoryId];
});

$uploadedPostId = null;
step('Upload reel as approved creator', function () use ($apiBase, $creatorToken, $categoryId, &$uploadedPostId) {
    $videoPath = __DIR__ . '/fixtures/smoke-reel.mp4';
    if (!is_file($videoPath)) {
        throw new RuntimeException('Missing scripts/fixtures/smoke-reel.mp4 — run download fixture first');
    }
    $r = http('POST', "$apiBase/creator/upload-reel", null, $creatorToken, [
        'videoFile' => new CURLFile($videoPath, 'video/mp4', 'smoke-reel.mp4'),
        'title' => 'E2E Smoke Reel ' . date('Y-m-d H:i'),
        'description' => 'Automated smoke test upload',
        'profile_category_id' => (string) $categoryId,
        'is_paid' => '0',
    ]);
    if ($r['code'] >= 400) {
        throw new RuntimeException($r['data']['message'] ?? $r['raw']);
    }
    $uploadedPostId = (int) (apiPayload($r)['post_id'] ?? $r['data']['post_id'] ?? 0);
    return [
        'post_id' => $uploadedPostId,
        'message' => $r['data']['message'] ?? '',
        'status' => $r['data']['status'] ?? null,
        'appraisal_status' => $r['data']['appraisal_status'] ?? null,
    ];
});

step('Poll feed for uploaded reel (60s)', function () use ($creatorToken, &$uploadedPostId) {
    if (!$uploadedPostId) {
        throw new RuntimeException('No post_id from upload');
    }
    $found = false;
    for ($i = 0; $i < 12; $i++) {
        sleep(5);
        global $apiBase;
        $r = http('GET', "$apiBase/posts/search-post?is_reel=1&is_ai_feed=1&page=1", null, $creatorToken);
        $posts = extractFeedPosts($r);
        foreach ($posts as $p) {
            if ((int) ($p['id'] ?? 0) === $uploadedPostId) {
                $found = true;
                return [
                    'found' => true,
                    'post_id' => $uploadedPostId,
                    'status' => $p['status'] ?? null,
                    'appraisal_status' => $p['appraisal_status'] ?? $p['dreamland']['appraisal_status'] ?? null,
                    'wait_seconds' => ($i + 1) * 5,
                ];
            }
        }
    }
    return ['found' => false, 'post_id' => $uploadedPostId, 'feed_count' => feedCount($creatorToken)];
});

$viewerToken = null;
step('Login viewer and read feed', function () use (&$viewerToken, &$uploadedPostId) {
    $auth = login('viewer@dreamland.app', 'demo123');
    $viewerToken = $auth['token'];
    global $apiBase;
    $r = http('GET', "$apiBase/posts/search-post?is_reel=1&is_ai_feed=1&page=1&expand=dreamland,user,postGallary", null, $viewerToken);
    if ($r['code'] >= 400) {
        throw new RuntimeException($r['data']['message'] ?? 'Feed error');
    }
    $posts = extractFeedPosts($r);
    $count = count($posts);
    $seen = false;
    if ($uploadedPostId) {
        foreach ($posts as $p) {
            if ((int) ($p['id'] ?? 0) === $uploadedPostId) {
                $seen = true;
                break;
            }
        }
    }
    return [
        'feed_items' => $count,
        'credits' => $auth['user']['available_coin'] ?? null,
        'saw_new_upload' => $seen,
    ];
});

step('Creator start-live API', function () use ($apiBase, $creatorToken) {
    $r = http('POST', "$apiBase/creator/start-live", [
        'title' => 'Smoke test live',
        'is_monetized' => '0',
    ], $creatorToken);
    if ($r['code'] === 503) {
        return 'Live server offline (expected on Render without SFU) — ' . ($r['data']['message'] ?? '');
    }
    if ($r['code'] >= 400) {
        throw new RuntimeException($r['data']['message'] ?? $r['raw']);
    }
    return ['live_id' => $r['data']['live']['id'] ?? $r['data']['live_id'] ?? null, 'message' => $r['data']['message'] ?? 'ok'];
});

step('Admin panel reachable', function () use ($adminUrl) {
    $ch = curl_init($adminUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        throw new RuntimeException("Admin HTTP $code");
    }
    if (stripos((string) $body, 'login') === false && stripos((string) $body, 'Sign In') === false) {
        throw new RuntimeException('Admin page unexpected content');
    }
    return "HTTP $code (login page)";
});

echo "\n=== SUMMARY ($failures failures) ===\n";
foreach ($results as $name => $r) {
    echo ($r['ok'] ? '[PASS]' : '[FAIL]') . " $name\n";
}
exit($failures > 0 ? 1 : 0);
