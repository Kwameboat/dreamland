<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * Admin-controlled credit packages for Dreamland wallet top-ups.
 *
 * @property string $id
 * @property int $credit_amount
 * @property float $fiat_cost
 * @property string $currency
 * @property bool $is_active
 * @property string $created_at
 */
class CreditPackage extends ActiveRecord
{
    public static function tableName()
    {
        return 'credit_packages';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules()
    {
        return [
            [['credit_amount', 'fiat_cost'], 'required'],
            [['credit_amount'], 'integer', 'min' => 1],
            [['fiat_cost'], 'number', 'min' => 0.01],
            [['currency'], 'string', 'max' => 3],
            [['currency'], 'default', 'value' => 'GHS'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => true],
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert && empty($this->id)) {
            $this->id = $this->generateUuid();
        }
        return parent::beforeSave($insert);
    }

    public static function getActivePackages()
    {
        return static::find()
            ->where(['is_active' => 1])
            ->orderBy(['fiat_cost' => SORT_ASC])
            ->all();
    }

    private function generateUuid()
    {
        return sprintf(
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
}
