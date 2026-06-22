<?php
/**
 * Apply Dreamland schema + seeds on cPanel MySQL (no Supabase).
 *
 * Prerequisites:
 *   1. Create MySQL database + user in cPanel
 *   2. Set DB_DRIVER=mysql and DB_* in ~/dreamland/.env
 *   3. Import base schema: bash deploy/cpanel/setup-mysql.sh
 *
 * Usage (from ~/dreamland):
 *   php scripts/setup-cpanel-mysql.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/common/config/load-dotenv.php';

echo "Dreamland cPanel MySQL setup\n";
echo str_repeat('=', 40) . "\n";

$driver = getenv('DB_DRIVER') ?: 'mysql';
if ($driver !== 'mysql') {
    fwrite(STDERR, "Set DB_DRIVER=mysql in .env (current: {$driver})\n");
    exit(1);
}

$pdo = require __DIR__ . '/lib/bootstrap-cli.php';
echo "MySQL: connected to " . (getenv('DB_NAME') ?: '') . "\n\n";

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!tableExists($pdo, 'user')) {
    fwrite(STDERR, "\nBase tables missing. Import SQL first:\n");
    fwrite(STDERR, "  cd ~/dreamland && bash deploy/cpanel/setup-mysql.sh\n");
    exit(1);
}

$steps = [
    'apply-dreamland-v2-migration.php',
    'apply-dreamland-v3-migration.php',
    'apply-dreamland-v4-migration.php',
    'dreamland-disable-legacy.php',
    'apply-dreamland-push-migration.php',
    'apply-dreamland-audience-migration.php',
    'apply-dreamland-creator-approval-migration.php',
    'apply-dreamland-moderation-migration.php',
    'apply-dreamland-rejection-migration.php',
    'apply-dreamland-walkthrough-seed.php',
    'seed-demo-data.php',
];

foreach ($steps as $script) {
    $path = __DIR__ . '/' . $script;
    if (!is_file($path)) {
        echo "SKIP (not found): {$script}\n";
        continue;
    }
    echo "--- {$script} ---\n";
    require $path;
    echo "\n";
}

echo "\nDone.\n";
echo "Admin: https://dreamlandgh.app/admin/site/login  (admin / demo123)\n";
echo "API:   https://dreamlandgh.app/api/v1/health\n";
