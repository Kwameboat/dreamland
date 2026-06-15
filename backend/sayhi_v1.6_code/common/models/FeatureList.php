<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\LiveTvCategory;


/**
 * This is the model class 
 *
 */
class FeatureList extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;

  
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feature_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
          
            [['type','section','priority','status'], 'integer'],
            [['name','feature_key'], 'string'],
           ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name')
            
        ];
    }



    

}
