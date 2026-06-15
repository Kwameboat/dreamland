<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\Comment;
use common\models\Competition;
use common\models\ReportedPost;
use common\models\PostGallary;


class Post extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;
    const STATUS_BLOCKED=9;

    const IS_SHARE_POST_YES=1;
    const IS_SHARE_POST_NO=0;

    const IS_WINNING_NO=0;
    const IS_WINNING_YES=1;


    const TYPE_NORMAL=1;
    const TYPE_COMPETITION=2;
    const TYPE_CLUB         =3;
    const TYPE_REEL         =4;

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


    public  $imageFile;
    public  $videoFile;
    public  $hashtag;
        
    
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
            [[ 'title','image','hashtag','latitude','longitude','address','description','unique_id'], 'string'],
            [['id','type','post_content_type','content_type_reference_id','competition_id','club_id','status','user_id','total_view','total_like','total_share','total_comment','is_share_post','share_level','origin_post_id','audio_id','audio_start_time','audio_end_time','is_add_to_post', 'created_at','created_by', 'updated_by','poll_id','is_comment_enable'], 'integer'],
            
            [['updated_by', 'updated_at','hashtag','audio_id','is_share_post','share_level','origin_post_id'], 'safe'],
            [['title'], 'string', 'max' => 256],
            ['status', 'in', 'range' => [0,9,10]],
            //[[ 'title','imageFile' ], 'required','on'=>'create'],
            
            //[[ 'title','category_id','currency' ], 'required','on'=>'update'],
            //[['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg','on'=>'create'],
            //[['videoFile'], 'file', 'skipOnEmpty' => false,'extensions' => 'mp4','maxSize' => '6048000','on'=>'create'],
            //[[ 'id' ], 'required','on'=>'share'],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'gif,png, jpg','on'=>'create'],
            [['imageFile'], 'safe'],
            
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => Yii::t('app', 'Title'),
            'status' => Yii::t('app', 'Status'),
            'video' => Yii::t('app', 'video'),
            'image' => Yii::t('app', 'Image'),
            'user_id' => Yii::t('app', 'User'),
            'total_view' => Yii::t('app', 'Total views'),
            'total_share' => Yii::t('app', 'Total share'),
            'total_comment' => Yii::t('app', 'Total comment'),
            'is_share_post' => Yii::t('app', 'Share Post'),
            'audio_id' => Yii::t('app', 'Audio')
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
            $this->user_id       =   Yii::$app->user->identity->id;
            $uniqueId = time().rand(1,99999).Yii::$app->user->identity->id;
            $this->unique_id       =  md5($uniqueId);
          
        }else{
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }

        
        return parent::beforeSave($insert);
    }
    

    public function getStatus()
    {
       if($this->status==$this::STATUS_BLOCKED){
           return 'Blocked';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_BLOCKED => 'Blocked');
    }
  
   
    public function getTotalPostCount()
    {
        $postAllow = [];
        $postAllow[] = Post::TYPE_NORMAL;
        $postAllow[] = Post::TYPE_COMPETITION;
        $postAllow[] = Post::TYPE_CLUB;
        return Post::find()->where(['<>','status',self::STATUS_DELETED])->andWhere(['type'=>$postAllow])->count();
        
    }
   
    public function getLastTweleveMonth()
    {
        $month =  strtotime("+1 month");
        for ($i = 1; $i <= 12; $i++) {
            $months[(int)date("m", $month)] = date("M", $month);
            $month = strtotime('+1 month', $month);
        }
        return $months;
        
    }


    public function getLastTweleveMonthPost()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();
        $postAllow = [];
        $postAllow[] = Post::TYPE_NORMAL;
        $postAllow[] = Post::TYPE_COMPETITION;
        $postAllow[] = Post::TYPE_CLUB;
        $postAllowString  = implode(',',$postAllow);
        
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, count(id) as total_ad FROM post where status!=0 and type IN($postAllowString) and from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();


        foreach($months as $key => $month){
            $found_key = array_search($key, array_column($res, 'month'));  
            //echo gettype($found_key), "\n";
            if(is_int($found_key)){
                $totalAd =  $res[$found_key]['total_ad'];
            }else{
                $totalAd = 0;
            }
            //echo $totalAds;
            /*echo '=====================';
            echo '<br>';
            echo $key.'#'.$month;
            echo '<br>';*/

            //print_r($found_key);
            
            $totalAds[]=$totalAd;
           
            $monthArr[]=$month;

        }
        $output=[];

        $output['data'] = $totalAds;
        $output['dataCaption'] = $monthArr;
        return $output;

        
    }
    


    
    public function getAudioUrl(){
        $audio = $this->audio;
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_POST,$audio);
        
    }

    public function getImageUrl(){
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_POST,$this->image);
        
    }
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }


    public function getPostComment()
    {
        return $this->hasMany(PostComment::className(), ['post_id'=>'id'])->andOnCondition(['post_comment.level' => PostComment::LEVEL_ONE]);
        
    }
    
    

    public function getPostGallary()
    {
        return $this->hasMany(PostGallary::className(), ['post_id'=>'id']);
        
    }
    
    public function getCompetition()
    {
        return $this->hasOne(Competition::className(), ['id'=>'competition_id']);
        
    }
    
    public function getReportedPost()
    {
        return $this->hasMany(ReportedPost::className(), ['post_id'=>'id']);
        
    }
    
    public function getReportedPostActive()
    {
        return $this->hasMany(ReportedPost::className(), ['post_id'=>'id'])->andOnCondition(['reported_post.status' => ReportedPost::STATUS_PENDING]);
        
    }


   /* public function getImageUrlBig(){
        
        $image = $this->image;
        if(empty($this->image)){
            $image  ='default.png';
        }
        return Yii::$app->urlManagerFrontend->baseUrl.'/uploads/banner/original/'.$image;
        
    }*/

    public function getAudioName()
    {
        return $this->hasOne(Audio::className(), ['id'=>'audio_id']);
        
    }

        public function getPostReelGallary()
    {
        return $this->hasOne(PostGallary::className(), ['post_id'=>'id']);
        
    }

    public function getTotalReelsCount()
    {
        return Post::find()->where(['<>','status',self::STATUS_DELETED])->andWhere(['post.type'=> Post::TYPE_REEL])->count();
    }

    public function getLastTweleveMonthReels()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();
        
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, count(id) as total_ad FROM post where status!=0 and type=4 and from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();

        foreach($months as $key => $month){
            $found_key = array_search($key, array_column($res, 'month'));  
            //echo gettype($found_key), "\n";
            if(is_int($found_key)){
                $totalAd =  $res[$found_key]['total_ad'];
            }else{
                $totalAd = 0;
            }
            
            $totalAds[]=$totalAd;
           
            $monthArr[]=$month;

        }
        $output=[];

        $output['data'] = $totalAds;
        $output['dataCaption'] = $monthArr;
        return $output;

        
    }

    public function getPostCompetitionGallary()
    {
        return $this->hasOne(PostGallary::className(), ['post_id'=>'id'])->andOnCondition(['type'=>PostGallary::TYPE_COMPETITION ,'is_default'=>PostGallary::IS_DEFAULT_YES])->andOnCondition(['status'=>PostGallary::STATUS_ACTIVE]);
        
    }

    public function getLatestPost()
    {
        $postAllow = [];
        $postAllow[] = Post::TYPE_NORMAL;
        $postAllow[] = Post::TYPE_COMPETITION;
        $postAllow[] = Post::TYPE_CLUB;
        return Post::find()
        ->where(['<>','status',Post::STATUS_DELETED])
        ->andWhere(['type'=>$postAllow])->orderBy(['id'=>SORT_DESC])->limit(13)->all();
        
    }
    public function getCommonDropDownData()
    {
        return array(1 => 'Yes', 0 => 'No');
    }
   
  
}
