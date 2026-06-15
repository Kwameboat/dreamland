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

    public function beforeSave($insert)
    {
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
