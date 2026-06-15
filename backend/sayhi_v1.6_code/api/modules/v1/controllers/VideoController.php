<?php

namespace api\modules\v1\controllers;

use common\components\DreamlandPaywallService;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class VideoController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Post';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    /**
     * POST /api/videos/unlock
     */
    public function actionUnlock()
    {
        $userId = Yii::$app->user->identity->id;
        $body = Yii::$app->request->getBodyParams();
        $videoId = (int) ($body['video_id'] ?? 0);
        if (!$videoId) {
            return ['statusCode' => 422, 'message' => 'video_id is required.'];
        }

        /** @var DreamlandPaywallService $service */
        $service = Yii::$app->dreamlandPaywall;
        $result = $service->unlockVideo($userId, $videoId);
        if (!$result['ok']) {
            return ['statusCode' => 422, 'message' => $result['error']];
        }

        return array_merge(['message' => 'Video unlocked successfully.'], $result);
    }
}
