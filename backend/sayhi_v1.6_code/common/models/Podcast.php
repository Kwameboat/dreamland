<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\PodcastCategory;


/**
 * This is the model class 
 *
 */
class Podcast extends \yii\db\ActiveRecord
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
        return 'podcast';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            
            [['status', 'id','category_id','priority','is_paid','paid_coin'], 'integer'],
            [['name','web_url','description','copyright'], 'string'],
            
            [['name','category_id'], 'required','on'=>['create','update']],
            
            [['category_id','image','priority'], 'safe'],

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
            'web_url' => Yii::t('app', 'Website URL'),
            'is_paid' => Yii::t('app', 'Is Paid ?'),
            'paid_coin' => Yii::t('app', 'Coin'),
            'copyright' => Yii::t('app', 'Copyright'),
            
            
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
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PODCAST,$this->image);

        
    }

    
    public function getCategory()
    {
        return $this->hasOne(PodcastCategory::className(), ['id' => 'category_id']);

    }

   



    

}
