<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\LiveTvCategory;


/**
 * This is the model class 
 *
 */
class TvBanner extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;
    const TYPE_SHOW=2;
    const TYPE_EPISODE=3;
    const TYPE_TV_CHANNEL=1;
    public $imageFile;
    public $tvbannerhiddenurl;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tv_banner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'banner_type','status','start_time','end_time'], 'required'],
            
            
            [['name','reference_id'], 'required','on'=>['create','update']],
            
            [['reference_id','cover_image','priority'], 'safe'],

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
            'reference_id' => Yii::t('app', 'Category'),
            'start_time' => Yii::t('app', 'Start Date'),
            'end_time' => Yii::t('app', 'End Date'),
            'banner_type' => Yii::t('app', 'Banner Type'),
            'cover_image' => Yii::t('app', 'Cover Image'),
            'reference_id' => Yii::t('app', 'Search'),
            
        ];
    }

    public function getPaidDropDownData()
    {
        return array(self::COMMON_NO => 'No', self::COMMON_YES => 'Yes');
    }


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getBannerType()
    {
        return array(self::TYPE_TV_CHANNEL =>'Tv Channel' ,self::TYPE_SHOW => 'Show', self::TYPE_EPISODE => 'Episode');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
  

    public function getBannerData()
    {
       if($this->banner_type==$this::TYPE_EPISODE){
           return 'Episode';
       }else if($this->banner_type==$this::TYPE_SHOW){
           return 'Show';    
       }else if($this->banner_type==$this::TYPE_TV_CHANNEL){
        return 'Tv Channel';    
    }
    }
    
    
    public function getImageUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_TV_BANNER,$this->cover_image);

        
    }

    
    public function getCategory()
    {
        //return $this->hasOne(LiveTvCategory::className(), ['id' => 'category_id']);

    }

   



    

}
