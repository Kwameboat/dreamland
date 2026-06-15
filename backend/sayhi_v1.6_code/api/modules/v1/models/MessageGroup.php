<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\User;
use api\modules\v1\models\Ad;
use api\modules\v1\models\Message;

class MessageGroup extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_group';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','ad_id','sender_id','receiver_id','status','created_at'], 'integer'],
    
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sender_id' => Yii::t('app', 'Sender'),
            'receiver_id' => Yii::t('app', 'Receiver'),
            'status'=> Yii::t('app', 'Status'),
            'created_at'=> Yii::t('app', 'Created At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->sender_id =   Yii::$app->user->identity->id;
        }

        
        return parent::beforeSave($insert);
    }

    public function extraFields()
    {
        return ['ad','senderUser','receiverUser','lastMessage'];
    }

    /*
    public function fields()
    {
        $fields = parent::fields();

        
       //$fields[] = 'statusString';
       $fields['sender_name'] = (function($model){
            if($model->sender_id==Yii::$app->user->identity->id){
                return 'You';
            }else{
                return $model->senderUser;
            }
            
        });
        $fields['receiver_name'] = (function($model){
            if($model->receirver_id==Yii::$app->user->identity->id){
                return 'You';
            }else{
                return $model->receiverUser;
            }
            
        });


        return $fields;
    }

    

    public function getIsReadString()
    {
       if($this->is_read==$this::IS_READ_YES){
           return 'Yes';
       } else{
            return 'No';    
        }
    }
    */
    public function getReceiverUser()
    {
        
        return $this->hasOne(User::className(), ['id'=>'receiver_id']);
        
    }
    public function getSenderUser()
    {
        
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }

    public function getAd()
    {
       return $this->hasOne(Ad::className(), ['id'=>'ad_id']);
        
    }

    public function getLastMessage()
    {
       return $this->hasOne(Message::className(), ['group_id'=>'id'])->orderBy(['message.id'=>SORT_DESC]);
        
    }


    public function getActiveGroup($userId)
    {
        
        return $this->find()
        
        ->with(['ad'=> function ($query) {
            $query->select(['ad.id','ad.title','ad.currency','ad.price','ad.created_at']);
        }])
        ->with(['senderUser'=> function ($query) {
            $query->select(['id','name','image']);
        }])
        ->with(['receiverUser'=> function ($query) {
            $query->select(['id','name','image']);
        }])
        
        ->where(['status'=>self::STATUS_ACTIVE])
        ->andWhere(['or', ['sender_id'=>$userId], ['receiver_id'=>$userId]])->all();
        
    }


    
    

    

}
