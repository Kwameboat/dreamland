<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;



class StreamerAwardHistory extends \yii\db\ActiveRecord
{
       
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'streamer_award_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','created_at'], 'integer'],
            [['coin'], 'number']
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'created_at'=> Yii::t('app', 'Created At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
        }
        return parent::beforeSave($insert);
    }

    

}
