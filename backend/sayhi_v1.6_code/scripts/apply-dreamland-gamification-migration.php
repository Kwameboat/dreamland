<?php
/**
 * Create gamification tables required for premium appraisal approval.
 * Usage: php scripts/apply-dreamland-gamification-migration.php
 */
declare(strict_types=1);

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

$sqlFile = __DIR__ . '/../doc/db/dreamland_gamification_mysql.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Missing {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "Empty SQL in dreamland_gamification_mysql.sql\n");
    exit(1);
}

foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $statement) {
    if ($statement === '' || stripos($statement, 'SET ') === 0) {
        continue;
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
    }
}

$tables = ['group_watch_pots', 'group_watch_pot_contributions', 'video_predictions', 'video_prediction_stakes'];
foreach ($tables as $table) {
    echo tableExists($pdo, $table) ? "OK: {$table}\n" : "MISSING: {$table}\n";
}

echo "Dreamland gamification migration complete.\n";
