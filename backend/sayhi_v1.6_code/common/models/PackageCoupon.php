<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\Category;
use backend\models\Package;
use common\models\User;

/**
 * This is the model class 
 *
 */
class PackageCoupon extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;
    const STATUS_EXPIRED=11;

    const COMMON_NO=0;
    const COMMON_YES=1;

    const IS_USED_YES=0;
    const IS_USED_NO=1;
    public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'package_coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['status', 'id','package_id','is_used','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['name','code','description'], 'string'],
            
            [['package_id','code'], 'required','on'=>['create','update']],
            [['code'],'string','min'=>6,'max'=>10],
            [['imageFile'], 'safe'],
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
            'name' => Yii::t('app', 'Package Coupon Name'),
            'status' => Yii::t('app', 'Status'),
            'package_id' => Yii::t('app', 'Package'),
            'created_at' => Yii::t('app', 'Created Date'),
            'created_by' => Yii::t('app', 'Created By'),
            'is_used' => Yii::t('app', 'Is used'),
            'description' => Yii::t('app', 'Description'),
            'code' => Yii::t('app', 'Code'),


            
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;
            $this->is_used = PackageCoupon::IS_USED_NO;
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
       }else if($this->status==$this::STATUS_EXPIRED){
        return 'Expired';    
    }
       
    }
  
    
    
    public function getImageUrl()
    {
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PACKAGE_COUPON,$this->image);

        
    }

    
    public function getPackage()
    {
        return $this->hasOne(Package::className(), ['id' => 'package_id']);

    }
    
    public function getTotalCouponCount()
    {
        return PackageCoupon::find()->where(['<>','status',self::STATUS_DELETED])->count();
    }

    public function getIsUsed()
    {
       if($this->is_used==$this::IS_USED_YES){
           return 'Yes';
       }else if($this->is_used==$this::IS_USED_NO){
           return 'No';    
       }
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'updated_by']);
        
    }
}
