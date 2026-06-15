<?php

namespace common\models;

use yii\db\ActiveRecord;

class WebPushSubscription extends ActiveRecord
{
    public static function tableName()
    {
        return 'web_push_subscription';
    }

    public static function upsertForUser(int $userId, array $subscription, ?string $userAgent = null): void
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $p256dh = (string) ($subscription['keys']['p256dh'] ?? '');
        $auth = (string) ($subscription['keys']['auth'] ?? '');
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            throw new \InvalidArgumentException('Invalid push subscription payload.');
        }

        $now = time();
        $model = static::find()->where(['endpoint' => $endpoint])->one();
        if (!$model) {
            $model = new static();
            $model->created_at = $now;
        }

        $model->user_id = $userId;
        $model->endpoint = $endpoint;
        $model->p256dh = $p256dh;
        $model->auth = $auth;
        $model->user_agent = $userAgent;
        $model->is_active = 1;
        $model->updated_at = $now;
        $model->save(false);
    }

    public static function deactivateForUser(int $userId): void
    {
        static::updateAll(['is_active' => 0, 'updated_at' => time()], ['user_id' => $userId]);
    }
}
