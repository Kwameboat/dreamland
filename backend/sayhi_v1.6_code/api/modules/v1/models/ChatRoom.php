<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\User;
use api\modules\v1\models\ChatRoomUser;
use api\modules\v1\models\ChatMessage;
//use api\modules\v1\models\Ad;
//use api\modules\v1\models\Message;


class ChatRoom extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;


    const TYPE_PERSONAL=1;
    const TYPE_GROUP=2;
    const TYPE_OPEN_GROUP=3;


    const ACCESS_GROUP_ADMIN = 1;
    const ACCESS_GROUP_ALL   = 2;

    public $receiver_id;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_room';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','order_id','club_id','type','status','chat_access_group','created_at','created_by','updated_at','updated_by','receiver_id'], 'integer'],
            [['title','description','image'], 'string'],
            [[ 'type' ], 'required','on'=>'createRoom'],
            [[ 'id' ], 'required','on'=>'deleteRoomChat']
           
    
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
            $this->created_by =   Yii::$app->user->identity->id;
        }

        
        return parent::beforeSave($insert);
    }

    public function extraFields()
    {
        return ['roomUserCount','lastMessage','chatRoomUser','createdByUser'];
    }

    
    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'imageUrl';
       // $fields[] = 'competitionImage';
        return $fields;
    }

    
    /*
   

    

    public function getIsReadString()
    {
       if($this->is_read==$this::IS_READ_YES){
           return 'Yes';
       } else{
            return 'No';    
        }
    }
    */
    
    
    /*public function getLastMessage()
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
        
    }*/

    public function getChatRoomUser()
    {
       return $this->hasMany(ChatRoomUser::className(), ['room_id'=>'id'])->andOnCondition(['chat_room_user.status'=>ChatRoomUser::STATUS_ACTIVE])->orderBy(["chat_room_user.is_admin" => SORT_DESC]);
        
    }
    public function getRoomUserCount()
    {
       return (int)$this->hasMany(ChatRoomUser::className(), ['room_id'=>'id'])->andOnCondition(['chat_room_user.status'=>ChatRoomUser::STATUS_ACTIVE])->count();
        
    }

    public function getChatMessage()
    {
       return $this->hasMany(ChatMessage::className(), ['room_id'=>'id']);
        
    }

    public function getCreatedByUser()
    {
       return  $this->hasOne(User::className(), ['id'=>'created_by']);
       
        
    }
    


    public function getLastMessage()
    {
       return $this->hasOne(ChatMessage::className(), ['room_id'=>'id'])->orderBy(['chat_message.id'=>SORT_DESC]);
        
    }
    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CHAT,$this->image);

            //return Yii::$app->params['pathUploadChat'] . "/" . $this->image;
        }else{
            return '';
        }
        
    }

    

    

    
    

    

}
