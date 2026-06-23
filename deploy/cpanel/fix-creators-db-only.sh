#!/bin/bash
# Self-contained: repair PWA creator DB rows + show user #2 status.
# No GitHub required. Run: bash deploy/cpanel/fix-creators-db-only.sh
set -e
cd "${HOME}/dreamland"

php << 'PHP'
<?php
declare(strict_types=1);
require __DIR__ . '/common/config/load-dotenv.php';
$pdo = require __DIR__ . '/scripts/lib/bootstrap-cli.php';

function hasCol(PDO $pdo, string $col): bool {
    $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $s->execute(['user', $col]);
    return (int)$s->fetchColumn() > 0;
}

echo "=== User #2 ===\n";
$row = $pdo->query('SELECT id, username, email, role, status, dreamland_account_type, dreamland_creator_status FROM user WHERE id = 2')->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "NOT FOUND — id=2 does not exist. The grid row number is not the user id.\n";
    echo "Run: php scripts/check-creator.php\n\n";
} else {
    print_r($row);
}

$hasType = hasCol($pdo, 'dreamland_account_type');
$hasStatus = hasCol($pdo, 'dreamland_creator_status');

$sql = 'SELECT id, role, dreamland_account_type, dreamland_creator_status FROM user WHERE status <> 0';
if ($hasStatus) {
    $sql .= " AND (dreamland_creator_status IN ('pending','approved','rejected') OR role = 4 OR dreamland_account_type = 'creator')";
} else {
    $sql .= ' AND role = 4';
}

$fixed = 0;
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $sets = [];
    $vals = [];
    if ((int)$row['role'] !== 4) { $sets[] = 'role = ?'; $vals[] = 4; }
    if ($hasType && ($row['dreamland_account_type'] ?? '') !== 'creator') {
        $sets[] = 'dreamland_account_type = ?'; $vals[] = 'creator';
    }
    if ($hasStatus) {
        $st = strtolower(trim((string)($row['dreamland_creator_status'] ?? '')));
        if (!in_array($st, ['pending','approved','rejected'], true)) {
            $sets[] = 'dreamland_creator_status = ?'; $vals[] = 'pending';
        }
    }
    if (!$sets) continue;
    $vals[] = (int)$row['id'];
    $pdo->prepare('UPDATE user SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    echo "Fixed user #{$row['id']}\n";
    $fixed++;
}

echo $fixed ? "Synced {$fixed} creator row(s).\n" : "No creator rows needed repair.\n";
echo "\nRe-open: https://dreamlandgh.app/admin/index.php?r=content-creator/view&id=2\n";
PHP
