<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
//use api\modules\v1\models\Message;

class ChatMessageUser extends \yii\db\ActiveRecord
{
    const STATUS_UNRECEIVED =1;
    const STATUS_RECEIVED   =2;
    const STATUS_UNREAD     =3;
    const STATUS_READ       =4;
    const STATUS_DELETED    =0;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_message_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','chat_message_id','user_id','status'], 'integer']
            
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
   
    
    
    public function extraFields()
    {
        return ['user'];
    }

    

    public function getUser()
    {
       return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    
    

    

}
