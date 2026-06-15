<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class LocalBlacklistKeyword extends ActiveRecord
{
    public static function tableName()
    {
        return 'local_blacklist_keywords';
    }

    public function rules()
    {
        return [
            [['keyword'], 'required'],
            [['keyword'], 'string', 'max' => 128],
            [['locale'], 'string', 'max' => 16],
            [['severity', 'is_active'], 'integer'],
            [['keyword'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'keyword' => 'Keyword / phrase',
            'locale' => 'Language locale',
            'severity' => 'Severity (1–3)',
            'is_active' => 'Active',
        ];
    }

    public static function getActiveKeywords()
    {
        $fromDb = static::find()
            ->select('keyword')
            ->where(['is_active' => 1])
            ->column();
        $fromFile = Yii::$app->params['localBlacklistKeywords']
            ?? (is_file(__DIR__ . '/../config/local_blacklist_keywords.php')
                ? require __DIR__ . '/../config/local_blacklist_keywords.php'
                : []);
        return array_values(array_unique(array_merge($fromDb, $fromFile)));
    }
}
