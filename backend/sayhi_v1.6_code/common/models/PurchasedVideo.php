<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $credits_paid
 * @property int $creator_credits
 * @property int $platform_commission
 * @property string $purchased_at
 */
class PurchasedVideo extends ActiveRecord
{
    public static function tableName()
    {
        return 'purchased_videos';
    }

    public function rules()
    {
        return [
            [['user_id', 'video_id', 'credits_paid', 'creator_credits'], 'required'],
            [['user_id', 'video_id', 'credits_paid', 'creator_credits', 'platform_commission'], 'integer'],
        ];
    }

    public static function hasPurchase($userId, $videoId)
    {
        return static::find()
            ->where(['user_id' => (int) $userId, 'video_id' => (int) $videoId])
            ->exists();
    }
}
