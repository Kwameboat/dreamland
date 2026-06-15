<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class PromotionalAdCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'promotional_ad_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'category_id', 'promotional_ad_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => Yii::t('app', 'Category'),
             'promotional_ad_id' => Yii::t('app', 'Promotional ad'),
        ];
    }

}
