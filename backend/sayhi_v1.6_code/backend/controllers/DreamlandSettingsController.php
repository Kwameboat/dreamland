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
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    public function actionIndex()
    {
        DreamlandSetting::ensureColumns();
        $model = DreamlandSetting::getSettings();
        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('generate_vapid')) {
                return $this->generateVapidKeys($model);
            }
            $model->load(Yii::$app->request->post());
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Dreamland settings saved.');
            }
            return $this->refresh();
        }
        return $this->render('index', ['model' => $model]);
    }

    protected function generateVapidKeys(DreamlandSetting $model)
    {
        $opensslConf = Yii::getAlias('@app/../openssl.cnf');
        if (is_file($opensslConf)) {
            putenv('OPENSSL_CONF=' . $opensslConf);
        }

        try {
            $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
            $model->vapid_public_key = $keys['publicKey'];
            $model->vapid_private_key = $keys['privateKey'];
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Web Push VAPID keys generated.');
        } catch (\Throwable $e) {
            $public = getenv('DREAMLAND_VAPID_PUBLIC') ?: '';
            $private = getenv('DREAMLAND_VAPID_PRIVATE') ?: '';
            if ($public && $private) {
                $model->vapid_public_key = $public;
                $model->vapid_private_key = $private;
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
