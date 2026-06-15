<?php
/**
 * Seed genres, credit packages, and local-dev settings for end-to-end walkthrough.
 * Usage: php scripts/apply-dreamland-walkthrough-seed.php
 */
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3309';
$dbName = getenv('DB_NAME') ?: 'yii2advanced';
$dbUser = getenv('DB_USER') ?: 'yii2advanced';
$dbPass = getenv('DB_PASSWORD') ?: 'secret';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

$genres = [
    'Comedy', 'Music & Dance', 'Sports', 'Food & Culture', 'Fashion', 'Tech & Gaming', 'Education', 'Lifestyle',
];

$genreCount = 0;
if (tableExists($pdo, 'profile_category_type')) {
    foreach ($genres as $name) {
        $check = $pdo->prepare('SELECT id FROM profile_category_type WHERE name = ? LIMIT 1');
        $check->execute([$name]);
        if ($check->fetchColumn()) {
            continue;
        }
        $pdo->prepare('INSERT INTO profile_category_type (name, status, image) VALUES (?, 10, ?)')
            ->execute([$name, '']);
        $genreCount++;
    }
    echo "Genres: {$genreCount} new profile_category_type rows\n";
} else {
    echo "Skip genres — profile_category_type table missing\n";
}

if (tableExists($pdo, 'credit_packages')) {
    $existing = (int) $pdo->query('SELECT COUNT(*) FROM credit_packages WHERE is_active = 1')->fetchColumn();
    if ($existing === 0) {
        $packages = [
            ['50 Credits Starter', 50, 5.00],
            ['120 Credits Value', 120, 10.00],
            ['300 Credits Pro', 300, 25.00],
        ];
        foreach ($packages as [$label, $credits, $cost]) {
            $id = sprintf(
                '%04x%04x-%04x-4000-8000-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
            $pdo->prepare(
                'INSERT INTO credit_packages (id, credit_amount, fiat_cost, currency, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())'
            )->execute([$id, $credits, $cost, 'GHS']);
        }
        echo "Credit packages: 3 packages seeded\n";
    } else {
        echo "Credit packages: {$existing} active (unchanged)\n";
    }
}

if (tableExists($pdo, 'setting')) {
    $pdo->exec('UPDATE setting SET content_moderation_gateway = 0 WHERE id = 1');
    echo "Disabled legacy upload-time Sightengine/Rekognition gate (Dreamland AI agent handles safety)\n";
}

if (tableExists($pdo, 'dreamland_settings')) {
    $pdo->exec('UPDATE dreamland_settings SET preview_seconds = 3 WHERE id = 1');
    echo "Dreamland settings row ready\n";
}

// Give demo viewer starter credits for unlock walkthrough
$pdo->exec("UPDATE user SET available_coin = GREATEST(available_coin, 100) WHERE email = 'viewer@dreamland.app'");
$pdo->exec("UPDATE user SET available_coin = GREATEST(available_coin, 50) WHERE dreamland_account_type = 'viewer' AND available_coin < 20");

echo "Walkthrough seed complete.\n";
echo "  PWA:     http://localhost:3000\n";
echo "  API:     http://localhost:8080/v1\n";
echo "  Admin:   http://localhost:8081\n";
