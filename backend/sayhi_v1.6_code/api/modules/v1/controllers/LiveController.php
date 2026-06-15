<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\LiveCallViewer;
use api\modules\v1\models\UserLiveHistory;
use common\models\PurchasedLive;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;

class LiveController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\UserLiveHistory';

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
            'optional' => ['list'],
        ];
        return $behaviors;
    }

    /**
     * GET /v1/live/list
     */
    public function actionList()
    {
        $viewerId = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->identity->id;

        $lives = UserLiveHistory::find()
            ->where(['status' => UserLiveHistory::STATUS_ONGOING])
            ->with(['user'])
            ->orderBy(['start_time' => SORT_DESC])
            ->limit(50)
            ->all();

        /** @var \common\components\DreamlandPaywallService $paywall */
        $paywall = Yii::$app->dreamlandPaywall;

        $items = [];
        foreach ($lives as $live) {
            $items[] = $this->serializeLiveCard($live, $paywall->decorateLiveItem($live, $viewerId));
        }

        return [
            'message' => count($items) ? 'Live streams found.' : 'No one is live right now.',
            'lives' => $items,
            'total' => count($items),
        ];
    }

    /**
     * GET /v1/live/watch?live_id=
     */
    public function actionWatch()
    {
        $liveId = (int) Yii::$app->request->get('live_id', 0);
        if (!$liveId) {
            return ['statusCode' => 422, 'message' => 'live_id is required.'];
        }

        $live = UserLiveHistory::find()
            ->where(['id' => $liveId, 'status' => UserLiveHistory::STATUS_ONGOING])
            ->with(['user'])
            ->one();

        if (!$live) {
            return ['statusCode' => 404, 'message' => 'Live stream not found or has ended.'];
        }

        $viewerId = (int) Yii::$app->user->identity->id;
        /** @var \common\components\DreamlandPaywallService $paywall */
        $paywall = Yii::$app->dreamlandPaywall;
        $dreamland = $paywall->decorateLiveItem($live, $viewerId);

        if (!$dreamland['is_unlocked']) {
            return [
                'statusCode' => 402,
                'message' => 'Unlock required to watch this live.',
                'live' => $this->serializeLiveCard($live, $dreamland),
                'dreamland' => $dreamland,
            ];
        }

        $viewerCount = (int) LiveCallViewer::find()->where(['live_call_id' => $live->id])->count();

        return [
            'message' => 'Live stream ready.',
            'live' => $this->serializeLiveWatch($live, $dreamland, $viewerCount),
        ];
    }

    /**
     * POST /v1/live/unlock
     */
    public function actionUnlock()
    {
        $userId = (int) Yii::$app->user->identity->id;
        $body = Yii::$app->request->getBodyParams();
        $liveId = (int) ($body['live_id'] ?? 0);
        if (!$liveId) {
            return ['statusCode' => 422, 'message' => 'live_id is required.'];
        }

        /** @var \common\components\DreamlandPaywallService $service */
        $service = Yii::$app->dreamlandPaywall;
        $result = $service->unlockLive($userId, $liveId);
        if (!$result['ok']) {
            return ['statusCode' => 422, 'message' => $result['error']];
        }

        return array_merge(['message' => 'Live unlocked — enjoy the stream.'], $result);
    }

    /**
     * POST /v1/live/join
     */
    public function actionJoin()
    {
        $userId = (int) Yii::$app->user->identity->id;
        $body = Yii::$app->request->getBodyParams();
        $liveId = (int) ($body['live_id'] ?? 0);
        if (!$liveId) {
            return ['statusCode' => 422, 'message' => 'live_id is required.'];
        }

        $live = UserLiveHistory::findOne(['id' => $liveId, 'status' => UserLiveHistory::STATUS_ONGOING]);
        if (!$live) {
            return ['statusCode' => 404, 'message' => 'Live stream not found or has ended.'];
        }

        /** @var \common\components\DreamlandPaywallService $paywall */
        $paywall = Yii::$app->dreamlandPaywall;
        $dreamland = $paywall->decorateLiveItem($live, $userId);
        if (!$dreamland['is_unlocked']) {
            return ['statusCode' => 402, 'message' => 'Unlock this live before joining.', 'dreamland' => $dreamland];
        }

        $existing = LiveCallViewer::findOne(['live_call_id' => $liveId, 'user_id' => $userId]);
        if (!$existing) {
            $viewer = new LiveCallViewer();
            $viewer->live_call_id = $liveId;
            $viewer->user_id = $userId;
            $viewer->role = 2;
            $viewer->save(false);
        }

        $viewerCount = (int) LiveCallViewer::find()->where(['live_call_id' => $liveId])->count();

        $rtc = null;
        if (Yii::$app->has('dreamlandLive')) {
            $rtc = Yii::$app->dreamlandLive->clientConfig($live, 'viewer');
        }

        return [
            'message' => 'Joined live stream.',
            'live_id' => $liveId,
            'viewer_count' => $viewerCount,
            'channel_name' => $live->channel_name,
            'token' => $live->token,
            'rtc' => $rtc,
        ];
    }

    private function serializeLiveCard(UserLiveHistory $live, array $dreamland): array
    {
        $host = $live->user;
        return [
            'id' => (int) $live->id,
            'user_id' => (int) $live->user_id,
            'title' => $live->live_title ?? 'Dreamland Live',
            'channel_name' => $live->channel_name,
            'started_at' => (int) $live->start_time,
            'total_comment' => (int) ($live->total_comment ?? 0),
            'creator' => [
                'id' => (int) ($host->id ?? $live->user_id),
                'name' => $host->name ?? $host->username ?? 'Creator',
                'username' => $host->username ?? 'creator',
                'picture' => $host->picture ?? null,
            ],
            'dreamland' => $dreamland,
        ];
    }

    private function serializeLiveWatch(UserLiveHistory $live, array $dreamland, int $viewerCount): array
    {
        $card = $this->serializeLiveCard($live, $dreamland);
        $card['viewer_count'] = $viewerCount;
        $card['token'] = $live->token;
        $card['status'] = (int) $live->status;
        if (Yii::$app->has('dreamlandLive')) {
            $card['rtc'] = Yii::$app->dreamlandLive->clientConfig($live, 'viewer');
        }
        return $card;
    }
}
