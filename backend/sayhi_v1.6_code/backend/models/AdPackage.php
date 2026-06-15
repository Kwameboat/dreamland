<?php
namespace backend\models;
use Yii;
use common\models\PromotionalBanner;

class AdPackage extends \yii\db\ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const TYPE_ORDINARY=1;
    const TYPE_BANNER=2;

   
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
            [['type', 'promotional_banner_id', 'term', 'ad_limit', 'ad_duration', 'featured_duration', 'is_default','deal_duration', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['featured_fee','price','deal_fee'], 'number'],
            [['name','in_app_purchase_id_ios','in_app_purchase_id_android'], 'string', 'max' => 256],
            [['name','term', 'is_default', 'status','term','in_app_purchase_id_ios','in_app_purchase_id_android'], 'required'],
           /* ['promotional_banner_id', 'required', 'when' => function ($model) {
                return $model->type == '2';
            }, 'whenClient' => "function (attribute, value) {
                return $('input[type=radio]:checked'). val() == '2';
            }"]*/
        
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
    }


    public function getStatusString()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getIsDefaultString()
    {
       if($this->is_default==0){
           return 'No';
       }else if($this->is_default==1){
           return 'Yes';    
       }
    }

    

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

    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getIsDefaultDropDownData()
    {
        return array(0 => 'No', 1 => 'Yes');
    }
    public function getTermDropDownData()
    {
        return array(1 => 'Weekly', 2 => 'Monthly',3=>'Yearly');
    }

    public function getPackageTypeData()
    {
        
        return array(self::TYPE_ORDINARY => 'Ordinary Package', self::TYPE_BANNER => 'Promotional Banner Package');
    }

    
    public function getPromotionalBanner()
    {
        return $this->hasOne(PromotionalBanner::className(), ['id'=>'promotional_banner_id']);
        
    }

    
}
