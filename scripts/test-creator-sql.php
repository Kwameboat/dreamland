<?php
require __DIR__ . '/lib/mysql-to-pgsql.php';
load_env_file(dirname(__DIR__) . '/.env.supabase');
$pdo = supabase_pdo();

$sql = <<<'SQL'
SELECT "user".*,
  (SELECT COUNT(*) FROM post p WHERE p.user_id = "user".id AND p.type = 4 AND p.status <> 0) AS reel_count,
  (SELECT COUNT(*) FROM user_live_history ulh WHERE ulh.user_id = "user".id) AS live_count
FROM "user"
WHERE "user".status <> 0
  AND ("user".dreamland_account_type = 'creator' OR "user".role = 4)
LIMIT 5
SQL;

try {
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo 'OK rows=' . count($rows) . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
}

$bad = <<<'SQL'
SELECT "user".*,
  (SELECT COUNT(*) FROM post p WHERE p.user_id = user.id AND p.type = 4 AND p.status <> 0) AS reel_count
FROM "user"
LIMIT 1
SQL;

try {
    $pdo->query($bad)->fetchAll(PDO::FETCH_ASSOC);
    echo 'unquoted user.id: OK' . PHP_EOL;
} catch (Throwable $e) {
    echo 'unquoted user.id FAIL: ' . $e->getMessage() . PHP_EOL;
}
