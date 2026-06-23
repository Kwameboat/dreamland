<?php

namespace common\models;

use yii\db\ActiveRecord;

class VideoPrediction extends ActiveRecord
{
    const STATUS_OPEN = 'open';
    const STATUS_RESOLVED = 'resolved';

    public static function tableName()
    {
        return 'video_predictions';
    }

    public function rules()
    {
        return [
            [['video_id', 'target_metric', 'target_value', 'timer_expires_at'], 'required'],
            [['video_id', 'target_value'], 'integer'],
            [['target_metric', 'status', 'outcome'], 'string', 'max' => 64],
            [['status'], 'in', 'range' => [self::STATUS_OPEN, self::STATUS_RESOLVED]],
            [['outcome'], 'default', 'value' => 'pending'],
            [['status'], 'default', 'value' => self::STATUS_OPEN],
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert && ($this->status === null || $this->status === '')) {
            $this->status = self::STATUS_OPEN;
        }
        if ($insert && ($this->outcome === null || $this->outcome === '')) {
            $this->outcome = 'pending';
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
