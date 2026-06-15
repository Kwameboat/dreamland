<?php

namespace common\models;

use yii\db\ActiveRecord;

class VideoPredictionStake extends ActiveRecord
{
    public static function tableName()
    {
        return 'video_prediction_stakes';
    }
}
