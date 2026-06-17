<?php
require __DIR__ . '/lib/mysql-to-pgsql.php';
load_env_file(dirname(__DIR__) . '/.env.supabase');
$pdo = supabase_pdo();
foreach (['user_verification', 'user_verification_document', 'profile_category_type', 'withdrawal_payment', 'broadcast_notification', 'broadcast_notification_user'] as $t) {
    $r = $pdo->query("SELECT to_regclass('public.$t')")->fetchColumn();
    echo "$t: " . ($r ?: 'MISSING') . PHP_EOL;
}
