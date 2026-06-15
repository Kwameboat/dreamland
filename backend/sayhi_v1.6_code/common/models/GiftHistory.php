<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\GiftCategory;
use common\models\UserLiveBattle;

/**
 * This is the model class 
 *
 */
class GiftHistory extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;
    const TYPE_IS_PAID = 1;
    
    const SEND_TO_TYPE_LIVE=1;
    const SEND_TO_TYPE_PROFILE=2;
    const SEND_TO_TYPE_POST=3;
    const SHOW_TOP_GIFT_RECIEVER_LIMIT =15;
    const POST_TYPE_TIMELINE_GIFT =2; 

    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gift_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'required'],
            [['status', 'id','reciever_id','sender_id','gift_id','live_call_id','battle_id','post_id','created_at','post_type'], 'integer'],
            [['name'], 'string'],
            [['coin','coin_actual'], 'safe'],
            [['name'], 'required','on'=>['create','update']],


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
        return $this->hasOne(User::className(), ['id'=>'reciever_id']);
        
    }

    public function getSenderUser()
    {
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }

        
    public function getUserBattle()
    {
        return $this->hasOne(UserLiveBattle::className(), ['id'=>'battle_id'])->andOnCondition(['status'=>UserLiveBattle::STATUS_COMPLETED]);
        
    }

    public function getGiftDetail()
    {
        return $this->hasOne(Gift::className(), ['id'=>'gift_id'])->andOnCondition(['status'=>Gift::STATUS_ACTIVE]);
        
    }
    
    public function getGiftImageUrl()
    {
       if($this->gift_id){
       $result = Gift::find()->select(['id','image'])->where(['id'=>$this->gift_id])->one();
        if($result){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_GIFT,$result->image);
        }
       }
 
    }
}
