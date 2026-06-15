<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


class FeatureEnabled extends \yii\db\ActiveRecord
{

    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feature_enabled';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type','id','user_id','feature_id'], 'integer']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            
        ];
    }
    public function fields()
    {
        $fields = parent::fields();
     
        return $fields;
    }
    

}
