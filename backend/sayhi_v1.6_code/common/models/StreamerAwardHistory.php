<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\User;


/**
 * This is the model class 
 *
 */
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
            [['id','user_id','created_at','position_number'], 'integer'],
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
            'position_number' => Yii::t('app', 'Position'),
            'status' => Yii::t('app', 'Status'),
            'award_coin' => Yii::t('app', 'Award Coin'),
            
        ];
    }
    
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }



}
