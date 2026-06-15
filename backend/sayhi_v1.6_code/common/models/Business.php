<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\Category;


/**
 * This is the model class 
 *
 */
class Business extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    
    public $imageFile;
    public $exampleFile;
    public $deletePhoto;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'business';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'business_category_id'], 'required'],
            
            [['status', 'id','business_category_id','price_range_from','price_range_to','created_by','created_at','phone','updated_at','updated_by'], 'integer'],
            [['name','open_time','close_time','address','location','latitude','longitude','description','city'], 'string'],
            
            [['name','business_category_id'], 'required','on'=>['create','update']],
            
            [['imageFile',], 'safe'],
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            [['exampleFile'], 'file', 'skipOnEmpty' => true, 'maxFiles' => 100],
            [['deletePhoto','start_date', 'end_date'], 'safe'],
            // [['open_time', 'close_time'], 'time'],

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
            'business_category_id' => Yii::t('app', 'Business Category'),
            'price_range_from' => Yii::t('app', 'Price Range From'),
            'price_range_to' => Yii::t('app', 'Price Range To'),
            'city' => Yii::t('app', 'City'),
            'open_time' => Yii::t('app', 'Open Time'),
            'close_time' => Yii::t('app', 'Close Time'),
            'address' => Yii::t('app', 'Address'),
            'location' => Yii::t('app', 'Location'),
            'latitude' => Yii::t('app', 'Latitude'),
            'longitude' => Yii::t('app', 'Longitude'),
            'description' => Yii::t('app', 'Description'),
            'phone' => Yii::t('app', 'Phone Number'),
            'exampleFile' => Yii::t('app', 'Add Multiple Images'),
            
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;

        } else {
            $this->updated_at = time();
            $this->updated_by = Yii::$app->user->identity->id;

        }

        return parent::beforeSave($insert);
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
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_BUSINESS,$this->image);

        
    }

    public function getExpampleImages()
    {
        return $this->hasMany(BusinessExampleImage::className(), ['business_id' => 'id']);

    }

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'business_category_id']);

    }

    public function getCity()
    {
        return City::find()->all();

    }
   



    

}
