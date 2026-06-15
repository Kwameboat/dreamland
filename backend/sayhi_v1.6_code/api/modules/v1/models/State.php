<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


class State extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'state';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['country_id','status', 'id'], 'integer'],
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
            'name' => Yii::t('app','Name'),
            'status' => Yii::t('app','Status'),
            'country_id' => Yii::t('app','Country'),
        ];
    }

   
    public function getStateList($country_id){
        return  $this->find()->select(['id','name'])->where(['status'=>State::STATUS_ACTIVE,'country_id'=>$country_id])->orderBy(['name'=>SORT_ASC ])->all();
       
        
    }
}
