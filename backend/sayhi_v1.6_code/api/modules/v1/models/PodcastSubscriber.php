<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\Podcast;


class PodcastSubscriber extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'podcast_subscriber';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','podcast_id','user_id','paid_coin','status','created_at'], 'integer']
            
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
       // return ['user'];
    }


   
    

    public function getUser()
    {
       return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }
    public function getLiveTv()
    {
       return $this->hasOne(Podcast::className(), ['id'=>'podcast_id']);
        
    }

    
    

    

}
