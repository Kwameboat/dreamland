<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;
use api\modules\v1\models\User;

class CommentLike extends \yii\db\ActiveRecord
{
    const SOURCE_TYPE_POST          =1;
    const SOURCE_TYPE_CAMPAIGN      =2;
    const SOURCE_TYPE_COUPON        =3;
    

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comment_like';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','comment_id','source_type','created_at'], 'integer'],
            [['comment_id','source_type'], 'required', 'on'=>'create']
            

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
            'comment_id' => Yii::t('app', 'Comment'),
            'created_at'=> Yii::t('app', 'created At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id =   Yii::$app->user->identity->id;
          
        }
    
        return parent::beforeSave($insert);
    }

     
    /*public function extraFields()
    {
        return ['user'];
    }*/
    
    /**
     * RELEATION START
     */
    /*public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }*/


    


    
    

}
