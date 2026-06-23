<?php

namespace common\models;

use Yii;
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

    public function hasAttribute($name)
    {
        if ($name === 'max_reel_duration_minutes') {
            return true;
        }
        return parent::hasAttribute($name);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->initDurationMinutes();
    }

    public function initDurationMinutes(): void
    {
        $secs = $this->hasAttribute('max_reel_duration_seconds')
            ? (int) $this->getAttribute('max_reel_duration_seconds')
            : 60;
        if ($secs <= 0) {
            $secs = 60;
        }
        $this->max_reel_duration_minutes = max(1, (int) round($secs / 60));
    }

    public function beforeSave($insert)
    {
        if ($this->max_reel_duration_minutes !== null && $this->max_reel_duration_minutes !== '') {
            $mins = max(1, min(10, (int) $this->max_reel_duration_minutes));
            if ($this->hasAttribute('max_reel_duration_seconds')) {
                $this->setAttribute('max_reel_duration_seconds', $mins * 60);
            }
        }
        if ($this->hasAttribute('max_live_duration_seconds')) {
            $this->setAttribute('max_live_duration_seconds', 0);
        }
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

    /** Add missing columns on older cPanel DBs (idempotent). */
    public static function ensureColumns(): void
    {
        try {
            $db = static::getDb();
            if ($db->driverName !== 'mysql') {
                return;
            }

            $table = static::tableName();
            $schema = $db->schema->getTableSchema($table, true);
            if (!$schema) {
                $db->createCommand("CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` SMALLINT NOT NULL DEFAULT 1 PRIMARY KEY,
                    `platform_commission_percent` SMALLINT NOT NULL DEFAULT 20,
                    `streak_freeze_cost` INT NOT NULL DEFAULT 5,
                    `streak_watch_threshold_seconds` INT NOT NULL DEFAULT 300,
                    `streak_game_score_threshold` INT NOT NULL DEFAULT 100
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")->execute();
                $db->createCommand("INSERT IGNORE INTO `{$table}` (`id`) VALUES (1)")->execute();
                $db->schema->refreshTableSchema($table);
                $schema = $db->schema->getTableSchema($table, true);
            }

            $columns = [
                'preview_seconds' => 'TINYINT NOT NULL DEFAULT 3',
                'paystack_public_key' => 'VARCHAR(128) NULL DEFAULT NULL',
                'paystack_secret_key' => 'VARCHAR(128) NULL DEFAULT NULL',
                'vapid_public_key' => 'VARCHAR(255) NULL DEFAULT NULL',
                'vapid_private_key' => 'TEXT NULL DEFAULT NULL',
                'max_reel_duration_seconds' => 'INT NOT NULL DEFAULT 60',
                'max_reel_upload_mb' => 'INT NOT NULL DEFAULT 128',
                'max_live_duration_seconds' => 'INT NOT NULL DEFAULT 0',
            ];

            foreach ($columns as $column => $definition) {
                if ($schema && !isset($schema->columns[$column])) {
                    $db->createCommand("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}")->execute();
                }
            }

            $db->schema->refreshTableSchema($table);
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }
    }

    public static function getSettings()
    {
        static::ensureColumns();

        try {
            $settings = static::findOne(1);
            if (!$settings) {
                $settings = new static(['id' => 1]);
                $settings->save(false);
            }
            $settings->initDurationMinutes();
            return $settings;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            $settings = new static(['id' => 1]);
            $settings->initDurationMinutes();
            return $settings;
        }
    }
}
