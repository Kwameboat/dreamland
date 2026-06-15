<?php

namespace common\models;

use yii\db\ActiveRecord;

class DreamlandSetting extends ActiveRecord
{
    public static function tableName()
    {
        return 'dreamland_settings';
    }

    public static function getSettings()
    {
        $settings = static::findOne(1);
        if (!$settings) {
            $settings = new static(['id' => 1]);
            $settings->save(false);
        }
        return $settings;
    }
}
