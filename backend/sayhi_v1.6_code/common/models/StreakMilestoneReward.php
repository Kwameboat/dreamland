<?php

namespace common\models;

use yii\db\ActiveRecord;

class StreakMilestoneReward extends ActiveRecord
{
    public static function tableName()
    {
        return 'streak_milestone_rewards';
    }
}
