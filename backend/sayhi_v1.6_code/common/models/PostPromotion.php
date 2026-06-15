<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Event;


/**
 * This is the model class 
 *
 */
class PostPromotion extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PAUSED = 3;
    //const STATUS_EXPIRED = 9;
    const STATUS_ACTIVE = 10;
    const STATUS_COMPLETED = 11;
    const AD_PROMOTION_RATE = 2;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'post_promotion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
          
            
            [['status', 'id','post_id','type','is_audience_automatic','audience_id','amount','duration','daily_promotion_limit','expiry','total_reached','total_uniq_reached','status','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['url','url_text'], 'string'],
            [['total_amount','total_spend','tax','grand_amount',], 'float'],
            [[ 'post_id','audience_id' ], 'required','on'=>['create','update']],
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'post_id' => Yii::t('app', 'Post'),
            'status' => Yii::t('app', 'Status'),
            'audience_id' => Yii::t('app', 'Audience'),
            'expiry' => Yii::t('app', 'End date'),
            'created_at' => Yii::t('app', 'Create date')
            
            
            
        ];
    }
    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
        }else{
            $this->updated_at = time();
        }
        return parent::beforeSave($insert);
    }


    public function getStatus()
    {
       if($this->status==$this::STATUS_DELETED){
           return 'Deleted';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }else if($this->status==$this::STATUS_PENDING){
        return 'Pending';    
    }else if($this->status==$this::STATUS_REJECTED){
        return 'Rejected';    
    }else if($this->status==$this::STATUS_PAUSED){
        return 'Paused';    
    }else if($this->status==$this::STATUS_COMPLETED){
        return 'Completed';    
    }
    }
  
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);

    }

   
    public function getAudience()
    {
        return $this->hasOne(Audience::className(), ['id'=>'audience_id']);
        
    }
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id'=>'post_id']);
        
    }
    // public function getAudience()
    // {
    //     return $this->hasOne(Post::className(), ['id'=>'post_id']);
        
    // }

    

}
