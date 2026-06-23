<?php
/**
 * Repair PWA creator rows missing role/account_type (admin view/delete 404).
 * Usage: php scripts/sync-pwa-creators.php
 */
declare(strict_types=1);

$pdo = require __DIR__ . '/lib/bootstrap-cli.php';

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

$hasType = columnExists($pdo, 'user', 'dreamland_account_type');
$hasStatus = columnExists($pdo, 'user', 'dreamland_creator_status');

$sql = 'SELECT id, role, dreamland_account_type, dreamland_creator_status FROM user WHERE status <> 0';
if ($hasStatus) {
    $sql .= " AND (
        dreamland_creator_status IN ('pending','approved','rejected')
        OR role = 4
        OR dreamland_account_type = 'creator'
    )";
} else {
    $sql .= ' AND role = 4';
}

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$fixed = 0;

foreach ($rows as $row) {
    $updates = [];
    if ((int) $row['role'] !== 4) {
        $updates['role'] = 4;
    }
    if ($hasType && ($row['dreamland_account_type'] ?? '') !== 'creator') {
        $updates['dreamland_account_type'] = 'creator';
    }
    if ($hasStatus) {
        $status = strtolower(trim((string) ($row['dreamland_creator_status'] ?? '')));
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $updates['dreamland_creator_status'] = 'pending';
        }
    }
    if ($updates === []) {
        continue;
    }
    $sets = [];
    $vals = [];
    foreach ($updates as $col => $val) {
        $sets[] = "`{$col}` = ?";
        $vals[] = $val;
    }
    $vals[] = (int) $row['id'];
    $pdo->prepare('UPDATE user SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    echo "Fixed user #{$row['id']}\n";
    $fixed++;
}

echo $fixed > 0
    ? "Synced {$fixed} creator row(s).\n"
    : "All creator rows already consistent.\n";
