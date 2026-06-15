<?php
/**
 * Seed Dreamland demo users, reels, and media for end-to-end PWA testing.
 *
 * Usage: php scripts/seed-demo-data.php
 */
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3309';
$dbName = getenv('DB_NAME') ?: 'yii2advanced';
$dbUser = getenv('DB_USER') ?: 'yii2advanced';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$demoVideos = [
    [
        'file' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
        'title' => 'Street Football Highlights',
        'description' => 'Free reel — watch the full clip instantly.',
        'is_paid' => 0,
        'price_credits' => null,
        'media_type' => 4,
    ],
    [
        'file' => 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/friday.mp4',
        'title' => 'Accra Night Market Tour',
        'description' => 'Premium reel — 3 second preview, unlock for credits.',
        'is_paid' => 1,
        'price_credits' => 15,
        'media_type' => 4,
    ],
    [
        'file' => 'https://www.w3schools.com/html/mov_bbb.mp4',
        'title' => 'Creator Masterclass Clip',
        'description' => 'Premium reel — unlock to support the creator.',
        'is_paid' => 1,
        'price_credits' => 25,
        'media_type' => 4,
    ],
];

$demoPassword = 'demo123';
$passwordHash = password_hash($demoPassword, PASSWORD_BCRYPT);
$adminUsername = 'admin';
$adminEmail = 'admin@dreamland.app';
$creatorEmail = 'creator@dreamland.app';
$viewerEmail = 'viewer@dreamland.app';
$now = time();

function nextUniqueId(PDO $pdo): int
{
    $last = (int) $pdo->query('SELECT COALESCE(MAX(unique_id), 100000) FROM user')->fetchColumn();
    return $last + 1;
}

