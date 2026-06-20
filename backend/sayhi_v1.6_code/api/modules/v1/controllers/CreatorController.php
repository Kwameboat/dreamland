<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Post;
use api\modules\v1\models\PostGallary;
use api\modules\v1\models\User;
use api\modules\v1\models\UserLiveHistory;
use common\components\DreamlandContentReview;
use common\helpers\DreamlandCreatorApproval;
use common\helpers\DreamlandUploadLimits;
use common\models\PurchasedLive;
use common\models\PurchasedVideo;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\web\UploadedFile;

class CreatorController extends ActiveController
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
     * GET /v1/creator/dashboard
     */
    public function actionDashboard()
    {
        $deny = $this->requireCreator();
        if ($deny) {
            return $deny;
        }

        $user = Yii::$app->user->identity;
        $userId = (int) $user->id;

        $posts = Post::find()
            ->where([
                'user_id' => $userId,
                'type' => Post::TYPE_REEL,
            ])
            ->andWhere(['<>', 'status', Post::STATUS_DELETED])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $videoIds = array_map(static function ($post) {
            return (int) $post->id;
        }, $posts);

        $purchaseMap = [];
        if ($videoIds) {
            $rows = PurchasedVideo::find()
                ->select([
                    'video_id',
                    'unlock_count' => 'COUNT(*)',
                    'earned_credits' => 'SUM(creator_credits)',
                ])
                ->where(['video_id' => $videoIds])
                ->groupBy('video_id')
                ->asArray()
                ->all();

            foreach ($rows as $row) {
                $purchaseMap[(int) $row['video_id']] = [
                    'unlocks' => (int) $row['unlock_count'],
                    'earned_credits' => (int) $row['earned_credits'],
                ];
            }
        }

        $reels = [];
        $totals = [
            'reels' => 0,
            'views' => 0,
            'likes' => 0,
            'comments' => 0,
            'unlocks' => 0,
            'earned_credits' => 0,
            'premium_reels' => 0,
        ];

        foreach ($posts as $post) {
            $stats = $purchaseMap[(int) $post->id] ?? ['unlocks' => 0, 'earned_credits' => 0];
            $isPaid = (int) $post->is_paid === 1;

            $reels[] = array_merge([
                'id' => (int) $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'status' => (int) $post->status,
                'is_paid' => $isPaid,
                'price_credits' => $isPaid ? (int) $post->price_credits : null,
                'appraisal_status' => $post->appraisal_status,
                'total_view' => (int) $post->total_view,
                'total_like' => (int) $post->total_like,
                'total_comment' => (int) $post->total_comment,
                'unlocks' => $stats['unlocks'],
                'earned_credits' => $stats['earned_credits'],
                'created_at' => (int) $post->created_at,
            ], DreamlandContentReview::serializeReelReviewFields($post));

            $totals['reels']++;
            $totals['views'] += (int) $post->total_view;
            $totals['likes'] += (int) $post->total_like;
            $totals['comments'] += (int) $post->total_comment;
            $totals['unlocks'] += $stats['unlocks'];
            $totals['earned_credits'] += $stats['earned_credits'];
            if ($isPaid) {
                $totals['premium_reels']++;
            }
        }

        $live = $this->findActiveLive($userId);
        $liveEarnings = $this->sumLiveEarnings($userId);

        return [
            'message' => 'Creator dashboard loaded.',
            'creator' => [
                'id' => $userId,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => (int) $user->role,
                'available_coin' => (int) $user->available_coin,
                'dreamland_account_type' => 'creator',
                'dreamland_creator_status' => $this->resolveCreatorStatus($user),
            ],
            'totals' => array_merge($totals, [
                'live_earned_credits' => $liveEarnings,
                'earned_credits' => $totals['earned_credits'] + $liveEarnings,
            ]),
            'reels' => $reels,
            'live' => $live ? $this->serializeLive($live, 'host') : null,
        ];
    }

    /** GET /v1/creator/analytics */
    public function actionAnalytics()
    {
        $deny = $this->requireCreator();
        if ($deny) {
            return $deny;
        }

        $dashboard = $this->actionDashboard();
        if (!empty($dashboard['statusCode'])) {
            return $dashboard;
        }

        $reels = $dashboard['reels'] ?? [];
        $series = array_map(static function ($reel) {
            $views = max(1, (int) ($reel['total_view'] ?? 0));
            $likes = (int) ($reel['total_like'] ?? 0);
            return [
                'id' => (int) $reel['id'],
                'title' => (string) ($reel['title'] ?? ''),
                'views' => (int) ($reel['total_view'] ?? 0),
                'likes' => $likes,
                'unlocks' => (int) ($reel['unlocks'] ?? 0),
                'earned_credits' => (int) ($reel['earned_credits'] ?? 0),
                'engagement_rate' => round(($likes / $views) * 100, 1),
            ];
        }, $reels);

        return [
            'message' => 'ok',
            'totals' => $dashboard['totals'] ?? [],
            'series' => $series,
        ];
    }

    /**
     * POST /v1/creator/upload-reel (multipart: videoFile, title, description, is_paid)
     */
    public function actionUploadReel()
    {
        $deny = $this->requireApprovedCreator();
        if ($deny) {
            return $deny;
        }

        $userId = (int) Yii::$app->user->identity->id;

        $uploadError = $this->resolveVideoUploadError('videoFile');
        if ($uploadError !== null) {
            return $uploadError;
        }

        $videoFile = UploadedFile::getInstanceByName('videoFile');
        if (!$videoFile) {
            return ['statusCode' => 422, 'message' => 'videoFile is required.'];
        }
        if ($videoFile->hasError) {
            return $this->resolveVideoUploadError('videoFile') ?? [
                'statusCode' => 422,
                'message' => 'Video upload failed.',
            ];
        }

        $limitError = DreamlandUploadLimits::validateVideoFile($videoFile);
        if ($limitError !== null) {
            return $limitError;
        }

        $title = trim((string) Yii::$app->request->post('title', 'New Dreamland reel'));
        $description = trim((string) Yii::$app->request->post('description', ''));
        $isPaid = (int) Yii::$app->request->post('is_paid', 0) === 1;
        $categoryId = (int) Yii::$app->request->post('profile_category_id', 0);

        if ($categoryId <= 0) {
            return ['statusCode' => 422, 'message' => 'profile_category_id (genre) is required.'];
        }

        if ($title === '') {
            $title = 'New Dreamland reel';
        }

        $uploaded = Yii::$app->fileUpload->uploadFile(
            $videoFile,
            Yii::$app->fileUpload::TYPE_POST,
            false
        );
        if (empty($uploaded[0]['file'])) {
            $msg = !empty($uploaded[0]['isProhabited'])
                ? 'Video was blocked by content moderation.'
                : 'Video upload failed — check API storage permissions.';
            return ['statusCode' => 422, 'message' => $msg];
        }

        $post = new Post();
        $post->user_id = $userId;
        $post->type = Post::TYPE_REEL;
        $post->post_content_type = Post::CONTENT_TYPE_MEDIA;
        $post->title = $title;
        $post->description = $description;
        $post->display_whose = 1;
        $post->is_paid = $isPaid ? 1 : 0;
        $post->price_credits = null;
        $post->appraisal_status = 'pending_safety';
        $post->status = Post::STATUS_BLOCKED;
        if (property_exists($post, 'category_id') || $post->hasAttribute('category_id')) {
            $post->category_id = $categoryId;
        }

        if (!$post->save(false)) {
            return ['statusCode' => 422, 'message' => 'Could not create reel post.'];
        }

        $creator = User::findOne($userId);
        if ($creator) {
            $creator->profile_category_type = $categoryId;
            $creator->save(false);
        }

        $gallery = new PostGallary();
        $gallery->updateGallary($post->id, [[
            'type' => 1,
            'media_type' => PostGallary::MEDIA_TYPE_VIDEO,
            'filename' => $uploaded[0]['file'],
            'video_thumb' => '',
            'is_default' => 1,
            'width' => 0,
            'height' => 0,
        ]]);

        $galleryCount = (int) PostGallary::find()->where(['post_id' => $post->id])->count();
        if ($galleryCount < 1) {
            $post->delete();

            return ['statusCode' => 422, 'message' => 'Video could not be attached to your reel. Try again.'];
        }

        $publishMessage = $isPaid
            ? 'Premium reel uploaded — safety scan then admin review.'
            : 'Reel uploaded — safety scan in progress.';

        if (Yii::$app->has('dreamlandSafety')) {
            Yii::$app->dreamlandSafety->enqueueVideoScan($post, [
                'title' => $title,
                'description' => $description,
                'tags' => [],
            ]);

            if (!$isPaid && !empty(Yii::$app->params['dreamlandDevMode'])) {
                $textScan = Yii::$app->dreamlandSafety->runLocalTextScan(trim($title . "\n" . $description));
                $passed = (bool) ($textScan['passed'] ?? true);
                $decision = $textScan['decision'] ?? ($passed ? 'allow' : 'block');
                Yii::$app->dreamlandSafety->finalizeScan($post, $passed, null, $decision);
                $post->refresh();
                if ((int) $post->status === Post::STATUS_ACTIVE) {
                    $publishMessage = 'Reel is live in the feed.';
                }
            }
        }

        return [
            'message' => $publishMessage,
            'post_id' => (int) $post->id,
            'file_url' => $uploaded[0]['fileUrl'] ?? null,
            'is_paid' => $isPaid,
            'appraisal_status' => $post->appraisal_status,
            'status' => (int) $post->status,
        ];
    }

    /**
     * POST /v1/creator/start-live
     */
    public function actionStartLive()
    {
        $deny = $this->requireApprovedCreator();
        if ($deny) {
            return $deny;
        }

        $userId = (int) Yii::$app->user->identity->id;
        $existing = $this->findActiveLive($userId);
        if ($existing) {
            return [
                'message' => 'You are already live.',
                'live' => $this->serializeLive($existing, 'host'),
            ];
        }

        $title = trim((string) Yii::$app->request->post('title', 'Dreamland Live'));
        $isMonetized = (int) Yii::$app->request->post('is_monetized', 0) === 1;
        $priceCredits = (int) Yii::$app->request->post('price_credits', 0);

        if ($title === '') {
            $title = 'Dreamland Live';
        }
        if ($isMonetized && $priceCredits <= 0) {
            return ['statusCode' => 422, 'message' => 'Set price_credits when live is monetized.'];
        }
        if (!$isMonetized) {
            $priceCredits = null;
        }

        $live = new UserLiveHistory();
        $live->user_id = $userId;
        $live->status = UserLiveHistory::STATUS_ONGOING;
        $live->channel_name = 'dreamland_' . $userId . '_' . time();
        $live->token = Yii::$app->security->generateRandomString(32);
        $live->live_title = $title;
        $live->is_monetized = $isMonetized ? 1 : 0;
        $live->price_credits = $priceCredits;
        $live->total_comment = 0;

        if (!$live->save(false)) {
            return ['statusCode' => 422, 'message' => 'Could not start live session.'];
        }

        /** @var \common\components\DreamlandLiveRtcService $rtc */
        $rtc = Yii::$app->dreamlandLive;
        if (!$rtc->registerRoom((int) $live->id, $userId, (string) $live->token)) {
            $live->status = UserLiveHistory::STATUS_COMPLETED;
            $live->end_time = time();
            $live->save(false);
            return [
                'statusCode' => 503,
                'message' => 'Dreamland Live server is not running. Start it with start-live-server.ps1',
            ];
        }

        return [
            'message' => 'You are live on Dreamland.',
            'live' => $this->serializeLive($live, 'host'),
        ];
    }

    /**
     * POST /v1/creator/end-live
     */
    public function actionEndLive()
    {
        $deny = $this->requireCreator();
        if ($deny) {
            return $deny;
        }

        $userId = (int) Yii::$app->user->identity->id;
        $live = $this->findActiveLive($userId);
        if (!$live) {
            return ['statusCode' => 422, 'message' => 'No active live session found.'];
        }

        $live->status = UserLiveHistory::STATUS_COMPLETED;
        $live->end_time = time();
        $live->total_time = max(0, (int) $live->end_time - (int) $live->start_time);
        $live->save(false);

        if (Yii::$app->has('dreamlandLive')) {
            Yii::$app->dreamlandLive->closeRoom((int) $live->id);
        }

        return [
            'message' => 'Live ended.',
            'live' => $this->serializeLive($live),
        ];
    }

    /**
     * POST /v1/creator/appeal-reel
     */
    public function actionAppealReel()
    {
        $deny = $this->requireApprovedCreator();
        if ($deny) {
            return $deny;
        }

        $body = Yii::$app->request->getBodyParams();
        $postId = (int) ($body['post_id'] ?? 0);
        $message = trim((string) ($body['message'] ?? ''));

        if ($postId < 1 || $message === '') {
            return ['statusCode' => 422, 'message' => 'post_id and message are required to submit an appeal.'];
        }

        $userId = (int) Yii::$app->user->identity->id;
        $post = Post::findOne(['id' => $postId, 'user_id' => $userId]);
        if (!$post || $post->appraisal_status !== 'rejected') {
            return ['statusCode' => 404, 'message' => 'Rejected reel not found.'];
        }

        if (!DreamlandContentReview::hasRejectionColumns()) {
            return ['statusCode' => 503, 'message' => 'Appeals are not enabled yet. Run apply-dreamland-rejection-migration.php'];
        }

        if ($post->appeal_status === 'pending') {
            return ['statusCode' => 422, 'message' => 'An appeal is already pending review for this reel.'];
        }

        $post->appeal_status = 'pending';
        $post->appeal_message = mb_substr($message, 0, 2000);
        $post->appeal_submitted_at = time();
        $post->save(false);

        return [
            'message' => 'Appeal submitted. Our moderation team will review it shortly.',
            'reel' => array_merge([
                'id' => (int) $post->id,
                'appraisal_status' => $post->appraisal_status,
            ], DreamlandContentReview::serializeReelReviewFields($post)),
        ];
    }

    /**
     * GET /v1/creator/live-status
     */
    public function actionLiveStatus()
    {
        $deny = $this->requireCreator();
        if ($deny) {
            return $deny;
        }

        $userId = (int) Yii::$app->user->identity->id;
        $live = $this->findActiveLive($userId);

        return [
            'message' => $live ? 'Live session active.' : 'Not live.',
            'live' => $live ? $this->serializeLive($live, 'host') : null,
        ];
    }

    private function resolveVideoUploadError(string $field): ?array
    {
        if (!isset($_FILES[$field])) {
            return null;
        }
        $error = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_OK) {
            return null;
        }
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Video exceeds server limit (' . ini_get('upload_max_filesize') . '). Try a shorter clip or restart API with upload limits.',
            UPLOAD_ERR_FORM_SIZE => 'Video file is too large for this request.',
            UPLOAD_ERR_PARTIAL => 'Upload was interrupted — try again.',
            UPLOAD_ERR_NO_FILE => 'videoFile is required.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing — contact support.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not save upload — try again.',
        ];
        return [
            'statusCode' => 422,
            'message' => $messages[$error] ?? 'Video upload failed.',
        ];
    }

    private function requireCreator()
    {
        $user = Yii::$app->user->identity;
        if (!$this->isCreatorUser($user)) {
            return ['statusCode' => 403, 'message' => 'Creator account required.'];
        }
        return null;
    }

    private function requireApprovedCreator()
    {
        $deny = $this->requireCreator();
        if ($deny) {
            return $deny;
        }
        $user = Yii::$app->user->identity;
        if (!$this->isApprovedCreator($user)) {
            $status = $this->resolveCreatorStatus($user);
            if ($status === 'pending') {
                return ['statusCode' => 403, 'message' => 'Creator approval pending — uploading is disabled until approved.'];
            }
            if ($status === 'rejected') {
                return ['statusCode' => 403, 'message' => 'Creator application was not approved.'];
            }
            return ['statusCode' => 403, 'message' => 'Approved creator account required to publish content.'];
        }
        return null;
    }

    private function isCreatorUser($user): bool
    {
        if (!$user) {
            return false;
        }
        if (isset($user->dreamland_account_type) && $user->dreamland_account_type === 'creator') {
            return true;
        }
        return (int) $user->role === User::ROLE_AGENT;
    }

    private function isApprovedCreator($user): bool
    {
        if (!$user || !$this->isCreatorUser($user)) {
            return false;
        }
        $status = DreamlandCreatorApproval::resolveStatus($user);
        if ($status === DreamlandCreatorApproval::STATUS_APPROVED) {
            return true;
        }
        if (in_array($status, [DreamlandCreatorApproval::STATUS_PENDING, DreamlandCreatorApproval::STATUS_REJECTED], true)) {
            return false;
        }
        return isset($user->dreamland_account_type)
            && $user->dreamland_account_type === 'creator'
            && (int) $user->role === User::ROLE_AGENT;
    }

    private function resolveCreatorStatus($user): string
    {
        return DreamlandCreatorApproval::resolveStatus($user);
    }

    private function hasCreatorStatusColumn(): bool
    {
        return DreamlandCreatorApproval::hasCreatorStatusColumn();
    }

    private function findActiveLive(int $userId): ?UserLiveHistory
    {
        return UserLiveHistory::find()
            ->where(['user_id' => $userId, 'status' => UserLiveHistory::STATUS_ONGOING])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    private function serializeLive(UserLiveHistory $live, string $rtcRole = 'viewer'): array
    {
        $payload = [
            'id' => (int) $live->id,
            'channel_name' => $live->channel_name,
            'token' => $live->token,
            'title' => $live->live_title ?? 'Dreamland Live',
            'is_monetized' => (int) ($live->is_monetized ?? 0) === 1,
            'price_credits' => isset($live->price_credits) ? (int) $live->price_credits : null,
            'status' => (int) $live->status,
            'started_at' => (int) $live->start_time,
        ];

        if ((int) $live->status === UserLiveHistory::STATUS_ONGOING && Yii::$app->has('dreamlandLive')) {
            $payload['rtc'] = Yii::$app->dreamlandLive->clientConfig($live, $rtcRole);
        }

        return $payload;
    }

    private function sumLiveEarnings(int $userId): int
    {
        if (!Yii::$app->db->schema->getTableSchema('purchased_lives', true)) {
            return 0;
        }
        $liveIds = UserLiveHistory::find()
            ->select('id')
            ->where(['user_id' => $userId])
            ->column();
        if (!$liveIds) {
            return 0;
        }
        return (int) PurchasedLive::find()
            ->where(['live_id' => $liveIds])
            ->sum('creator_credits');
    }
}
