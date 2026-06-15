<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;



class ChatRoom extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;


    const TYPE_PERSONAL=1;
    const TYPE_GROUP=2;


    const ACCESS_GROUP_ADMIN = 1;
    const ACCESS_GROUP_ALL   = 2;
    
    
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
            [['title','description','image'], 'string']
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => Yii::t('app', 'Joined at')
            
            
            
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
           
        } else {
            $this->updated_at = time();
           
        }

        return parent::beforeSave($insert);
    }

   
 

}
