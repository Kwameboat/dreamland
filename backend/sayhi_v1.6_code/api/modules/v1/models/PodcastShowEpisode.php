<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\PodcastSubscriber;
use api\modules\v1\models\PodcastViewer;
use api\modules\v1\models\PodcastFavorite;
use api\modules\v1\models\PodcastCategory;

class PodcastShowEpisode extends \yii\db\ActiveRecord
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
        return 'podcast_show_episode';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status','podcast_show_id'], 'required'],
            
            [['status', 'id','podcast_show_id','created_by'], 'integer'],
            [['name','episode_period'], 'string'],
            
            [['name','podcast_show_id','created_at'], 'required','on'=>['create','update']],
            
            [['image','audio'], 'safe'],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            // [['video'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, mp4','maxFiles' => 2],
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
        $fields['audio'] = 'audio';
        $fields['audioUrl'] = 'audioUrl';
        //$fields[] = 'categoryName';

        // $fields['categoryName'] = (function($model){
        //     return @$model->category->name;
        //    // return (@$model->isReported) ? 1: 0;
        // });
       
        return $fields;
    }


    public function extraFields()
    {
        // return ['subCategory','currentViewer','TvShowEpisode'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(PodcastCategory::className(), ['id' => 'category_id']);

    }

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST_SHOW,$this->image);
           
        }else{
            return '';
        }
        
    }


    public function getAudioUrl()
    {
        if($this->audio){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST_SHOW,$this->audio);
           
        }else{
            return '';
        }
        
    }


    

}
