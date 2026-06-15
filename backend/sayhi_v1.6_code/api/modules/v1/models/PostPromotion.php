<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Audience;
use api\modules\v1\models\Ad;
use api\modules\v1\models\AdPromotionCard;
use api\modules\v1\models\AdViewLog;
use api\modules\v1\models\OrderItem;
use api\modules\v1\models\PostView;
use api\modules\v1\models\Setting;

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
    const STATUS_CANCEL = 12;
    public $current_status;
    public $payments;
    

    
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

            [['id', 'status','current_status','post_id','type','is_audience_automatic','audience_id','duration','expiry','total_reached','created_at', 'created_by', 'updated_at', 'updated_by','total_uniq_reached'], 'integer'],
            [['amount','total_amount','tax','grand_amount','daily_promotion_limit'], 'number',],
            [['url','url_text'], 'string', 'max' => '250'],
            [['post_id','type','duration'], 'required', 'on' => 'create'],//,'is_audience_automatic'
            [['id','status'], 'required', 'on' => 'updateStatus'],
            [['id'], 'required', 'on' => 'cancel'],
            [['payments'], 'safe'],
            [['is_audience_automatic'], 'required', 'when' => function ($model) { return $model->audience_id == null;}, 'on' => 'create', 'enableClientValidation' => false ],
            [['audience_id'], 'required', 'when' => function ($model) { return $model->is_audience_automatic == null;},  'on' => 'create','enableClientValidation' => false ],
           
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
            'status' => Yii::t('app', 'Status'),
            'name' => Yii::t('app', 'Name'),
            'created_at'=> Yii::t('app', 'Created At'),
            'url'=> Yii::t('app', 'Url'),
            'url_text'=> Yii::t('app', 'Url Text'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
          
        }
        
        return parent::beforeSave($insert);
    }


    public function fields()
    {
        $fields = parent::fields();

       
        return $fields;
    }
    public function extraFields()
    {
       
        return ['keyword','audience','totalSpend','totalOrder'];
    }



    
    public function updateReachedCounter($adPromotionIds)
    {
       // print_r($adPromotionIds);
       $adPromotionIds  = (array_unique($adPromotionIds));
       
        foreach($adPromotionIds as $record){
            $promoitonId = (int)$record;
            
            if($promoitonId >0){
                $result = $this->findOne($promoitonId);
                $result->total_reached =  $result->total_reached+1;
                $result->save(false);

            }
            
        }
    }

    public function getTotalSpend()
    {
       $modelSetting = new Setting(); //PostPromotion
       $promotionData = $modelSetting->find()->one();
       $viewsPerPrice = $promotionData->each_view_price_promotion;
       $totalViewResult = $this->postPromotionTotalView;
       $totalView   = count($totalViewResult);
       $totalSpend = $viewsPerPrice*$totalView;
       return $totalSpend;
       

       
    }

    // public function getTotalOrder()
    // {
       
    //   return $totalOrder = (int)$this->hasMany(OrderItem::className(), ['ad_promotion_id'=>'id'])->count();
    //   // $totalOrder   = count($totalOrderResult);
    //   // return $totalOrder;
       
       
    // }

    
    
    
    public function getAudience()
    {
        return $this->hasMany(Audience::className(), ['id'=>'audience_id']);
        
    }
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id'=>'post_id']);
        
    }
    // public function getAdPromotionCard()
    // {
    //     return $this->hasOne(AdPromotionCard::className(), ['id'=>'type']);
        
    // }

    public function getPostPromotionTotalView()
    {
        return $this->hasMany(PostView::className(), ['post_promotion_id'=>'id']);
        
    }

    public function updatePromotionReachCounter($promotionId , $postId){

        $result = $this->findOne($promotionId);
        // print_r($result);
        // exit;
        $userId = @Yii::$app->user->identity->id;
        $modelPostPromotion = new PostPromotion(); //PostPromotion
        $promotionData = $modelPostPromotion->find()->where(['id'=>$promotionId , 'post_id'=>$postId])->one();
        $totalReach=0;
        if(!empty($promotionData)){
           $totalReach = $promotionData->total_reached;
           $result->total_reached = @$totalReach+1;
           $oldTotalSpend = $promotionData->total_spend;
           $post_view = PostView::find()->where(['post_promotion_id'=>$promotionId , 'post_id'=>$postId , 'user_id'=>$userId , 'is_ad_promotion_status'=>PostView::AD_PROMOTION_STATUS_NOT_ADDED ])->one();
           if($post_view){
            if($post_view['is_ad_promotion_status']==PostView::AD_PROMOTION_STATUS_NOT_ADDED){
                $oldtotalUniqReach = $promotionData->total_uniq_reached;
                $totalUniqReach = @$oldtotalUniqReach+1;
                $result->total_uniq_reached = $totalUniqReach;
                $totalSpend = $oldTotalSpend;
                $adViewRate = PostPromotion::AD_PROMOTION_RATE;
                $result->total_spend = ($totalUniqReach * $adViewRate);
            }
            $post_view->is_ad_promotion_status = PostView::AD_PROMOTION_STATUS_ADDED;
            $post_view->ad_post_impression_created_at = time();
            $post_view->save();
           }
        //    print_r($post_view);  
        //    exit;
        // $result->popular_point   = $result->popular_point + Yii::$app->params['postPopularityPoint']['postView'];

        if($result->save(false)){
           return true;
        }else{
            return false;
        }
    }     
    }


    public function getPromotionPostViewLimit()
    {
        $currentDate = date('Y-m-d');
        // $currentDate = time();
          return $this->hasMany(PostView::className(), ['post_promotion_id' => 'id'])
          //->andWhere([ 'post_promotion_id' => 'promotionPost.id'])
        //   ->joinWith('promotionPost')
            ->andWhere(['=', 'DATE(FROM_UNIXTIME(ad_post_impression_created_at))', $currentDate]);
            // ->andWhere(['>', 'COUNT(id) as ttotal ', 'post_promotion.daily_promotion_limit']);
            // ->count();

        // return $count;
    }

//     public function getPromotionPostViewLimit()
// {

//     $currentDate = date('Y-m-d');
//     $count = PostView::find()
//         ->andWhere(['post_promotion_id' => $this->id])
//         ->andWhere(['=', 'DATE(FROM_UNIXTIME(ad_post_impression_created_at))', $currentDate])
//         ->count();

//     return $count;
// }


}
