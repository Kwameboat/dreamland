<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\Podcast;

use api\modules\v1\models\Coupon;


class UserFavorite extends \yii\db\ActiveRecord
{
   
    const TYPE_COUPON     =1;
    const TYPE_BUSINESS  =2;
    const TYPE_POST  =3;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_favorite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','reference_id','type','user_id','created_at'], 'integer'],
            [['reference_id','type'], 'required','on'=>['create','removeFavorite']],
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
   
    
     
    

    

}
