<?php
/**
 * Apply Dreamland v3 live unlock schema.
 * Usage: php scripts/apply-dreamland-v3-migration.php
 */
$pdo = require __DIR__ . '/lib/bootstrap-cli.php';

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!tableExists($pdo, 'purchased_lives')) {
    $sqlFile = __DIR__ . '/../doc/db/dreamland_v3_live.sql';
    if (!is_file($sqlFile)) {
        echo "SKIP: {$sqlFile} not found (optional on cPanel)\n";
    } else {
        $sql = file_get_contents($sqlFile);
        if ($sql === false || trim($sql) === '') {
            echo "SKIP: empty SQL in dreamland_v3_live.sql\n";
        } else {
            $pdo->exec($sql);
            echo "Created purchased_lives table\n";
        }
    }
} else {
    echo "purchased_lives already exists\n";
}

echo "Dreamland v3 migration complete.\n";
