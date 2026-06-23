<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Post;
use common\components\DreamlandAppraisalService;
use common\components\DreamlandContentReview;
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
            $isPaid = (int) $post->is_paid === 1;
            if ($isPaid && ($priceCredits === null || $priceCredits <= 0)) {
                return ['statusCode' => 422, 'message' => 'price_credits is required when approving premium content.'];
            }
            try {
                DreamlandAppraisalService::approvePost($post, (int) ($priceCredits ?? 0));
            } catch (\InvalidArgumentException $e) {
                return ['statusCode' => 422, 'message' => $e->getMessage()];
            } catch (\Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
                return ['statusCode' => 500, 'message' => 'Could not approve video: ' . $e->getMessage()];
            }
            return ['message' => 'Video appraisal updated.', 'video' => $post];
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
    }
}
