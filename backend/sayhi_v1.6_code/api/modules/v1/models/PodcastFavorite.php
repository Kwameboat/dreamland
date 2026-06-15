<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\Podcast;


class PodcastFavorite extends \yii\db\ActiveRecord
{
   
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'podcast_favorite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','podcast_id','user_id','created_at'], 'integer']
            
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
