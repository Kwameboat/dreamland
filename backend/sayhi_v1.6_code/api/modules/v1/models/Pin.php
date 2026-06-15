<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;


class Pin extends \yii\db\ActiveRecord
{
   
    const TYPE_POST     =1;
    const TYPE_COMMENT  =2;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pin';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','reference_id','type','user_id','created_at'], 'integer'],
            [['reference_id','type'], 'required','on'=>['create','removePin']],
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
            $this->user_id =  Yii::$app->user->identity->id;
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
