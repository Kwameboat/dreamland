<?php
$port = (int) (getenv('DB_PORT') ?: 3309);
try {
    new PDO(
        "mysql:host=127.0.0.1;port={$port};dbname=yii2advanced",
        getenv('DB_USER') ?: 'yii2advanced',
        getenv('DB_PASSWORD') ?: 'secret'
    );
    echo "yii2advanced@127.0.0.1:{$port}/yii2advanced: OK\n";
    exit(0);
} catch (Throwable $e) {
    echo "yii2advanced@127.0.0.1:{$port}/yii2advanced: FAIL\n";
    exit(1);
}
