<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;

class ProfileView extends \yii\db\ActiveRecord
{

    const IMPRESSION_COUNT =1;
    const SOURCE_TYPE_POST =1;
    const SOURCE_TYPE_REELS =2;
    const SOURCE_TYPE_STORY =3;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'profile_view';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','reference_id','created_at','age','gender','country_id','profile_category_id','is_follower','source_type','impression_count'], 'integer'],
            [['reference_id'], 'required', 'on'=>'create']
            

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
            'reference_id' => Yii::t('app', 'Ad'),
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
    

}
