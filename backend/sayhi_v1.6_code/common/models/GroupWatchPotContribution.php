<?php

namespace common\models;

use yii\db\ActiveRecord;

class GroupWatchPotContribution extends ActiveRecord
{
    public static function tableName()
    {
        return 'group_watch_pot_contributions';
    }
}
