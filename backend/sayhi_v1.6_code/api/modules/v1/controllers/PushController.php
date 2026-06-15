<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\User;
use common\models\WebPushSubscription;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class PushController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    /** POST /v1/push/register — save PWA Web Push subscription (works when added to home screen). */
    public function actionRegister()
    {
        $payload = json_decode(Yii::$app->request->rawBody, true);
        if (!is_array($payload)) {
            return ['statusCode' => 422, 'message' => 'Invalid JSON body.'];
        }

        $userId = (int) Yii::$app->user->identity->id;
        try {
            WebPushSubscription::upsertForUser(
                $userId,
                $payload,
                substr((string) Yii::$app->request->userAgent, 0, 512)
            );
        } catch (\InvalidArgumentException $e) {
            return ['statusCode' => 422, 'message' => $e->getMessage()];
        }

        User::updateAll(['is_push_notification_allow' => 1], ['id' => $userId]);

        return [
            'message' => 'Push notifications enabled for this device.',
            'data' => ['subscribed' => true],
        ];
    }

    /** POST /v1/push/unregister */
    public function actionUnregister()
    {
        $userId = (int) Yii::$app->user->identity->id;
        WebPushSubscription::deactivateForUser($userId);
        return ['message' => 'Push notifications disabled for this account on all browsers.'];
    }
}
