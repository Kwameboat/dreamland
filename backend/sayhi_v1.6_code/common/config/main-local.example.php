<?php
/**
 * Local / production DB bootstrap from environment.
 * Copy to main-local.php or merge into your deployed main-local.php.
 */
$params = array_merge(
    require __DIR__ . '/params.php',
    file_exists(__DIR__ . '/params-local.php') ? require __DIR__ . '/params-local.php' : []
);

$driver = $params['db']['driver'] ?? getenv('DB_DRIVER') ?: 'mysql';
$host = $params['db']['host'] ?? '127.0.0.1';
$port = $params['db']['port'] ?? ($driver === 'pgsql' ? 5432 : 3306);
$name = $params['db']['name'] ?? 'yii2advanced';
$user = $params['db']['username'] ?? 'yii2advanced';
$pass = $params['db']['password'] ?? 'secret';
$charset = $params['db']['charset'] ?? ($driver === 'pgsql' ? 'utf8' : 'utf8mb4');

if ($driver === 'pgsql') {
    $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
} else {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
}

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => $dsn,
            'username' => $user,
            'password' => $pass,
            'charset' => $charset,
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
            'schemaMap' => $driver === 'pgsql' ? [
                'pgsql' => [
                    'class' => 'yii\db\pgsql\Schema',
                    'defaultSchema' => 'public',
                ],
            ] : [],
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'useFileTransport' => !(getenv('SMTP_HOST') ?: ''),
            'transport' => [
                'dsn' => sprintf(
                    'smtp://%s:%s@%s:%s',
                    urlencode($params['smtp']['username'] ?? ''),
                    urlencode($params['smtp']['password'] ?? ''),
                    $params['smtp']['host'] ?? 'localhost',
                    $params['smtp']['port'] ?? '587'
                ),
            ],
            'viewPath' => '@common/mail',
        ],
    ],
];
