<?php
require __DIR__ . '/lib/mysql-to-pgsql.php';
load_env_file(dirname(__DIR__) . '/.env.supabase');
$pdo = supabase_pdo();
foreach (['user_verification', 'user_verification_document'] as $t) {
    $r = $pdo->query("SELECT to_regclass('public.$t')")->fetchColumn();
    echo "$t: " . ($r ?: 'MISSING') . PHP_EOL;
}
