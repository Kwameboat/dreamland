<?php

namespace common\models;

use yii\db\ActiveRecord;

class DreamlandSetting extends ActiveRecord
{
    public static function tableName()
    {
        return 'dreamland_settings';
    }

    public function rules()
    {
        return [
            [[
                'platform_commission_percent',
                'preview_seconds',
                'streak_freeze_cost',
                'streak_watch_threshold_seconds',
                'streak_game_score_threshold',
                'max_reel_duration_seconds',
                'max_reel_upload_mb',
                'max_live_duration_seconds',
            ], 'integer'],
            [['paystack_public_key', 'paystack_secret_key', 'vapid_public_key'], 'string'],
            [['vapid_private_key'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'max_reel_duration_seconds' => 'Max reel duration (seconds)',
            'max_reel_upload_mb' => 'Max reel upload size (MB)',
            'max_live_duration_seconds' => 'Max live duration (seconds)',
        ];
    }

    public static function getSettings()
    {
        try {
            $settings = static::findOne(1);
            if (!$settings) {
                $settings = new static(['id' => 1]);
                $settings->save(false);
            }
            return $settings;
        } catch (\Throwable $e) {
            \Yii::warning($e->getMessage(), __METHOD__);
            $settings = new static(['id' => 1]);
            return $settings;
        }
    }
}
