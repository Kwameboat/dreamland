<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\Category;
use api\modules\v1\models\User;


/**
 * This is the model class 
 *
 */
class PodcastShowEpisode extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    
    public $imageFile;
    public $audioFile;
    
    const FILE_SOURCE_MANUALL_UPLOAD  = 1;
    const FILE_SOURCE_FTP  = 2;
    
    
    
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
            
            [['status', 'id','podcast_show_id','created_by','file_source'], 'integer'],
            [['name','episode_period','audio'], 'string'],
            
            [['name','podcast_show_id','created_at','file_source'], 'required','on'=>['create','update']],
            
            [['image','audioFile'], 'safe'],
            [['audioFile'], 'file', 'skipOnEmpty' => true],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            // [['video'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, mp4','maxFiles' => 2],
            [['audioFile'], 'required','on'=>'create', 'when' => function ($model) {
                return $model->file_source == '1';
            },'whenClient' => "function (attribute, value) { 
                return $('#podcastshowepisode-file_source').val() == '1'; 
            }"],
            [['audio'], 'required','on'=>'create', 'when' => function ($model) {
                return $model->file_source == '2';
            },'whenClient' => "function (attribute, value) { 
                return $('#podcastshowepisode-file_source').val() == '2'; 
            }"],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'podcast_show_id' => Yii::t('app', 'Show'),
            'created_at' => Yii::t('app', 'Show Episode Date'),
            'created_by' => Yii::t('app', 'Created by'),
            'audio' => Yii::t('app', 'Audio'),
            
            
        ];
    }



    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getFileSourceDropDownData()

    {
    
        return array(self::FILE_SOURCE_MANUALL_UPLOAD => 'Manuall Upload', self::FILE_SOURCE_FTP => 'FTP Upload');
    }

  
    
    
    public function getImageUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST_SHOW,$this->image);

        
    }

    public function getVideoUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST,$this->video);

        
    }

    
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

 

    public function beforeSave($insert)
    {
        if ($insert) {
            // $this->created_at = time();
            $this->created_by   =   Yii::$app->user->identity->id;
            
        }

        
        return parent::beforeSave($insert);
    }

    public function getTvShowList(){
        return $this->find()->select(['id','name','image'])->all();
    }

    public function getAudioUrl(){
        
        $audio = $this->audio;
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST_SHOW,$audio);


    }

    public function getShowName()
    {
        return $this->hasOne(PodcastShow::className(), ['id' => 'podcast_show_id']);

    }

    public function getPodcastShowName($podcastShowID)
    {
        return PodcastShow::find()->where(['id'=>$podcastShowID])->one();

    }
}
