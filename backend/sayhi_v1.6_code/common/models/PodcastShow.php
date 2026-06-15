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
class PodcastShow extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    
    public $imageFile;

    
    
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
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'category_id' => Yii::t('app', 'Category'),
            'podcast_channel_id' => Yii::t('app', 'Host'),
            'description' => Yii::t('app', 'Description'),
            'age_group' => Yii::t('app', 'Age Group'),
            'language' => Yii::t('app', 'Language'),
            'created_at' => Yii::t('app', 'Show Date'),
            'created_by' => Yii::t('app', 'Created by'),
            
            
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
  
    
    
    public function getImageUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST_SHOW,$this->image);

        
    }

    
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

 

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by   =   Yii::$app->user->identity->id;
            
        }

        
        return parent::beforeSave($insert);
    }

    public function getPoscastHostName()
    {
        return $this->hasOne(Podcast::className(), ['id' => 'podcast_channel_id']);

    }

    public function getPodcastShowAllEpisode()
    {
        return $this->hasMany(PodcastShowEpisode::className(), ['podcast_show_id' => 'id'])->where(['status' =>PodcastShowEpisode::STATUS_ACTIVE]);

    }

}
