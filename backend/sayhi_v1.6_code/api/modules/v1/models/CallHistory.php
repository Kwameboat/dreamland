<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
//use api\modules\v1\models\Message;

class CallHistory extends \yii\db\ActiveRecord
{
    //const STATUS_DELETED = 0;
    const STATUS_RINGING=1;
    const STATUS_REJECTED=2;
    const STATUS_UN_ANSWERED=3;
    const STATUS_PICKED=4;
    const STATUS_COMPLETED=5;

    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'call_detail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','local_call_id','call_type','caller_id','receiver_id','start_time','end_time','total_time','status'], 'integer']
            //[['message'], 'string']
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
           
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->start_time = time();
        }

        
        return parent::beforeSave($insert);
    }
    
    public function extraFields()
    {
        return ['callerDetail','receiverDetail'];
    }

    
    public function fields()
    {
        $fields = parent::fields();

        
       //$fields[] = 'statusString';
       /*$fields['username'] = (function($model){
            if($model->created_by==Yii::$app->user->identity->id){
                return 'You';
            }else{
                $senderUser = $model->user;
                //return $senderUser->first_name.' '.$senderUser->last_name;
                return $senderUser->username;


            }
            
        });*/
      

        return $fields;
    }



    public function getCallerDetail()
    {
       return $this->hasOne(User::className(), ['id'=>'caller_id']);
        
    }

    public function getReceiverDetail()
    {
       return $this->hasOne(User::className(), ['id'=>'receiver_id']);
        
    }

    
    

    

}
