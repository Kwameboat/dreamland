<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class DreamlandSetting extends ActiveRecord
{
    /** Admin form helper — saved as max_reel_duration_seconds (seconds). */
    public $max_reel_duration_minutes;

    /** @var array<string,mixed> */
    private $_fallback = [];

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
            [['paystack_public_key', 'paystack_secret_key', 'vapid_public_key', 'vapid_private_key'], 'string'],
        ];
    }

    public function attributes()
    {
        $attrs = parent::attributes();
        if ($attrs === []) {
            $attrs = array_keys(self::defaultValues());
        }
        $attrs[] = 'max_reel_duration_minutes';
        return array_values(array_unique($attrs));
    }

    public function hasAttribute($name)
    {
        if ($name === 'max_reel_duration_minutes' || array_key_exists($name, self::defaultValues())) {
            return true;
        }
        return parent::hasAttribute($name);
    }

    public function __get($name)
    {
        if ($name === 'max_reel_duration_minutes') {
            return $this->max_reel_duration_minutes;
        }
        if (parent::hasAttribute($name)) {
            return parent::__get($name);
        }
        if (array_key_exists($name, self::defaultValues())) {
            return $this->_fallback[$name] ?? self::defaultValues()[$name];
        }
        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if ($name === 'max_reel_duration_minutes') {
            $this->max_reel_duration_minutes = $value;
            return;
        }
        if (parent::hasAttribute($name)) {
            parent::__set($name, $value);
            return;
        }
        if (array_key_exists($name, self::defaultValues())) {
            $this->_fallback[$name] = $value;
            return;
        }
        parent::__set($name, $value);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->initDurationMinutes();
    }

    public function initDurationMinutes(): void
    {
        $secs = 60;
        try {
            if (parent::hasAttribute('max_reel_duration_seconds')) {
                $secs = (int) parent::__get('max_reel_duration_seconds');
            }
        } catch (\Throwable $e) {
            $secs = (int) ($this->_fallback['max_reel_duration_seconds'] ?? 60);
        }
        if ($secs <= 0) {
            $secs = 60;
        }
        $this->max_reel_duration_minutes = max(1, (int) round($secs / 60));
    }

    public function beforeSave($insert)
    {
        static::ensureColumns();

        if ($this->max_reel_duration_minutes !== null && $this->max_reel_duration_minutes !== '') {
            $mins = max(1, min(10, (int) $this->max_reel_duration_minutes));
            if (parent::hasAttribute('max_reel_duration_seconds')) {
                $this->setAttribute('max_reel_duration_seconds', $mins * 60);
            } else {
                $this->_fallback['max_reel_duration_seconds'] = $mins * 60;
            }
        }
        if (parent::hasAttribute('max_live_duration_seconds')) {
            $this->setAttribute('max_live_duration_seconds', 0);
        }

        return parent::beforeSave($insert);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        static::ensureColumns();

        if (!static::tableExists()) {
            Yii::warning('dreamland_settings table missing — cannot save settings.', __METHOD__);
            return false;
        }

        if (!$this->id) {
            $this->id = 1;
        }

        if (!static::findOne(1)) {
            try {
                $insert = new static(['id' => 1]);
                $insert->save(false);
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
            }
        }

        return parent::save($runValidation, $attributeNames);
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

    /** @return array<string,mixed> */
    public static function defaultValues(): array
    {
        return [
            'id' => 1,
            'platform_commission_percent' => 20,
            'preview_seconds' => 3,
            'streak_freeze_cost' => 5,
            'streak_watch_threshold_seconds' => 300,
            'streak_game_score_threshold' => 100,
            'paystack_public_key' => '',
            'paystack_secret_key' => '',
            'vapid_public_key' => '',
            'vapid_private_key' => '',
            'max_reel_duration_seconds' => 60,
            'max_reel_upload_mb' => 128,
            'max_live_duration_seconds' => 0,
        ];
    }

    public static function tableExists(): bool
    {
        try {
            return static::getDb()->schema->getTableSchema(static::tableName(), true) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Add missing columns on older cPanel DBs (idempotent). */
    public static function ensureColumns(): bool
    {
        try {
            $db = static::getDb();
            if ($db->driverName !== 'mysql') {
                return true;
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
                $schema = $db->schema->getTableSchema($table, true);
                if ($schema && !isset($schema->columns[$column])) {
                    $db->createCommand("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}")->execute();
                    $db->schema->refreshTableSchema($table);
                }
            }

            $db->schema->refreshTableSchema($table);
            return static::tableExists();
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return false;
        }
    }

    public static function getSettings(): self
    {
        static::ensureColumns();

        try {
            $settings = static::findOne(1);
            if ($settings) {
                $settings->initDurationMinutes();
                return $settings;
            }
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }

        $settings = new static();
        $settings->id = 1;
        foreach (self::defaultValues() as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            $settings->$key = $value;
        }
        $settings->initDurationMinutes();

        try {
            if (static::tableExists()) {
                $settings->save(false);
            }
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
        }

        return $settings;
    }
}
