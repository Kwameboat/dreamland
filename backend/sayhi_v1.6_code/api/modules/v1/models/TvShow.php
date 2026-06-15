<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\LiveTvFavorite;
use api\modules\v1\models\LiveTvCategory;
use api\modules\v1\models\TvShowEpisode;
use api\modules\v1\models\Rating;

class TvShow extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;
    public $transaction_id;
   

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tv_show';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            
            [['status', 'id','tv_channel_id','category_id','created_by'], 'integer'],
            [['name','language','age_group','description'], 'string'],
            
            [['name','category_id','tv_channel_id','show_time'], 'required','on'=>['create','update']],
            
            [['category_id','image'], 'safe'],

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
    
    public function fields()
    {
        
        $fields = parent::fields();
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields[] = 'imageUrl';
        //$fields[] = 'categoryName';

        $fields['categoryName'] = (function($model){
            return @$model->category->name;
           // return (@$model->isReported) ? 1: 0;
        });
        // $fields['is_subscribed'] = (function($model){
            

        //     return (@$model->isSubscribe) ? 1: 0;
        // });
        // $fields['is_favorite'] = (function($model){
        //     return (@$model->isFavorite) ? 1: 0;
        // });
        return $fields;
    }


    public function extraFields()
    {
        return ['rating','tvShowEpisode'];
    }


    public function getRating()
    {

        
        $modelRating = new Rating();
      
        $result = $modelRating->find()
            ->select(['rating.id, count(rating.id) as totalCount', 'sum(rating.rating) as totalRating'])
            ->where(['rating.reference_id' => $this->id,  'rating.type' => Rating::TYPE_SHOW])
           
            ->asArray()->one();

           
        $totalCount = (int) $result['totalCount'];
        $totalRating = (int) $result['totalRating'];
        $ratingScore=0;
        if($totalCount>0){
        
            $ratingScore = $totalRating/$totalCount;
        
           // $ratingScore = number_format($ratingScore, 1);
        }
        
        $response = [
            "totalCount" => $totalCount,
            "ratingScore" => $ratingScore
        ];
        return $response;

    }

   
 

    public function getCategory(){

        return $this->hasOne(LiveTvCategory::className(), ['id' => 'category_id']);

    }

    public function getCurrentViewer(){

        return (int)$this->hasMany(LiveTvViewer::className(), ['live_tv_id' => 'id'])->count();

    }


     public function gettvShowEpisode(){
        return $this->hasMany(TvShowEpisode::className(), ['tv_show_id' => 'id'])->andWhere(['and', 'status', TvShowEpisode::STATUS_ACTIVE])->orderBy("id DESC")->limit(10)->all();

    }

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_TV_SHOW,$this->image);
           
        }else{
            return '';
        }
        
    }

    

}
