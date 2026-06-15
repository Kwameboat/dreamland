<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\User;
use common\models\PurchasedLive;
use common\models\PurchasedVideo;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class ViewerController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\User';

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
     * GET /v1/viewer/dashboard
     */
    public function actionDashboard()
    {
        $user = Yii::$app->user->identity;
        $userId = (int) $user->id;

        $unlocks = (int) PurchasedVideo::find()->where(['user_id' => $userId])->count();
        $spent = (int) PurchasedVideo::find()->where(['user_id' => $userId])->sum('credits_paid');
        $liveUnlocks = 0;
        $liveSpent = 0;
        if ($this->hasPurchasedLivesTable()) {
            $liveUnlocks = (int) PurchasedLive::find()->where(['user_id' => $userId])->count();
            $liveSpent = (int) PurchasedLive::find()->where(['user_id' => $userId])->sum('credits_paid');
        }

        return [
            'message' => 'Viewer dashboard loaded.',
            'viewer' => [
                'id' => $userId,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'available_coin' => (int) $user->available_coin,
                'dreamland_account_type' => $this->resolveAccountType($user),
            ],
            'stats' => [
                'credits_balance' => (int) $user->available_coin,
                'premium_unlocks' => $unlocks,
                'live_unlocks' => $liveUnlocks,
                'credits_spent' => $spent + $liveSpent,
                'daily_streak' => (int) ($user->current_streak ?? 0),
            ],
        ];
    }

    private function hasPurchasedLivesTable(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $cached = Yii::$app->db->schema->getTableSchema('purchased_lives', true) !== null;
        return $cached;
    }

    private function resolveAccountType($user): string
    {
        if (isset($user->dreamland_account_type) && $user->dreamland_account_type) {
            return (string) $user->dreamland_account_type;
        }
        return (int) $user->role === User::ROLE_AGENT ? 'creator' : 'viewer';
    }
}
