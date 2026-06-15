<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\User;

/**
 * This is the model class 
 *
 */
class BroadcastNotificationUser extends \yii\db\ActiveRecord
{

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'broadcast_notification_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
                      
            [['id','broadcast_notification_id','user_id'], 'integer']
            
            
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID'
           
            
            
        ];
    }
   /* public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;

        } 

        return parent::beforeSave($insert);
    }*/
    
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }

}
