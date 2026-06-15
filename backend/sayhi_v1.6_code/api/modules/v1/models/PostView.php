<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;

class PostView extends \yii\db\ActiveRecord
{

    const IMPRESSION_COUNT =1;
    const VIEW_SOURCE_TYPE_NORMAL =1;
    const VIEW_SOURCE_TYPE_PROMOTION =2;
    const AD_PROMOTION_STATUS_NOT_ADDED =0;
    const AD_PROMOTION_STATUS_ADDED =1;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'post_view';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','post_id','created_at','age','gender','country_id','profile_category_id','is_follower','impression_count','view_source','post_promotion_id','ad_post_impression_count','ad_post_impression_created_at','is_ad_promotion_status'], 'integer'],
            [['post_id'], 'required', 'on'=>'create']
            

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
            'post_id' => Yii::t('app', 'Ad'),
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
