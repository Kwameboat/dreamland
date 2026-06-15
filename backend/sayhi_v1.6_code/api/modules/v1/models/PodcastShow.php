<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\PodcastSubscriber;
use api\modules\v1\models\PodcastViewer;
use api\modules\v1\models\PodcastFavorite;
use api\modules\v1\models\PodcastCategory;
use api\modules\v1\models\PodcastShowEpisode;

class PodcastShow extends \yii\db\ActiveRecord
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
        return 'podcast_show';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            
            [['status', 'id','podcast_channel_id','category_id','created_by'], 'integer'],
            [['name','language','age_group','description'], 'string'],
            
            [['name','category_id','podcast_channel_id','show_time'], 'required','on'=>['create','update']],
            
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
        return ['podcastShowEpisode'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(PodcastCategory::className(), ['id' => 'category_id']);

    }

    public function getCurrentViewer(){

        return (int)$this->hasMany(PodcastViewer::className(), ['podcast_id' => 'id'])->count();

    }


     public function getpodcastShowEpisode(){
        return $this->hasMany(PodcastShowEpisode::className(), ['podcast_show_id' => 'id'])->andWhere(['and', 'status', PodcastShowEpisode::STATUS_ACTIVE])->orderBy("id DESC")->limit(10)->all();

    }

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST_SHOW,$this->image);
           
        }else{
            return '';
        }
        
    }

    

}
