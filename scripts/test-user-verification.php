<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

$base = __DIR__ . '/../backend/sayhi_v1.6_code';
require __DIR__ . '/lib/mysql-to-pgsql.php';
load_env_file(dirname(__DIR__) . '/.env.supabase');

require $base . '/vendor/autoload.php';
require $base . '/vendor/yiisoft/yii2/Yii.php';
require $base . '/common/config/bootstrap.php';
require $base . '/backend/config/bootstrap.php';

$params = array_merge(
    require $base . '/common/config/params.php',
    file_exists($base . '/common/config/params-local.php') ? require $base . '/common/config/params-local.php' : []
);
$driver = 'pgsql';
$host = getenv('SUPABASE_DB_HOST') ?: 'aws-0-eu-west-3.pooler.supabase.com';
$port = getenv('SUPABASE_DB_PORT') ?: '6543';
$name = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER');
$pass = getenv('SUPABASE_DB_PASSWORD');
$dsn = "pgsql:host={$host};port={$port};dbname={$name}";

$config = yii\helpers\ArrayHelper::merge(
    require $base . '/common/config/main.php',
    [
        'components' => [
            'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => $dsn,
                'username' => $user,
                'password' => $pass,
                'charset' => 'utf8',
                'schemaMap' => [
                    'pgsql' => [
                        'class' => 'yii\db\pgsql\Schema',
                        'defaultSchema' => 'public',
                    ],
                ],
            ],
            'user' => [
                'class' => 'yii\web\User',
                'identityClass' => 'common\models\User',
                'enableAutoLogin' => true,
            ],
            'request' => [
                'class' => 'yii\web\Request',
                'cookieValidationKey' => 'test-key',
            ],
            'urlManager' => [
                'class' => 'yii\web\UrlManager',
                'enablePrettyUrl' => true,
                'showScriptName' => false,
            ],
        ],
    ],
    require $base . '/backend/config/main.php'
);

$app = new yii\web\Application($config);

// Fake logged-in admin for widgets that need identity
$admin = common\models\User::find()->where(['role' => common\models\User::ROLE_ADMIN])->one();
if ($admin) {
    Yii::$app->user->login($admin, 3600);
}

echo 'gridview: ' . (Yii::$app->hasModule('gridview') ? 'yes' : 'no') . PHP_EOL;

try {
    $searchModel = new backend\models\UserVerificationSearch();
    $dataProvider = $searchModel->search([], null);
    $dataProvider->prepare(true);
    echo 'query count=' . $dataProvider->getTotalCount() . PHP_EOL;
} catch (Throwable $e) {
    echo 'QUERY FAIL: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}

try {
    $view = Yii::$app->view;
    $searchModel = new backend\models\UserVerificationSearch();
    $dataProvider = $searchModel->search([], null);
    $html = $view->render('@backend/views/user-verification/index', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
        'type' => null,
    ]);
    echo 'render ok, bytes=' . strlen($html) . PHP_EOL;
} catch (Throwable $e) {
    echo 'RENDER FAIL: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
