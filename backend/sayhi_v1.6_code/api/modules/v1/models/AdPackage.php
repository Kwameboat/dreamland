<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;
use api\modules\v1\models\PromotionalBanner;



class AdPackage extends ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const TYPE_ORDINARY=1;
    const TYPE_BANNER=2;


    const IS_DEFAULT_YES=1;
    const IS_DEFAULT_NO=0;
    

    

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ad_package';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'promotional_banner_id', 'term', 'ad_limit', 'ad_duration', 'featured_duration', 'is_default', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['featured_fee','price'], 'number'],
            [['name'], 'string', 'max' => 256]
       
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'type' => Yii::t('app','Package Type'),
            'promotional_banner_id' => Yii::t('app','Promotional Banner'),
            'name' => Yii::t('app','Name'),
            'term' => Yii::t('app','Term'),
            'ad_limit' => Yii::t('app','Ad Posting Limit(Max No)'),
            'ad_duration' => Yii::t('app','Ad Duration'),
            'featured_fee' => Yii::t('app','Featured Fee'),
            'featured_duration' => Yii::t('app','Featured Duration'),
            'is_default' => Yii::t('app','Is Default'),
            'status' => Yii::t('app','Status'),
            'created_at' => Yii::t('app','Created At'),
            'created_by' => Yii::t('app','Created By'),
            'updated_at' => Yii::t('app','Updated At'),
            'updated_by' => Yii::t('app','Updated By')
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['created_at'], $fields['created_by'], $fields['updated_at'], $fields['updated_by']);
       $fields[] = 'promotionalBanner';
     
        return $fields;
    }
    /*
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }*/


    

    public function getTermString()
    {
        if($this->term==1){
           return 'Weekly';
        }else if($this->term==2){
        return 'Monthly';
        }else if($this->term==3){
           return 'Yearly';    
       }
    }

    public function getTypeString()
    {
        
       
        if($this->type==self::TYPE_ORDINARY){
           return 'Ordinary Package';
        }else if($this->type==self::TYPE_BANNER){
            return 'Promotional Banner Package';
        }
    }

    public function getPackageTypeData()
    {
        
        return array(self::TYPE_ORDINARY => 'Ordinary Package', self::TYPE_BANNER => 'Promotional Banner Package');
    }

    public function getDefaultPackage()
    {
        
        return  $this->find()->where(['is_default'=>AdPackage::IS_DEFAULT_YES,'status'=>AdPackage::STATUS_ACTIVE])->one();
       

    }

    public function getBannerPackage()
    {
        return  $this->find()->where(['type'=>AdPackage::TYPE_BANNER,'status'=>AdPackage::STATUS_ACTIVE])->all();
    }

    public function getOrdinaryPackage()
    {
        return  $this->find()->where(['type'=>AdPackage::TYPE_ORDINARY,'status'=>AdPackage::STATUS_ACTIVE])->all();
    }

    

    /**
     * RELEATION START
     */
    public function getPromotionalBanner()
    {
        $result  =  $this->hasOne(PromotionalBanner::className(), ['id'=>'promotional_banner_id'])->select(['id','name']);
        if(is_null($result)){
            $result=[];
        }
        return $result;
        
    }

    
}
