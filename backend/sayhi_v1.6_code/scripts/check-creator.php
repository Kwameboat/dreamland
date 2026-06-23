<?php
/**
 * Check / repair a creator row for admin view.
 * Usage: php scripts/check-creator.php [user_id]
 */
declare(strict_types=1);

$id = isset($argv[1]) ? (int) $argv[1] : 0;

require __DIR__ . '/../common/config/load-dotenv.php';
$pdo = require __DIR__ . '/lib/bootstrap-cli.php';

echo "Dreamland creator check\n";
echo str_repeat('=', 40) . "\n\n";

$cols = ['id', 'username', 'email', 'role', 'status'];
foreach (['dreamland_account_type', 'dreamland_creator_status'] as $c) {
    $s = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $s->execute(['user', $c]);
    if ((int) $s->fetchColumn() > 0) {
        $cols[] = $c;
    }
}

$sql = 'SELECT ' . implode(', ', $cols) . ' FROM user';
if ($id > 0) {
    $sql .= ' WHERE id = ' . $id;
} else {
    $sql .= ' WHERE status <> 0 AND (role = 4';
    if (in_array('dreamland_account_type', $cols, true)) {
        $sql .= ' OR dreamland_account_type = "creator"';
    }
    if (in_array('dreamland_creator_status', $cols, true)) {
        $sql .= ' OR dreamland_creator_status IN ("pending","approved","rejected")';
    }
    $sql .= ') ORDER BY id DESC LIMIT 20';
}

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
    echo $id > 0 ? "No user with id={$id}.\n" : "No creator rows found.\n";
    echo "\nAll non-deleted users:\n";
    $all = $pdo->query('SELECT id, username, email, role, status FROM user WHERE status <> 0 ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $r) {
        echo "#{$r['id']} {$r['username']} ({$r['email']}) role={$r['role']} status={$r['status']}\n";
    }
    exit(1);
}

foreach ($rows as $row) {
    echo "User #{$row['id']}\n";
    foreach ($row as $k => $v) {
        if ($k === 'id') {
            continue;
        }
        echo "  {$k}: " . ($v === null || $v === '' ? '(empty)' : $v) . "\n";
    }
    echo "  admin view: https://dreamlandgh.app/admin/index.php?r=content-creator/view&id={$row['id']}\n\n";
}

if ($id > 0 && $rows) {
    $row = $rows[0];
    $action = isset($argv[2]) ? strtolower(trim((string) $argv[2])) : '';

    if ($action === 'approve' && in_array('dreamland_creator_status', $cols, true)) {
        $pdo->prepare('UPDATE user SET dreamland_creator_status = ?, role = 4 WHERE id = ?')
            ->execute(['approved', $id]);
        if (in_array('dreamland_account_type', $cols, true)) {
            $pdo->prepare('UPDATE user SET dreamland_account_type = ? WHERE id = ?')
                ->execute(['creator', $id]);
        }
        echo "Approved user #{$id} for PWA upload.\n";
        exit(0);
    }

    $sets = [];
    if ((int) $row['role'] !== 4) {
        $sets[] = 'role = 4';
    }
    if (in_array('dreamland_account_type', $cols, true) && ($row['dreamland_account_type'] ?? '') !== 'creator') {
        $sets[] = 'dreamland_account_type = "creator"';
    }
    if (in_array('dreamland_creator_status', $cols, true)) {
        $st = strtolower(trim((string) ($row['dreamland_creator_status'] ?? '')));
        if (!in_array($st, ['pending', 'approved', 'rejected'], true)) {
            $sets[] = 'dreamland_creator_status = "pending"';
        }
    }
    if ($sets !== []) {
        $pdo->exec('UPDATE user SET ' . implode(', ', $sets) . ' WHERE id = ' . $id);
        echo "Repaired user #{$id}: " . implode(', ', $sets) . "\n";
    } else {
        echo "User #{$id} creator fields already OK.\n";
    }
}

echo "\nDone.\n";
