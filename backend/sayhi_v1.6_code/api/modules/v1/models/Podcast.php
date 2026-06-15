<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\PodcastSubscriber;
use api\modules\v1\models\PodcastViewer;
use api\modules\v1\models\PodcastFavorite;
use api\modules\v1\models\PodcastCategory;
use api\modules\v1\models\TvShow;
use common\models\TvShowEpisode;

class Podcast extends \yii\db\ActiveRecord
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
        return 'podcast';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','category_id','priority','paid_coin'], 'integer'],
            [['name','web_url'], 'string'],
            [['id' ], 'required','on'=>['subscribe','stopViewing','addFavorite','removeFavorite']],
            [['transaction_id'], 'safe'],
            
            
            
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
        $fields['is_subscribed'] = (function($model){
            

            return (@$model->isSubscribe) ? 1: 0;
        });
        $fields['is_favorite'] = (function($model){
            return (@$model->isFavorite) ? 1: 0;
        });
        return $fields;
    }


    public function extraFields()
    {
        return ['subCategory','currentViewer','podcastShow'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(PodcastCategory::className(), ['id' => 'category_id']);

    }

    public function getCurrentViewer(){

        return (int)$this->hasMany(PodcastViewer::className(), ['podcast_id' => 'id'])->count();

    }

    public function getPodcastShow(){
        
        return $this->hasMany(PodcastShow::className(), ['podcast_channel_id' => 'id'])->andWhere(['and', 'status', PodcastShow::STATUS_ACTIVE])->limit(10);

    }

    // public function getTvShowEpisode(){
    //     echo "hello";
    //     // $response = $this->hasMany(TvShowEpisode::className(), ['tv_show_id' => 'id'])->andWhere(['and', 'status', TvShowEpisode::STATUS_ACTIVE])->all();
    //     // print_r($response);
    //     exit("kdjk");

    // }


    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST,$this->image);
           
        }else{
            return '';
        }
        
    }

    public function getIsSubscribe()
    {
        return $this->hasOne(PodcastSubscriber::className(), ['podcast_id'=>'id'])->andOnCondition(['podcast_subscriber.user_id' => Yii::$app->user->identity->id]);
        
    }

    public function getIsFavorite()
    {
        return $this->hasOne(PodcastFavorite::className(), ['podcast_id'=>'id'])->andOnCondition(['podcast_favorite.user_id' => Yii::$app->user->identity->id]);
        
    }
    

    public function getPodcastMySubscriber()
    {
        return $this->hasMany(PodcastSubscriber::className(), ['podcast_id'=>'id']);
        
    }

    public function getPodcastMyFavorite()
    {
        return $this->hasMany(PodcastFavorite::className(), ['podcast_id'=>'id']);
        
    }

    

}
