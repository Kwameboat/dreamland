<?php

namespace backend\controllers;

use common\models\DreamlandSetting;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;

class DreamlandSettingsController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => ['class' => VerbFilter::className(), 'actions' => ['update' => ['POST']]],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [[
                    'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::DREAMLAND_SETTINGS),
                    'roles' => ['@'],
                ]],
            ],
        ];
    }

    public function actionIndex()
    {
        $migrationOk = DreamlandSetting::ensureColumns();
        $model = DreamlandSetting::getSettings();

        if (!$migrationOk) {
            Yii::$app->session->setFlash(
                'warning',
                'Some settings columns are missing in the database. Run on cPanel: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-dreamland-settings.sh | bash'
            );
        }

        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('generate_vapid')) {
                return $this->generateVapidKeys($model);
            }
            $model->load(Yii::$app->request->post());
            DreamlandSetting::ensureColumns();
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Dreamland settings saved.');
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Could not save settings. Run fix-dreamland-settings.sh on the server to repair the database.'
                );
            }
            return $this->refresh();
        }
        return $this->render('index', ['model' => $model]);
    }

    protected function generateVapidKeys(DreamlandSetting $model)
    {
        DreamlandSetting::ensureColumns();

        $opensslConf = Yii::getAlias('@app/../openssl.cnf');
        if (is_file($opensslConf)) {
            putenv('OPENSSL_CONF=' . $opensslConf);
        }

        try {
            $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
            if ($model->hasAttribute('vapid_public_key')) {
                $model->vapid_public_key = $keys['publicKey'];
            }
            if ($model->hasAttribute('vapid_private_key')) {
                $model->vapid_private_key = $keys['privateKey'];
            }
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Web Push VAPID keys generated.');
        } catch (\Throwable $e) {
            $public = getenv('DREAMLAND_VAPID_PUBLIC') ?: '';
            $private = getenv('DREAMLAND_VAPID_PRIVATE') ?: '';
            if ($public && $private) {
                if ($model->hasAttribute('vapid_public_key')) {
                    $model->vapid_public_key = $public;
                }
                if ($model->hasAttribute('vapid_private_key')) {
                    $model->vapid_private_key = $private;
                }
                $model->save(false);
                Yii::$app->session->setFlash('success', 'Web Push VAPID keys loaded from environment.');
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Could not generate VAPID keys. Run: php scripts/generate-vapid-keys.php (requires OpenSSL EC), or set DREAMLAND_VAPID_PUBLIC / DREAMLAND_VAPID_PRIVATE.'
                );
            }
        }

        return $this->redirect(['index']);
    }
}