function upsertUser(PDO $pdo, string $email, array $fields): int
{
    $stmt = $pdo->prepare('SELECT id FROM user WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $sets = [];
        $vals = [];
        foreach ($fields as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $pdo->prepare('UPDATE user SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
        return (int) $id;
    }

    $fields['email'] = $email;
    $cols = array_keys($fields);
    $placeholders = array_fill(0, count($cols), '?');
    $sql = 'INSERT INTO user (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $placeholders) . ')';
    $pdo->prepare($sql)->execute(array_values($fields));
    return (int) $pdo->lastInsertId();
}

$adminStmt = $pdo->prepare('SELECT id FROM user WHERE username = ? LIMIT 1');
$adminStmt->execute([$adminUsername]);
$adminId = $adminStmt->fetchColumn();
if ($adminId) {
    $pdo->prepare(
        'UPDATE user SET role = 1, status = 10, email = ?, password_hash = ?, updated_at = ? WHERE id = ?'
    )->execute([$adminEmail, $passwordHash, $now, $adminId]);
    echo "Admin user #{$adminId} ({$adminUsername} / {$adminEmail})\n";
} else {
    $adminId = upsertUser($pdo, $adminEmail, [
        'role' => 1,
        'username' => $adminUsername,
        'name' => 'Dreamland Admin',
        'auth_key' => bin2hex(random_bytes(16)),
        'password_hash' => $passwordHash,
        'unique_id' => nextUniqueId($pdo),
        'status' => 10,
        'is_verified' => 1,
        'is_email_verified' => 1,
        'is_phone_verified' => 0,
        'account_created_with' => 1,
        'available_coin' => 0,
        'available_balance' => 0,
        'profile_visibility' => 1,
        'is_push_notification_allow' => 0,
        'like_push_notification_status' => 1,
        'comment_push_notification_status' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    echo "Admin user #{$adminId} ({$adminUsername} / {$adminEmail})\n";
}

$pdo->exec('UPDATE setting SET is_two_factor_auth = 0 WHERE id = 1');

require __DIR__ . '/dreamland-disable-legacy.php';
dreamlandDisableLegacy($pdo, false);

$creatorId = upsertUser($pdo, $creatorEmail, [
    'role' => 4,
    'username' => 'dreamcreator',
    'name' => 'Dream Creator',
    'auth_key' => bin2hex(random_bytes(16)),
    'password_hash' => $passwordHash,
    'unique_id' => nextUniqueId($pdo),
    'status' => 10,
    'is_verified' => 1,
    'is_email_verified' => 1,
    'is_phone_verified' => 0,
    'account_created_with' => 1,
    'available_coin' => 0,
    'available_balance' => 0,
    'profile_visibility' => 1,
    'is_push_notification_allow' => 0,
    'like_push_notification_status' => 1,
    'comment_push_notification_status' => 1,
    'created_at' => $now,
    'updated_at' => $now,
]);
echo "Creator user #{$creatorId} ({$creatorEmail})\n";

$viewerId = upsertUser($pdo, $viewerEmail, [
    'role' => 3,
    'username' => 'dreamviewer',
    'name' => 'Dream Viewer',
    'auth_key' => bin2hex(random_bytes(16)),
    'password_hash' => $passwordHash,
    'unique_id' => nextUniqueId($pdo),
    'status' => 10,
    'is_verified' => 1,
    'is_email_verified' => 1,
    'is_phone_verified' => 0,
    'account_created_with' => 1,
    'available_coin' => 100,
    'available_balance' => 0,
    'profile_visibility' => 1,
    'is_push_notification_allow' => 0,
    'like_push_notification_status' => 1,
    'comment_push_notification_status' => 1,
    'created_at' => $now,
    'updated_at' => $now,
]);
echo "Viewer user #{$viewerId} ({$viewerEmail}) — 100 credits\n";

$pdo->prepare(
    'DELETE pg FROM post_gallary pg
     INNER JOIN post p ON p.id = pg.post_id
     WHERE p.user_id = ? AND p.title LIKE ?'
)->execute([$creatorId, 'Dreamland Demo:%']);

$pdo->prepare('DELETE FROM post WHERE user_id = ? AND title LIKE ?')
    ->execute([$creatorId, 'Dreamland Demo:%']);

$insertPost = $pdo->prepare(
    'INSERT INTO post (
        type, post_content_type, unique_id, user_id, title, description,
        total_view, total_like, total_comment, total_share, popular_point,
        status, is_paid, price_credits, appraisal_status, is_comment_enable,
        display_whose, created_by, updated_by, created_at, updated_at
    ) VALUES (
        4, 2, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        10, ?, ?, \'active\', 1,
        1, ?, ?, ?, ?
    )'
);

$insertGallery = $pdo->prepare(
    'INSERT INTO post_gallary (
        post_id, type, media_type, filename, video_thumb, is_default,
        status, width, height, created_at
    ) VALUES (?, 1, ?, ?, \'\', 1, 10, 720, 1280, ?)'
);

foreach ($demoVideos as $index => $video) {
    $uniqueId = 'dldemo' . ($index + 1) . substr(md5($video['title']), 0, 6);
    $createdAt = $now - ($index * 3600);
    $insertPost->execute([
        $uniqueId,
        $creatorId,
        'Dreamland Demo: ' . $video['title'],
        $video['description'],
        random_int(120, 2400),
        random_int(10, 200),
        random_int(0, 40),
        random_int(0, 20),
        random_int(5, 80),
        $video['is_paid'],
        $video['price_credits'],
        $creatorId,
        $creatorId,
        $createdAt,
        $now,
    ]);
    $postId = (int) $pdo->lastInsertId();
    $insertGallery->execute([$postId, $video['media_type'], $video['file'], $now]);
    $kind = $video['is_paid'] ? "paid ({$video['price_credits']} credits)" : 'free';
    echo "  reel #{$postId}: {$video['title']} — {$kind}\n";
}

$feedCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM post WHERE type = 4 AND status = 10 AND appraisal_status = \'active\''
)->fetchColumn();

echo "\nDone. Active reels in feed: {$feedCount}\n";
echo "\nDemo credentials (password: {$demoPassword})\n";
echo "  Admin panel:          http://localhost:8081\n";
echo "    Username:           {$adminUsername}\n";
echo "    Email:              {$adminEmail}\n";
echo "  PWA viewer (100 cr):  {$viewerEmail}\n";
echo "  PWA creator:          {$creatorEmail}\n";
echo "\nPWA: http://localhost:3000\n";
