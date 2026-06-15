<?php

namespace common\components;

use common\models\DreamlandSetting;
use common\models\Notification;
use common\models\User;
use common\models\WebPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Yii;
use yii\base\Component;

class DreamlandPushService extends Component
{
    /** Broadcast / admin push type for in-app feed. */
    public const TYPE_BROADCAST = 50;

    /**
     * Notify users via in-app feed, mobile FCM, and PWA web push (home-screen installs).
     */
    public function notifyUsers(array $userIds, string $title, string $body, array $options = []): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return;
        }

        $type = (int) ($options['type'] ?? self::TYPE_BROADCAST);
        $url = (string) ($options['url'] ?? '/');
        $referenceId = (int) ($options['referenceId'] ?? 0);
        $saveInApp = (bool) ($options['saveInApp'] ?? true);
        $audienceGroup = (string) ($options['audienceGroup'] ?? '');

        $users = User::find()
            ->select(['id', 'device_token', 'is_push_notification_allow'])
            ->where(['id' => $userIds])
            ->all();

        foreach ($users as $user) {
            if ($saveInApp) {
                $notification = new Notification();
                $notification->user_id = (int) $user->id;
                $notification->type = $type;
                $notification->title = $title;
                $notification->message = $body;
                $notification->reference_id = $referenceId;
                $notification->read_status = 0;
                if ($schema = \Yii::$app->db->schema->getTableSchema('notification', true)) {
                    if (isset($schema->columns['audience_group']) && !empty($options['audienceGroup'])) {
                        $notification->audience_group = (string) $options['audienceGroup'];
                    }
                }
                $notification->save(false);
            }

            if ($user->device_token && (int) $user->is_push_notification_allow === 1) {
                $dataPush = [
                    'title' => $title,
                    'body' => $body,
                    'data' => [
                        'notification_type' => $type,
                        'reference_id' => $referenceId,
                        'receiver_id' => (int) $user->id,
                        'url' => $url,
                    ],
                ];
                Yii::$app->pushNotification->sendPushNotification([$user->device_token], $dataPush);
            }
        }

        $this->sendWebPush($userIds, $title, $body, $url);
    }

    public function sendWebPush(array $userIds, string $title, string $body, string $url = '/'): void
    {
        if (!class_exists(WebPush::class)) {
            return;
        }

        $settings = DreamlandSetting::getSettings();
        $publicKey = (string) ($settings->vapid_public_key ?? '');
        $privateKey = (string) ($settings->vapid_private_key ?? '');
        if ($publicKey === '' || $privateKey === '') {
            return;
        }

        $subscriptions = WebPushSubscription::find()
            ->where(['user_id' => $userIds, 'is_active' => 1])
            ->all();

        if (!$subscriptions) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:admin@dreamland.app',
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        foreach ($subscriptions as $row) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $row->endpoint,
                    'keys' => [
                        'p256dh' => $row->p256dh,
                        'auth' => $row->auth,
                    ],
                ]);
                $report = $webPush->sendOneNotification($subscription, $payload);
                if ($report->isSubscriptionExpired()) {
                    $row->is_active = 0;
                    $row->updated_at = time();
                    $row->save(false);
                }
            } catch (\Throwable $e) {
                Yii::warning('Web push failed: ' . $e->getMessage(), __METHOD__);
            }
        }
    }
}
