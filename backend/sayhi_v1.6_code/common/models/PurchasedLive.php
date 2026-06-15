<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int $live_id
 * @property int $credits_paid
 * @property int $creator_credits
 * @property int $platform_commission
 * @property string $purchased_at
 */
class PurchasedLive extends ActiveRecord
{
    public static function tableName()
    {
        return 'purchased_lives';
    }

    public function rules()
    {
        return [
            [['user_id', 'live_id', 'credits_paid', 'creator_credits'], 'required'],
            [['user_id', 'live_id', 'credits_paid', 'creator_credits', 'platform_commission'], 'integer'],
        ];
    }

    public static function hasPurchase($userId, $liveId)
    {
        return static::find()
            ->where(['user_id' => (int) $userId, 'live_id' => (int) $liveId])
            ->exists();
    }
}
