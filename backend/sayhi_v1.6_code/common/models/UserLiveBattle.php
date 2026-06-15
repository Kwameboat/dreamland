<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\GiftCategory;


/**
 * This is the model class 
 *
 */
class UserLiveBattle extends \yii\db\ActiveRecord
{
    const STATUS_ONGOING=4;
    const STATUS_COMPLETED=10;


    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_live_battle';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'required'],
            [['status', 'id','user_live_history_id','super_host_user_id','host_user_id','start_time','end_time','total_time','total_allowed_time','created_at'], 'integer']
           


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'is_paid' => Yii::t('app', 'Is Paid ?'),
            'coin' => Yii::t('app', 'Coin'),
            
            
        ];
    }
   
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    public function getSenderUser()
    {
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }

    public function getGiftDetails()
    {
        return $this->hasMany(GiftHistory::className(), ['live_call_id'=>'id']);
        
    }
    
    public function getUserBattle()
    {
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }
    

}
