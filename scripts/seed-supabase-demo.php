<?php
/**
 * Seed demo viewer/creator accounts on Supabase PostgreSQL.
 * Password for both: demo123
 *
 * Usage (after apply-supabase.php):
 *   set DATABASE_URL=postgresql://...
 *   php scripts/seed-supabase-demo.php
 */
require __DIR__ . '/lib/mysql-to-pgsql.php';

load_env_file(dirname(__DIR__) . '/.env.supabase');
load_env_file(dirname(__DIR__) . '/.env');

$pdo = supabase_pdo();
$now = time();
$passwordHash = password_hash('demo123', PASSWORD_BCRYPT);

$users = [
    [
        'role' => 1,
        'dreamland_account_type' => 'viewer',
        'dreamland_creator_status' => 'none',
        'username' => 'admin',
        'name' => 'Admin',
        'email' => 'admin@gmail.com',
        'unique_id' => 100000,
        'available_coin' => 0,
        'auth_key' => bin2hex(random_bytes(16)),
    ],
    [
        'role' => 3,
        'dreamland_account_type' => 'viewer',
        'dreamland_creator_status' => 'none',
        'username' => 'dreamviewer',
        'name' => 'Dream Viewer',
        'email' => 'viewer@dreamland.app',
        'unique_id' => 100001,
        'available_coin' => 100,
        'auth_key' => bin2hex(random_bytes(16)),
    ],
    [
        'role' => 4,
        'dreamland_account_type' => 'creator',
        'dreamland_creator_status' => 'approved',
        'username' => 'dreamcreator',
        'name' => 'Dream Creator',
        'email' => 'creator@dreamland.app',
        'unique_id' => 100002,
        'available_coin' => 50,
        'auth_key' => bin2hex(random_bytes(16)),
    ],
];

foreach ($users as $u) {
    $exists = $pdo->prepare('SELECT id FROM "user" WHERE email = ?');
    $exists->execute([$u['email']]);
    if ($exists->fetchColumn()) {
        echo "Skip existing {$u['email']}\n";
        continue;
    }

    $sql = 'INSERT INTO "user" (
        role, dreamland_account_type, dreamland_creator_status, username, name, email,
        password_hash, auth_key, status, is_email_verified, account_created_with,
        available_coin, created_at, unique_id
    ) VALUES (?,?,?,?,?,?,?,?,10,1,1,?,?,?)';

    $pdo->prepare($sql)->execute([
        $u['role'], $u['dreamland_account_type'], $u['dreamland_creator_status'],
        $u['username'], $u['name'], $u['email'],
        $passwordHash, $u['auth_key'], $u['available_coin'], $now, $u['unique_id'],
    ]);
    echo "Seeded {$u['email']}\n";
}

echo "Demo seed complete. Login: viewer@dreamland.app / creator@dreamland.app — password demo123\n";
