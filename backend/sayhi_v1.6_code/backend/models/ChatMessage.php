<?php
namespace backend\models;
use Yii;
use yii\helpers\ArrayHelper;

class ChatMessage extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','local_message_id','room_id','type','status','current_status','is_user_notify','created_at','created_at','chat_version'], 'integer'],
            [['message'], 'string']
            
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
            $this->created_at = time();
        }

        
        return parent::beforeSave($insert);
    }
    
    public function extraFields()
    {
        return ['user','chatMessageUser'];
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
}
