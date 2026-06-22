<?php
/**
 * Yii admin boot test — visit /admin/boot-test.php then delete this file.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$yiiRoot = dirname(__DIR__, 2) . '/dreamland';
require $yiiRoot . '/common/config/load-dotenv.php';
require $yiiRoot . '/common/config/render-https.php';
require $yiiRoot . '/vendor/autoload.php';
require $yiiRoot . '/vendor/yiisoft/yii2/Yii.php';
require $yiiRoot . '/common/config/bootstrap.php';

Yii::setAlias('@webroot', $yiiRoot . '/backend/web');
Yii::setAlias('@web', '/admin');

$config = yii\helpers\ArrayHelper::merge(
    require $yiiRoot . '/common/config/main.php',
    require $yiiRoot . '/common/config/main-local.php',
    require $yiiRoot . '/backend/config/main.php',
    require $yiiRoot . '/backend/config/main-local.php',
    require $yiiRoot . '/deploy/cpanel/config/backend-subdir.php'
);

try {
    $app = new yii\web\Application($config);
    echo "Yii boot: OK\n";
    $app->get('db')->open();
    echo "DB open: OK\n";
    $users = (int) $app->db->createCommand('SELECT COUNT(*) FROM user')->queryScalar();
    echo "user table rows: {$users}\n";

    $key = (string) $app->request->cookieValidationKey;
    echo 'cookieValidationKey: ' . ($key !== '' ? 'OK (' . strlen($key) . ' chars)' : 'MISSING') . "\n";

    $app->session->open();
    echo "session open: OK\n";
    echo 'session savePath: ' . $app->session->savePath . "\n";

    $token = $app->request->csrfToken;
    echo "csrf token: OK\n";

    backend\assets\AdminLteAsset::register($app->view);
    echo "AdminLteAsset register: OK\n";

    echo "\n--- login page render test ---\n";
    ob_start();
    $app->runAction('site/login');
    $out = ob_get_clean();
    $body = $app->response->data ?? $out;
    if (is_string($body) && strlen($body) > 100) {
        echo 'site/login render: OK (' . strlen($body) . " bytes)\n";
    } else {
        echo "site/login render: unexpected output\n";
        echo substr((string) $body, 0, 500) . "\n";
    }

    $logFile = $yiiRoot . '/backend/runtime/logs/app.log';
    if (is_file($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $tail = array_slice($lines ?: [], -8);
        echo "\n--- app.log (last lines) ---\n";
        echo implode("\n", $tail) . "\n";
    }

    echo "\nIf all OK, login should work at /admin/site/login\n";
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
