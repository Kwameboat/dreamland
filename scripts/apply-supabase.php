<?php
/**
 * Apply all Supabase migrations in supabase/migrations/ order.
 *
 * Set connection via DATABASE_URL or SUPABASE_DB_* env vars (see env.example).
 *
 * Usage:
 *   set DATABASE_URL=postgresql://postgres.[ref]:[password]@aws-0-[region].pooler.supabase.com:6543/postgres
 *   php scripts/apply-supabase.php
 */
require __DIR__ . '/lib/mysql-to-pgsql.php';

load_env_file(dirname(__DIR__) . '/.env.supabase');
load_env_file(dirname(__DIR__) . '/.env');

$migrationsDir = dirname(__DIR__) . '/supabase/migrations';
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Missing {$migrationsDir}\nRun: php scripts/export-mysql-to-supabase.php\n");
    exit(1);
}

try {
    $pdo = supabase_pdo();
    $pdo->exec('SELECT 1');
    echo "Connected to Supabase PostgreSQL.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Connection failed: {$e->getMessage()}\n");
    fwrite(STDERR, "Set DATABASE_URL or SUPABASE_DB_HOST/PORT/NAME/USER/PASSWORD\n");
    exit(1);
}

$pdo->exec('CREATE TABLE IF NOT EXISTS _dreamland_migrations (
    filename VARCHAR(255) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$files = glob($migrationsDir . '/*.sql');
sort($files);

$applied = 0;
foreach ($files as $file) {
    $name = basename($file);
    $check = $pdo->prepare('SELECT 1 FROM _dreamland_migrations WHERE filename = ?');
    $check->execute([$name]);
    if ($check->fetchColumn()) {
        echo "Skip (already applied): {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if (trim($sql) === '') {
        continue;
    }

    echo "Applying {$name}...\n";
    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $ins = $pdo->prepare('INSERT INTO _dreamland_migrations (filename) VALUES (?)');
        $ins->execute([$name]);
        $pdo->commit();
        $applied++;
        echo "  OK\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "  FAILED: {$e->getMessage()}\n");
        exit(1);
    }
}

echo "\nDone. Applied {$applied} migration(s).\n";
echo "Verify: curl https://YOUR-API/v1/health (database: true)\n";
