<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\LiveTvFavorite;
use api\modules\v1\models\LiveTvCategory;

class TvShowEpisode extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const FILE_SOURCE_MANUAL=1;
    const FILE_SOURCE_FTP=2;
    const FILE_SOURCE_URL=3;

   
    public $imageFile;
    public $transaction_id;
   

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tv_show_episode';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status','tv_show_id'], 'required'],
            
            [['status', 'id','tv_show_id','created_by'], 'integer'],
            [['name','episode_period'], 'string'],
            
            [['name','tv_show_id','created_at'], 'required','on'=>['create','update']],
            
            [['image','video'], 'safe'],
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
        $fields['video'] = 'video';
        $fields['videoUrl'] = 'videoUrl';
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

        return $this->hasOne(LiveTvCategory::className(), ['id' => 'category_id']);

    }

    public function getImageUrl()
    {
        if($this->image){
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_TV_SHOW_EPISODE,$this->image);
           
        }else{
            return '';
        }
        
    }


    public function getVideoUrl()
    {
        
        

        
        if($this->video){
            if($this->file_source==TvShowEpisode::FILE_SOURCE_URL){
                return $this->video;
            }else{
                
                return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_TV_SHOW_EPISODE,$this->video);
            }   
        }else{
            return '';
        }
        
    }


    

}
