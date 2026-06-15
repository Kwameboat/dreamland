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
class LiveTv extends \yii\db\ActiveRecord
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
        return 'live_tv';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            
            [['status', 'id','category_id','priority','is_live','is_paid','paid_coin'], 'integer'],
            [['name','tv_url','description'], 'string'],
            
            [['name','category_id','is_live'], 'required','on'=>['create','update']],
  
            [['category_id','image','priority'], 'safe'],
            [
                'tv_url', 
                'required', 
                'when' => function ($model) { 
                    return $model->is_live == 1; 
                }, 
                'whenClient' => "function (attribute, value) { 
                    return $('#livetv-is_live').val() == '1'; 
                }"
             ]

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
            'tv_url' => Yii::t('app', 'URL'),
            'is_paid' => Yii::t('app', 'Is Paid ?'),
            'is_live' => Yii::t('app', 'Is Live ?'),
            
            'paid_coin' => Yii::t('app', 'Coin'),
            
            
        ];
    }

    public function getPaidDropDownData()
    {
        return array(self::COMMON_NO => 'No', self::COMMON_YES => 'Yes');
    }
    public function getIsLiveDropDownData()
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
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_LIVE_TV,$this->image);

        
    }

    
    public function getCategory()
    {
        return $this->hasOne(LiveTvCategory::className(), ['id' => 'category_id']);

    }

   



    

}
