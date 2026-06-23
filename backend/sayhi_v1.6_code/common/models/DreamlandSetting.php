<?php

namespace common\models;

use yii\db\ActiveRecord;

class DreamlandSetting extends ActiveRecord
{
    /** Admin form helper — saved as max_reel_duration_seconds (seconds). */
    public $max_reel_duration_minutes;

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
            [['max_reel_duration_minutes'], 'integer', 'min' => 1, 'max' => 10],
            [['paystack_public_key', 'paystack_secret_key', 'vapid_public_key'], 'string'],
            [['vapid_private_key'], 'string'],
        ];
    }

    public function afterFind()
    {
        parent::afterFind();
        $secs = (int) ($this->max_reel_duration_seconds ?? 60);
        $this->max_reel_duration_minutes = max(1, (int) round($secs / 60));
    }

    public function beforeSave($insert)
    {
        if ($this->max_reel_duration_minutes !== null && $this->max_reel_duration_minutes !== '') {
            $mins = max(1, min(10, (int) $this->max_reel_duration_minutes));
            $this->max_reel_duration_seconds = $mins * 60;
        }
        // Live: 0 = no cap — creator ends broadcast manually in the PWA.
        $this->max_live_duration_seconds = 0;
        return parent::beforeSave($insert);
    }

    public function attributeLabels()
    {
        return [
            'max_reel_duration_minutes' => 'Max reel length (minutes)',
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
