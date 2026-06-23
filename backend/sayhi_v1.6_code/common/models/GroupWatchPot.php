<?php

namespace common\models;

use yii\db\ActiveRecord;

class GroupWatchPot extends ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';

    public static function tableName()
    {
        return 'group_watch_pots';
    }

    public function rules()
    {
        return [
            [['video_id', 'expires_at'], 'required'],
            [['video_id', 'target_unlocks', 'current_unlocks', 'bonus_pool_credits'], 'integer'],
            [['target_unlocks'], 'default', 'value' => 100],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_COMPLETED, self::STATUS_EXPIRED]],
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert && ($this->status === null || $this->status === '')) {
            $this->status = self::STATUS_OPEN;
        }
        if ($insert && $this->current_unlocks === null) {
            $this->current_unlocks = 0;
        }
        if ($insert && empty($this->id)) {
            $this->id = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
        }
        return parent::beforeSave($insert);
    }
}
