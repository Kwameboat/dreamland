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
class Coupon extends \yii\db\ActiveRecord
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
        return 'coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'business_id'], 'required'],
            
            [['status', 'id','business_id','status','created_at','created_by','updated_at','updated_by','total_comment'], 'integer'],
            [['name','code','description','website_url'], 'string'],
            
            [['name','business_id'], 'required','on'=>['create','update']],
            
            [['imageFile','start_date','expiry_date'], 'safe'],
            [['imageFile'], 'file', 'skipOnEmpty' => true],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Coupon Name'),
            'status' => Yii::t('app', 'Status'),
            'business_id' => Yii::t('app', 'Business'),
            'start_date' => Yii::t('app', 'Start Date'),
            'expiry_date' => Yii::t('app', 'Expiry Date'),
            'code' => Yii::t('app', 'Coupon Code'),
            'description' => Yii::t('app', 'Description'),
            'total_comment' => Yii::t('app', 'Total Comments'),
            'website_url' => Yii::t('app', 'Website Url'),
            'imageFile' => Yii::t('app', 'File/Image'),
            'image' => Yii::t('app', 'File/Image'),
            
            
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
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_COUPON,$this->image);

        
    }

    
    public function getBusiness()
    {
        return $this->hasOne(Business::className(), ['id' => 'business_id']);

    }
    
    public function getTotalCouponCount()
    {
        return Coupon::find()->where(['<>','status',self::STATUS_DELETED])->count();
    }
}
