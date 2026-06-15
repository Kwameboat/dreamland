<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Post;
use common\components\DreamlandContentReview;
use common\components\DreamlandPaywallService;
use common\models\GroupWatchPot;
use common\models\VideoPrediction;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class AdminAppraisalController extends ActiveController
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

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $role = (int) Yii::$app->user->identity->role;
        if (!in_array($role, [1, 2], true)) {
            Yii::$app->response->statusCode = 403;
            Yii::$app->response->data = ['statusCode' => 403, 'message' => 'Admin access required.'];
            return false;
        }
        return true;
    }

    /**
     * GET /api/admin/appraisal/queue
     */
    public function actionQueue()
    {
        $posts = Post::find()
            ->where(['appraisal_status' => 'pending_review'])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit(100)
            ->all();

        return [
            'message' => 'ok',
            'queue' => $posts,
        ];
    }

    /**
     * POST /api/admin/appraisal/evaluate
     */
    public function actionEvaluate()
    {
        $body = Yii::$app->request->getBodyParams();
        $videoId = (int) ($body['video_id'] ?? 0);
        $status = (string) ($body['status'] ?? '');
        $priceCredits = isset($body['price_credits']) ? (int) $body['price_credits'] : null;

        if (!$videoId || !in_array($status, ['active', 'rejected'], true)) {
            return ['statusCode' => 422, 'message' => 'video_id and status (active|rejected) are required.'];
        }

        $post = Post::findOne($videoId);
        if (!$post) {
            return ['statusCode' => 404, 'message' => 'Video not found.'];
        }

        if ($status === 'active') {
            if ($priceCredits === null || $priceCredits <= 0) {
                return ['statusCode' => 422, 'message' => 'price_credits is required when approving paid content.'];
            }
            $post->price_credits = $priceCredits;
            $post->appraisal_status = 'active';
            $post->status = Post::STATUS_ACTIVE;

            $pot = new GroupWatchPot([
                'video_id' => $post->id,
                'target_unlocks' => 100,
                'current_unlocks' => 0,
                'bonus_pool_credits' => max(50, (int) floor($priceCredits * 0.5)),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
                'status' => GroupWatchPot::STATUS_OPEN,
            ]);
            $pot->save(false);

            $prediction = new VideoPrediction([
                'video_id' => $post->id,
                'target_metric' => '10000 views',
                'target_value' => 10000,
                'timer_expires_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
                'status' => VideoPrediction::STATUS_OPEN,
                'outcome' => 'pending',
            ]);
            $prediction->save(false);
        } else {
            $reason = trim((string) ($body['rejection_reason'] ?? ''));
            if ($reason === '') {
                return ['statusCode' => 422, 'message' => 'rejection_reason is required when rejecting content.'];
            }
            try {
                DreamlandContentReview::rejectPost($post, $reason, (int) Yii::$app->user->identity->id);
            } catch (\InvalidArgumentException $e) {
                return ['statusCode' => 422, 'message' => $e->getMessage()];
            }
            return ['message' => 'Video rejected and creator notified.', 'video' => $post];
        }

        $post->save(false);
        return ['message' => 'Video appraisal updated.', 'video' => $post];
    }
}
