<?php
/**
 * Run API login + feed logic locally against Supabase to surface real errors.
 */
$root = dirname(__DIR__) . '/backend/sayhi_v1.6_code';
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
require $root . '/api/config/bootstrap.php';

$envFile = dirname(__DIR__) . '/.env.supabase';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if (getenv($k) === false) putenv("$k=$v");
    }
}

$config = yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/common/config/main-local.php',
    require $root . '/api/config/main.php',
    require $root . '/api/config/main-local.php'
);
new yii\web\Application($config);

use api\modules\v1\models\User;
use api\modules\v1\models\PostSearch;
use api\modules\v1\models\UserLoginLog;

function tryStep(string $label, callable $fn): void {
    echo "\n=== $label ===\n";
    try {
        $r = $fn();
        echo "OK: " . (is_string($r) ? $r : json_encode($r, JSON_UNESCAPED_SLASHES)) . "\n";
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n";
        if ($e instanceof yii\db\Exception) {
            echo "SQL: " . $e->errorInfo[2] . "\n";
        }
    }
}

tryStep('checkLogin viewer', function () {
    $model = new User();
    $model->scenario = 'login';
    $model->email = 'viewer@dreamland.app';
    $model->password = 'demo123';
    if (!$model->validate()) {
        return ['errors' => $model->errors];
    }
    $user = $model->checkLogin();
    if (!$user) return 'checkLogin returned false';
    return ['id' => $user->id, 'email' => $user->email];
});

tryStep('getProfile viewer', function () {
    $model = new User();
    return $model->getProfile(2)->toArray();
});

tryStep('UserLoginLog save', function () {
    $log = new UserLoginLog();
    $log->user_id = 2;
    $log->login_mode = UserLoginLog::LOGIN_MODE_MANUALLY;
    $log->device_type = 3;
    $log->login_ip = '127.0.0.1';
    $log->created_at = time();
    if (!$log->save(false)) return 'save failed';
    return ['id' => $log->id];
});

tryStep('feed search', function () {
    $model = new PostSearch();
    $model->is_reel = 1;
    $model->is_ai_feed = 1;
    $dp = $model->search(['is_reel' => 1, 'is_ai_feed' => 1, 'page' => 1]);
    $models = $dp->getModels();
    return ['count' => count($models), 'first_id' => $models[0]->id ?? null];
});

tryStep('register save', function () {
    $ts = time();
    $model = new User();
    $model->scenario = 'register';
    $model->username = 'localtest' . $ts;
    $model->email = 'localtest' . $ts . '@example.com';
    $model->password = 'demo123';
    $model->name = 'Local Test';
    $model->device_type = '3';
    $model->role = User::ROLE_CUSTOMER;
    $model->status = User::STATUS_ACTIVE;
    $model->is_email_verified = User::IS_EMAIL_VERIFIED_YES;
    $model->account_created_with = 1;
    if (!$model->validate()) return ['errors' => $model->errors];
    if (!$model->save()) return ['save_errors' => $model->errors];
    return ['id' => $model->id];
});
