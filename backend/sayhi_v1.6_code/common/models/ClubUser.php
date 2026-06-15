<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\Post;


class ClubUser extends \yii\db\ActiveRecord
{
    const STATUS_DELETED=0;
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'club_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','club_id','is_admin','created_at','updated_at'], 'integer'],
           // [['image',], 'string', 'max' => 100]

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

   
   /*
    public function getImageUrl(){
        
        return Yii::$app->params['pathUploadCompetition'] ."/".$this->image;
    }
    */

    
  
    /**
     * RELEATION START
     */
    public function getUserDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }
 

}
