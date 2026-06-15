<?php

namespace common\components;

use common\models\Notification;
use common\models\Post;
use Yii;
use yii\base\Component;

class DreamlandContentReview extends Component
{
    public static function hasRejectionColumns(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $schema = Yii::$app->db->schema->getTableSchema('post', true);
        $cached = $schema && isset($schema->columns['rejection_reason']);
        return $cached;
    }

    public static function rejectPost($post, string $reason, ?int $adminId = null): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Rejection reason is required.');
        }

        $post->appraisal_status = 'rejected';
        $post->status = Post::STATUS_BLOCKED;

        if (self::hasRejectionColumns()) {
            $post->rejection_reason = mb_substr($reason, 0, 2000);
            $post->rejected_at = time();
            $post->rejected_by = $adminId;
            $post->appeal_status = null;
            $post->appeal_message = null;
            $post->appeal_submitted_at = null;
        }

        $post->save(false);
        self::notifyCreatorRejection($post);
    }

    /**
     * @param Post|\api\modules\v1\models\Post $post
     */
    public static function notifyCreatorRejection($post): void
    {
        $userId = (int) $post->user_id;
        if ($userId < 1) {
            return;
        }

        $template = Yii::$app->params['pushNotificationMessage']['contentRejected'] ?? [
            'title' => 'Reel not approved',
            'body' => 'Your reel "{{TITLE}}" was rejected: {{REASON}}. You can appeal or upload a new version in Studio.',
            'type' => 50,
        ];

        $title = (string) ($template['title'] ?? 'Reel not approved');
        $bodyTemplate = (string) ($template['body'] ?? 'Your reel was rejected.');
        $reason = self::hasRejectionColumns() ? trim((string) $post->rejection_reason) : '';
        if ($reason === '') {
            $reason = 'Did not meet community guidelines.';
        }

        $replace = [
            'TITLE' => strip_tags((string) $post->title),
            'REASON' => mb_substr($reason, 0, 180),
        ];
        $body = $bodyTemplate;
        foreach ($replace as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        $notification = new Notification();
        $notification->createNotification([
            'userIds' => [$userId],
            'referenceId' => (int) $post->id,
            'notificationData' => [
                'title' => $title,
                'body' => $body,
                'type' => (int) ($template['type'] ?? 50),
            ],
            'url' => '/?view=creator-view',
        ]);
    }

    /**
     * @param Post|\api\modules\v1\models\Post $post
     */
    public static function serializeReelReviewFields($post): array
    {
        if (!self::hasRejectionColumns()) {
            return [
                'rejection_reason' => null,
                'rejected_at' => null,
                'appeal_status' => null,
                'appeal_message' => null,
                'appeal_submitted_at' => null,
            ];
        }

        return [
            'rejection_reason' => $post->rejection_reason ? (string) $post->rejection_reason : null,
            'rejected_at' => $post->rejected_at ? (int) $post->rejected_at : null,
            'appeal_status' => $post->appeal_status ? (string) $post->appeal_status : null,
            'appeal_message' => $post->appeal_message ? (string) $post->appeal_message : null,
            'appeal_submitted_at' => $post->appeal_submitted_at ? (int) $post->appeal_submitted_at : null,
        ];
    }
}
