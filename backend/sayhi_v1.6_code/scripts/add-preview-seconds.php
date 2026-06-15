<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3309;dbname=yii2advanced;charset=utf8mb4', 'yii2advanced', 'secret');
$stmt = $pdo->query("SHOW COLUMNS FROM dreamland_settings LIKE 'preview_seconds'");
if (!$stmt->fetch()) {
    $pdo->exec('ALTER TABLE dreamland_settings ADD COLUMN preview_seconds TINYINT NOT NULL DEFAULT 3');
    echo "Added preview_seconds\n";
} else {
    echo "preview_seconds exists\n";
}
