<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\HashTag;
use api\modules\v1\models\Post;
use api\modules\v1\models\PostSearch;
use api\modules\v1\models\ProfileCategoryType;
use api\modules\v1\models\User;
use common\models\DreamlandSetting;
use common\helpers\DreamlandUploadLimits;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class DreamlandMetaController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'optional' => ['settings', 'categories', 'profile', 'search', 'user-reels'],
            'authMethods' => [HttpBearerAuth::className()],
        ];
        return $behaviors;
    }

    /** GET /v1/dreamland-meta/settings */
    public function actionSettings()
    {
        $s = DreamlandSetting::getSettings();
        $uploadLimits = DreamlandUploadLimits::forApi();
        return [
            'preview_seconds' => (int) ($s->preview_seconds ?? 3),
            'streak_freeze_cost' => (int) ($s->streak_freeze_cost ?? 5),
            'streak_watch_threshold_seconds' => (int) ($s->streak_watch_threshold_seconds ?? 300),
            'platform_commission_percent' => (int) ($s->platform_commission_percent ?? 20),
            'vapid_public_key' => (string) ($s->vapid_public_key ?? ''),
            'push_enabled' => !empty($s->vapid_public_key),
            'max_reel_duration_seconds' => $uploadLimits['max_reel_duration_seconds'],
            'max_reel_upload_mb' => $uploadLimits['max_reel_upload_mb'],
            'max_reel_upload_bytes' => $uploadLimits['max_reel_upload_bytes'],
            'max_live_duration_seconds' => $uploadLimits['max_live_duration_seconds'],
            'live_signaling_url' => (string) (Yii::$app->params['dreamlandLiveSignalingUrl'] ?? 'http://localhost:4443'),
            'live_enabled' => Yii::$app->has('dreamlandLive') ? Yii::$app->dreamlandLive->isHealthy() : false,
            'moderation_agent_url' => (string) (Yii::$app->params['dreamlandModerationAgentUrl'] ?? 'http://localhost:4444'),
            'moderation_enabled' => Yii::$app->has('dreamlandModeration') ? Yii::$app->dreamlandModeration->isHealthy() : false,
            'ai_enabled' => Yii::$app->has('dreamlandAi') ? Yii::$app->dreamlandAi->isEnabled() : false,
            'ai_provider' => 'google-gemini',
            'gemini_model' => (string) (Yii::$app->params['dreamlandGeminiModel'] ?? 'gemini-2.0-flash'),
            'gemini_multimodal' => true,
            'ai_capabilities' => $this->aiCapabilities(),
            'dev_mode' => (bool) (Yii::$app->params['dreamlandDevMode'] ?? false),
            'api_base' => rtrim((string) (Yii::$app->params['siteUrl'] ?? 'http://localhost:8080'), '/') . '/v1',
            'uploads_base' => rtrim((string) (Yii::$app->params['siteUrl'] ?? 'http://localhost:8080'), '/') . '/frontend/web/uploads/image',
        ];
    }

    /** GET /v1/dreamland-meta/categories */
    public function actionCategories()
    {
        $items = ProfileCategoryType::find()
            ->where(['status' => ProfileCategoryType::STATUS_ACTIVE])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        return [
            'categories' => array_map(static function ($row) {
                return ['id' => (int) $row->id, 'name' => $row->name];
            }, $items),
        ];
    }

    /** GET /v1/dreamland-meta/profile?user_id= */
    public function actionProfile()
    {
        $userId = (int) Yii::$app->request->get('user_id', 0);
        if (!$userId) {
            return ['statusCode' => 422, 'message' => 'user_id is required.'];
        }
        $user = User::findOne(['id' => $userId, 'status' => User::STATUS_ACTIVE]);
        if (!$user) {
            return ['statusCode' => 404, 'message' => 'Profile not found.'];
        }
        return [
            'profile' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'bio' => $user->bio,
                'picture' => $user->picture ?? null,
                'dreamland_account_type' => $user->dreamland_account_type ?? null,
            ],
        ];
    }

    /** GET /v1/dreamland-meta/search?q= */
    public function actionSearch()
    {
        $q = trim((string) Yii::$app->request->get('q', ''));
        if (strlen($q) < 2) {
            return ['message' => 'ok', 'users' => [], 'reels' => [], 'hashtags' => []];
        }

        $users = User::find()
            ->select(['id', 'username', 'name', 'bio', 'picture'])
            ->where(['status' => User::STATUS_ACTIVE])
            ->andWhere([
                'or',
                ['like', 'username', $q],
                ['like', 'name', $q],
            ])
            ->limit(12)
            ->asArray()
            ->all();

        $reels = Post::find()
            ->select(['id', 'user_id', 'title', 'description', 'total_view', 'total_like', 'created_at'])
            ->where(['status' => Post::STATUS_ACTIVE, 'type' => Post::TYPE_REEL])
            ->andWhere([
                'or',
                ['like', 'title', $q],
                ['like', 'description', $q],
            ])
            ->orderBy(['id' => SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        $hashtags = HashTag::find()
            ->select(['hashtag', 'COUNT(*) AS count'])
            ->where(['like', 'hashtag', $q . '%', false])
            ->andWhere(['<>', 'hashtag', ''])
            ->groupBy('hashtag')
            ->orderBy(['count' => SORT_DESC])
            ->limit(12)
            ->asArray()
            ->all();

        return [
            'message' => 'ok',
            'query' => $q,
            'users' => $users,
            'reels' => $reels,
            'hashtags' => $hashtags,
        ];
    }

    /** GET /v1/dreamland-meta/user-reels?user_id=&page= */
    public function actionUserReels()
    {
        $userId = (int) Yii::$app->request->get('user_id', 0);
        if (!$userId) {
            return ['statusCode' => 422, 'message' => 'user_id is required.'];
        }

        $search = new PostSearch();
        $provider = $search->search([
            'user_id' => $userId,
            'is_reel' => 1,
            'page' => (int) Yii::$app->request->get('page', 1) - 1,
        ]);

        return [
            'message' => 'ok',
            'post' => $provider,
        ];
    }

    /** @return array<int, string> */
    private function aiCapabilities(): array
    {
        if (!Yii::$app->has('dreamlandAi')) {
            return [];
        }
        try {
            return Yii::$app->dreamlandAi->getStatus()['capabilities'] ?? [];
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return [];
        }
    }
}
