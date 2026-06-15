<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\User;


class Follower extends \yii\db\ActiveRecord
{
    const FOLLOW_REQUEST =1;
    const FOLLOW_REQUEST_ACCEPT =2;
    const FOLLOW_PUBLIC =0;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'follower';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','follower_id','created_at'], 'integer'],
            [['user_id'], 'required', 'on'=>'create']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'follower_id' => Yii::t('app', 'Follower'),
            'created_at'=> Yii::t('app', 'Reported At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->follower_id =   Yii::$app->user->identity->id;
          
        }

        
        return parent::beforeSave($insert);
    }

    

   
    

    public function getFollowingUserDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
    }


   public function getFollowerUserDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'follower_id']);
    }

    public function getUserLocation()
    {
        return $this->hasOne(UserLocation::className(), ['user_id'=>'id']);
    }

    public function getUser()
{
    return $this->hasOne(User::class, ['id' => 'user_id']);
}



}
