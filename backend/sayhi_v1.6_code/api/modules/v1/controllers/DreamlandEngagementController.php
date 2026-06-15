<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\Follower;
use api\modules\v1\models\Notification;
use api\modules\v1\models\Post;
use api\modules\v1\models\PostLike;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class DreamlandEngagementController extends Controller
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

    /** POST /v1/dreamland-engagement/toggle-like */
    public function actionToggleLike()
    {
        $userId = (int) Yii::$app->user->identity->id;
        $postId = (int) Yii::$app->request->getBodyParam('post_id', 0);
        if (!$postId) {
            return ['statusCode' => 422, 'message' => 'post_id is required.'];
        }

        $post = Post::findOne(['id' => $postId, 'status' => Post::STATUS_ACTIVE]);
        if (!$post) {
            return ['statusCode' => 404, 'message' => 'Reel not found.'];
        }

        $existing = PostLike::find()->where(['post_id' => $postId, 'user_id' => $userId])->one();
        $modelPost = new Post();

        if ($existing) {
            $existing->delete();
            $totalLike = $modelPost->updateLikeCounter($postId, 'unlike');
            return [
                'message' => 'Unliked.',
                'liked' => false,
                'total_like' => (int) $totalLike,
            ];
        }

        $like = new PostLike();
        $like->post_id = $postId;
        $like->user_id = $userId;
        $like->created_at = time();
        if (!$like->save(false)) {
            return ['statusCode' => 422, 'message' => 'Could not like reel.'];
        }

        $totalLike = $modelPost->updateLikeCounter($postId);
        $this->notifyLike($post, $userId);

        return [
            'message' => 'Liked.',
            'liked' => true,
            'total_like' => (int) $totalLike,
        ];
    }

    /** POST /v1/dreamland-engagement/share-bump */
    public function actionShareBump()
    {
        $postId = (int) Yii::$app->request->getBodyParam('post_id', 0);
        if (!$postId) {
            return ['statusCode' => 422, 'message' => 'post_id is required.'];
        }

        $post = Post::findOne(['id' => $postId, 'status' => Post::STATUS_ACTIVE]);
        if (!$post) {
            return ['statusCode' => 404, 'message' => 'Reel not found.'];
        }

        $modelPost = new Post();
        $totalShare = $modelPost->updateShareCounter($postId);

        return [
            'message' => 'Share recorded.',
            'total_share' => (int) $totalShare,
        ];
    }

    /** GET /v1/dreamland-engagement/liked-ids?post_ids=1,2,3 */
    public function actionLikedIds()
    {
        $userId = (int) Yii::$app->user->identity->id;
        $raw = (string) Yii::$app->request->get('post_ids', '');
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        if (!$ids) {
            return ['message' => 'ok', 'liked_ids' => []];
        }

        $liked = PostLike::find()
            ->select(['post_id'])
            ->where(['user_id' => $userId, 'post_id' => $ids])
            ->column();

        return [
            'message' => 'ok',
            'liked_ids' => array_map('intval', $liked),
        ];
    }

    /** POST /v1/dreamland-engagement/record-watch */
    public function actionRecordWatch()
    {
        $userId = (int) Yii::$app->user->identity->id;
        $body = Yii::$app->request->bodyParams;
        $postId = (int) ($body['post_id'] ?? 0);
        $watchMs = max(0, (int) ($body['watch_ms'] ?? 0));
        $completionPct = min(100, max(0, (int) ($body['completion_pct'] ?? 0)));
        $seconds = max(1, (int) ceil($watchMs / 1000));

        if (Yii::$app->has('dreamlandStreak')) {
            Yii::$app->dreamlandStreak->recordWatchSeconds($userId, $seconds);
        }

        if ($postId > 0 && $this->watchEventsTableExists()) {
            Yii::$app->db->createCommand()->insert('post_watch_events', [
                'user_id' => $userId,
                'post_id' => $postId,
                'watch_ms' => $watchMs,
                'completion_pct' => $completionPct,
                'rewatched' => !empty($body['rewatched']) ? 1 : 0,
                'created_at' => time(),
            ])->execute();
        }

        return ['message' => 'ok'];
    }

    private function notifyLike(Post $post, int $fromUserId): void
    {
        if ((int) $post->user_id === $fromUserId) {
            return;
        }

        $modelFollower = new Follower();
        $isFollowing = $modelFollower->find()
            ->where(['user_id' => $fromUserId, 'follower_id' => (int) $post->user_id])
            ->count();

        $modelNotification = new Notification();
        $notificationData = Yii::$app->params['pushNotificationMessage']['likePost'] ?? [
            'title' => 'New like',
            'body' => '{USER} liked your reel',
        ];
        $replaceContent = ['USER' => Yii::$app->user->identity->username];
        $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);

        $modelNotification->createNotification([
            'referenceId' => (int) $post->id,
            'userIds' => [(int) $post->user_id],
            'notificationData' => $notificationData,
            'isFollowing' => $isFollowing,
        ]);
    }

    private function watchEventsTableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        $schema = Yii::$app->db->schema->getTableSchema('post_watch_events', true);
        $exists = $schema !== null;
        return $exists;
    }
}
