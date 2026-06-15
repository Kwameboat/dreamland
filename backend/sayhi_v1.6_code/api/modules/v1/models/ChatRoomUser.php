<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
//use api\modules\v1\models\Message;

class ChatRoomUser extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;
    const STATUS_REMOVED = 2;
    const STATUS_LEFT = 3;

    const TYPE_PERSONAL=1;
    const TYPE_GROUP=2;

    const ACCESS_GROUP_ADMIN = 1;
    const ACCESS_GROUP_ALL   =  2;
    


    public $receiver_id;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_room_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','room_id','user_id','status','created_at','updated_at'], 'integer']
            
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
        return ['user'];
    }


    public function getIsUserInRoom($roomUsers,$userId)
    {
        foreach($roomUsers as $roomUser ) {
           
             if($roomUser->user_id==$userId){
                 //return true;
                 return $roomUser;
             }
         }
         return false;
        
        
    }

    public function getMyUserInRoom($roomUsers,$roomId,$userId)
    {
        foreach($roomUsers as $roomUser ) {
             if($roomUser->user_id==$userId && $roomUser->room_id==$roomId){
                 //return true;
                 return $roomUser;
             }
         }
         return false;
        
        
    }

    

    public function getUser()
    {
       return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    
    

    

}
