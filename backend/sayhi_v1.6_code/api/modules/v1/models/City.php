<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

class City extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'city';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['state_id', 'id'], 'integer'],
            [['name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'name' => Yii::t('app','Name')
            
        ];
    }

    public function getCityList($state_id){
        return  $this->find()->select(['id','name'])->where(['state_id'=>$state_id])->orderBy(['name'=>SORT_ASC ])->all();
       
        
    }

}
