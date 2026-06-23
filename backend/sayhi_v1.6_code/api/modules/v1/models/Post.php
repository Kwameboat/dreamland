<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\HashTag;
use api\modules\v1\models\Audio;
use api\modules\v1\models\PostLike;
use api\modules\v1\models\PostView;
use api\modules\v1\models\PostComment;
use api\modules\v1\models\Follower;
use api\modules\v1\models\ReportedPost;
use api\modules\v1\models\PostGallary;
use common\helpers\DreamlandMediaUrl;
use api\modules\v1\models\MentionUser;
use api\modules\v1\models\Club;
use api\modules\v1\models\GiftHistory;
use api\modules\v1\models\UserFavorite;
use api\modules\v1\models\Competition;
use api\modules\v1\models\Event;
use api\modules\v1\models\Job;
use api\modules\v1\models\Campaign;
use api\modules\v1\models\Coupon;
use api\modules\v1\models\Ad;
use api\modules\v1\models\ChatRoom;
use api\modules\v1\models\Pin;
use api\modules\v1\models\Collaborate;








class Post extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;
    const STATUS_BLOCKED=9;

    const IS_SHARE_POST_YES=1;
    const IS_SHARE_POST_NO=0;

    const TYPE_NORMAL       =1;
    const TYPE_COMPETITION  =2;
    const TYPE_CLUB         =3;
    const TYPE_REEL         =4;
    const TYPE_RESHARE_POST =5;
    const TYPE_EVENT        =6;
    const TYPE_CAMPAIGN     =7;

    
    

    const IS_WINNING_NO=0;
    const IS_WINNING_YES=1;


    const COMMON_NO=0;
    const COMMON_YES=1;

    const CONTENT_TYPE_TEXT = 1;
    const CONTENT_TYPE_MEDIA = 2;
    const CONTENT_TYPE_LOCATION = 3;
    const CONTENT_TYPE_POLL = 4;
    const CONTENT_TYPE_COMPETITION = 5;
    const CONTENT_TYPE_EVENT = 6;
    const CONTENT_TYPE_FUND_RAISING = 7;
    const CONTENT_TYPE_JOB = 8;
    const CONTENT_TYPE_DECLARE_COMPETITION = 9;
    const CONTENT_TYPE_COUPON = 10;
    const CONTENT_TYPE_DONATE_FUND_RAISING = 11;
    const CONTENT_TYPE_AD = 12;
    const CONTENT_TYPE_CLUB = 13;
    const CONTENT_TYPE_OPEN_GROUP =14;

    public  $imageFile;
    public  $videoFile;
    public  $hashtag;
    public  $mentionUser;
    public  $gallary;
    
    
  
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'post';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'title','image','hashtag','mentionUser','latitude','longitude','address','description','unique_id'], 'string'],
            [['id','type','post_content_type','content_type_reference_id','competition_id','club_id','event_id','campaign_id','status','user_id','total_view','total_like','total_share','total_comment','is_share_post','share_level','origin_post_id','audio_id','audio_start_time','audio_end_time','is_add_to_post', 'created_at','created_by', 'updated_by','poll_id','is_comment_enable','display_whose','is_paid','price_credits','category_id'], 'integer'],
            [['updated_by', 'updated_at','hashtag','mentionUser','audio_id','is_share_post','share_level','origin_post_id','gallary','share_comment','appraisal_status'], 'safe'],
           // [['title'], 'string', 'max' => 256],
            ['status', 'in', 'range' => [0,9,10]],
            [['type','post_content_type' ], 'required','on'=>'create'],
            [['category_id'], 'required', 'when' => function ($model) {
                return (int) $model->type === self::TYPE_REEL;
            }, 'whenClient' => "function (attribute, value) { return $('#post-type').val() == '4'; }", 'on' => 'create'],
            [['competition_id','gallary' ], 'required','on'=>'competitionImage'],

            
            
            //[['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg','on'=>['create','competitionImage']],
            
            [[ 'id' ], 'required','on'=>'share'],
            [[ 'id' ], 'required','on'=>'reportPost'],
            [[ 'unique_id' ], 'safe'],
            
            
            
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
            $this->created_by  =   Yii::$app->user->identity->id;
            $this->user_id       =   Yii::$app->user->identity->id;
            $uniqueId = time().rand(1,99999).Yii::$app->user->identity->id;
            $this->unique_id       =  md5($uniqueId);
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['share_link'] = (function ($model) {
            return Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl . '/post/share/?pid=' . $model->unique_id;
        });
        $fields[] = 'postGallary';
        $fields['reel_video_url'] = static function ($model) {
            return DreamlandMediaUrl::resolvePostVideoUrl($model);
        };
        $fields['is_like'] = (function ($model) {
            return self::safeFlagRelation($model, 'isLike');
        });
        $fields['is_reported'] = (function ($model) {
            if (Yii::$app->user->isGuest) {
                return 0;
            }
            return self::safeFlagRelation($model, 'isReported');
        });
        $fields['hashtags'] = (function ($model) {
            return self::safeMapRelation($model, 'hashtags', static function ($tag) {
                return $tag->hashtag;
            });
        });
        $fields['mentionUsers'] = (function ($model) {
            return self::safeMapRelation($model, 'mentionUsers', static function ($user) {
                return ['user_id' => $user->user_id, 'username' => $user->username];
            });
        });
        $fields['is_promotion'] = (function ($model) {
            return self::safeFlagRelation($model, 'promotionPost');
        });
        $fields['dreamland'] = (function ($model) {
            try {
                $viewerId = Yii::$app->user->isGuest ? null : Yii::$app->user->identity->id;
                return Yii::$app->dreamlandPaywall->decorateFeedItem($model, $viewerId);
            } catch (\Throwable $e) {
                return [
                    'is_paid' => (bool) ($model->is_paid ?? false),
                    'is_unlocked' => !(bool) ($model->is_paid ?? false),
                    'price_credits' => (int) ($model->price_credits ?? 0),
                ];
            }
        });

        return $fields;
    }

    private static function safeFlagRelation(self $model, string $relation): int
    {
        try {
            return @$model->{$relation} ? 1 : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function safeMapRelation(self $model, string $relation, callable $map): array
    {
        try {
            $result = [];
            foreach ($model->{$relation} as $item) {
                $result[] = $map($item);
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    public function extraFields()
    {
        return ['user','clubDetail','audio','giftSummary','promotionPost','postPromotionData','isPostView','isFavorite','isPin','originPost','pollDetails','contentReferenceDetail','event','campaign','collaborate'];
    }
    
   
    public function getStatusString()
    {
        if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
        }else if($this->status==$this::STATUS_DELETED){
            return 'Deleted';    
        }else if($this->status==$this::STATUS_BLOCKED){
            return 'Blocked';    
        }
       
    }
    public function getImageUrl(){
        if($this->image){
            return Yii::$app->params['pathUploadImage'] ."/".$this->image;
        }
     }

    
     /*public function getVideoUrl(){
        if($this->video){
            return Yii::$app->params['pathUploadVideo'] ."/".$this->video;
        } 
     }*/
     
     public function updateLikeCounter($postId,$type='like'){

         $result = $this->findOne($postId);
         $modelPostLike = new PostLike();
         $totalCount = $modelPostLike->find()->where(['post_id'=>$postId])->count();
         $result->total_like = $totalCount;
        
        if($type=='like'){
            $result->popular_point   = $result->popular_point + Yii::$app->params['postPopularityPoint']['postLike'];
        }else{
            $result->popular_point   = $result->popular_point - Yii::$app->params['postPopularityPoint']['postLike'];
        }

         if($result->save(false)){
            return  $totalCount;
         }else{
             return false;
         }
               
     }



     public function updateViewCounter($postId){

        $result = $this->findOne($postId);
        $modelPostLike = new PostView();
        $totalCount = $modelPostLike->find()->where(['post_id'=>$postId])->count();
        $result->total_view = @$totalCount;
        $result->popular_point   = $result->popular_point + Yii::$app->params['postPopularityPoint']['postView'];

        if($result->save(false)){
           return  $totalCount;
        }else{
            return false;
        }
              
    }

    public function updateShareCounter($postId){

        $result = $this->findOne($postId);
        $result->total_share = $result->total_share +1;
        $result->popular_point   = $result->popular_point + Yii::$app->params['postPopularityPoint']['postShare'];
        return $result->save(false);
              
    }


    public function updateCommentCounter($postId){

        $result = $this->findOne($postId);
        $model = new PostComment();
        $totalCount = $model->find()->where(['post_id'=>$postId,'status'=>$model::STATUS_ACTIVE])->count();
        $result->total_comment = $totalCount;
        if($result->save(false)){
           return  $totalCount;
        }else{
            return false;
        }
              
    }


     public function getIsLike()
     {
         return $this->hasOne(PostLike::className(), ['post_id'=>'id'])->andOnCondition(['post_like.user_id' => Yii::$app->user->identity->id]);
         
     }
     public function getIsPin()
     {
         return $this->hasOne(Pin::className(), ['reference_id'=>'id'])->andOnCondition(['pin.user_id' => Yii::$app->user->identity->id,'pin.type'=>Pin::TYPE_POST]);
         
     }
 


    public function getGiftSummary(){

        $modelGiftHistory =  new GiftHistory();
       
        $result = $modelGiftHistory->find()
        ->select(['count(id) as totalGift','sum(coin) as totalCoin'])
        ->where(['post_id'=>$this->id,'send_on_type'=>GiftHistory::SEND_TO_TYPE_POST])->asArray()->one();
        
        $totalGift = (int)$result['totalGift'];
        $totalCoin = (int)$result['totalCoin'];
        
        $response=[
            'totalGift'=>$totalGift,
            'totalCoin'=>$totalCoin

        ];
        return $response;

     }


       
    
  
    /**
     * RELEATION START
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    public function getHashtags()
    {
        return $this->hasMany(HashTag::className(), ['post_id'=>'id']);
        
    }
    public function getMentionUsers()
    {
        return $this->hasMany(MentionUser::className(), ['post_id'=>'id']);
        
    }
    

    public function getAudioDetail()
    {
        return $this->hasOne(Audio::className(), ['id'=>'audio_id']);
        
    }
    public function getFollowers()
    {
        return $this->hasMany(Follower::className(), ['user_id'=>'user_id'])->onCondition(['NOT',['follower.type'=> Follower::FOLLOW_REQUEST]]);
        
    }
    public function getCollaborate()
    {
        return $this->hasMany(Collaborate::className(), ['reference_id'=>'id'])->andOnCondition(['collaborate.type' => Collaborate::TYPE_POST]);
    }
     
    public function getisReported()
    {
        if (Yii::$app->user->isGuest) {
            return $this->hasOne(ReportedPost::className(), ['post_id' => 'id'])
                ->andOnCondition('0=1');
        }
        return $this->hasOne(ReportedPost::className(), ['post_id' => 'id'])
            ->andOnCondition(['reported_post.user_id' => Yii::$app->user->identity->id]);
    }

    public function getPostGallary(){
        return $this->hasMany(PostGallary::className(), ['post_id'=>'id']);
     }
     public function getClubDetail()
    {
        return $this->hasOne(Club::className(), ['id'=>'club_id']);
    }
    public function getAudio()
    {
        return $this->hasOne(Audio::className(), ['id'=>'audio_id']);
    }

    public function getCountCategoriesType($id){

    }
     

    public function getPromotionPost()
    {
        return $this->hasMany(PostPromotion::className(), ['post_id'=>'id']);
        
    }

    public function getPostPromotionData()
    {
        return $this->hasOne(PostPromotion::className(), ['post_id'=>'id']);
        
    }

    public function getAdViewLogToday()
    {

        $result =  $this->hasMany(PostView::className(), ['post_id'=>'id']);
        return $result;
        
        
    }

    public function getIsPostView()
    {
        return (int) $this->hasOne(PostView::className(), ['post_id'=>'id'])->andOnCondition(['post_view.user_id' => @Yii::$app->user->identity->id])->count();
        
        
    }
    public function getIsFavorite()
    {
        return (int) $this->hasOne(UserFavorite::className(), ['reference_id'=>'id'])->andOnCondition(['user_favorite.type' => UserFavorite::TYPE_POST,'user_favorite.user_id' => @Yii::$app->user->identity->id])->count();
        
        
    }
    
    public function getFavorite()
    {
        
          return $this->hasOne(UserFavorite::className(), ['reference_id'=>'id'])->andOnCondition(['user_favorite.type' => UserFavorite::TYPE_POST,'user_favorite.user_id' => @Yii::$app->user->identity->id]);
        
        
        
        
    }
    public function getOriginPost()
    {
        if($this->type == Post::TYPE_RESHARE_POST ){
            return $this->hasOne(Post::className(), ['id'=>'origin_post_id'])->andOnCondition(['status' => Post::STATUS_ACTIVE]);
        
        }
        
        
    }

    public function getPollDetails()
    {
        if($this->poll_id != null ){
            return $this->hasOne(Poll::className(), ['id'=>'poll_id'])->andOnCondition(['status' => Poll::STATUS_ACTIVE]);
        }
    }

    public function getContentReferenceDetail()
    {
        $response=null;
        if($this->post_content_type==Post::CONTENT_TYPE_POLL){
            $response =  $this->hasOne(Poll::className(), ['id'=>'content_type_reference_id']);
        }
        if($this->post_content_type==Post::CONTENT_TYPE_COMPETITION || $this->post_content_type==Post::CONTENT_TYPE_DECLARE_COMPETITION){
            $response =  $this->hasOne(Competition::className(), ['id'=>'content_type_reference_id']);
        }else if($this->post_content_type==Post::CONTENT_TYPE_EVENT){
            $response =  $this->hasOne(Event::className(), ['id'=>'content_type_reference_id']);
        }else if($this->post_content_type==Post::CONTENT_TYPE_FUND_RAISING || $this->post_content_type==Post::CONTENT_TYPE_DONATE_FUND_RAISING){
            $response =  $this->hasOne(Campaign::className(), ['id'=>'content_type_reference_id']);
        }
        else if($this->post_content_type==Post::CONTENT_TYPE_JOB){
            $response =  $this->hasOne(Job::className(), ['id'=>'content_type_reference_id']);
        }
        else if($this->post_content_type==Post::CONTENT_TYPE_COUPON ){
            $response =  $this->hasOne(Coupon::className(), ['id'=>'content_type_reference_id']);
        }
        else if($this->post_content_type==Post::CONTENT_TYPE_AD ){
            $response =  $this->hasOne(Ad::className(), ['id'=>'content_type_reference_id']);
        }else if($this->post_content_type==Post::CONTENT_TYPE_CLUB ){
            $response =  $this->hasOne(Club::className(), ['id'=>'content_type_reference_id']);
        }else if($this->post_content_type==Post::CONTENT_TYPE_OPEN_GROUP ){
            $response =  $this->hasOne(ChatRoom::className(), ['id'=>'content_type_reference_id']);
        }
        
        return $response;
    }
    
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id'=>'event_id']);
    }
    
    public function getCampaign()
    {
        return $this->hasOne(Campaign::className(), ['id'=>'campaign_id']);
    }
   
    


}
