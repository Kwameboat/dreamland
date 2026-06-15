<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;


/**
 * This is the model class 
 *
 */
class FeatureEnabled extends \yii\db\ActiveRecord
{
    
  
    
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



    

}
