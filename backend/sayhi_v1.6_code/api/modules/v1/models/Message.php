<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\User;

class Message extends \yii\db\ActiveRecord
{
    const IS_READ_NO = 0;
    const IS_READ_YES = 1;


   
    public $ad_id;


    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','sender_id','receiver_id','is_read','created_at','group_id','ad_id'], 'integer'],
           // [['group_id'], 'save'],
            [['message'], 'string'],
            [['message'], 'required','on'=>'create'],
            [['group_id'], 'required','on'=>'messageHistory'],
            


            

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
            'message' => Yii::t('app', 'Message'),
            'is_read'=> Yii::t('app', 'Read'),
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


    public function fields()
    {
        $fields = parent::fields();

        
       //$fields[] = 'statusString';
       $fields['sender_name'] = (function($model){
            if($model->sender_id==Yii::$app->user->identity->id){
                return 'You';
            }else{
                return $model->senderUser->name;
            }
            
        });
        $fields['receiver_name'] = (function($model){
            if($model->receiver_id==Yii::$app->user->identity->id){
                return 'You';
            }else{
                return $model->receiverUser->name;
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

    public function getReceiverUser()
    {
        
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }
    public function getSenderUser()
    {
        
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }
    

    

}
