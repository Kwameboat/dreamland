<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\PromotionalAdCategory;
use common\models\Country;


class PromotionalAd extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const AD_TYPE_IMAGE=1;
    const AD_TYPE_VIDEO=2;
   
    public $imageFile;
    public $videoFile;
    public $category_id;


    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'promotional_ad';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','ad_type','country_id','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['name','image','video'], 'string', 'max' => 250],
            [['imageFile','ad_type','name','status','start_date','end_date','category_id','country_id'], 'required','on'=>'create'],
            [['ad_type','name','status','start_date','end_date','category_id','country_id'], 'required','on'=>'update'],

            [['end_date'], 'checkEndDate', 'on' => ['create','update']],
            
            [['imageFile'], 'file', 'skipOnEmpty' => true],
            [['videoFile'], 'file', 'skipOnEmpty' => true,'extensions' => 'mp4','maxSize' => '9048000'],
            ['videoFile', 'required', 'when' => function ($model) {
                return $model->ad_type == '2';
            }, 'whenClient' => "function (attribute, value) {
                return $('#promotionalad-ad_type').val() == '2';
            }",'on'=>'create'],
            

            [['start_date','end_date'], 'safe']

            
            
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
            'image' => Yii::t('app', 'Image'),
            'video' => Yii::t('app', 'Video'),
            'category_id' => Yii::t('app', 'Category'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'country_id' => Yii::t('app', 'Country'),
            'ad_type' => Yii::t('app', 'Ad Type'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
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
    
    public function checkEndDate($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
            if($this->start_date > $this->end_date ){
                $this->addError($attribute, Yii::t('app','End date must be greater than start date'));  
            }
        
            
        }
       
    }


    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getAdType()
    {
       if($this->ad_type==$this::AD_TYPE_IMAGE){
           return 'Image';
       }else if($this->status==$this::AD_TYPE_VIDEO){
           return 'Video';    
       }
    }
    public function getAdTypeDropDownData()
    {
        return array(self::AD_TYPE_IMAGE => 'Image', self::AD_TYPE_VIDEO => 'Video');
    }
    
    public function getImageUrl(){
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD,$this->image);
        
    }

    public function getVideoUrl(){
         
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD,$this->video);
    }

    
    /*public function getAllPromotionalAd()
    {
        return $this->find()
        ->where(['status'=>$this::STATUS_ACTIVE])
        ->all();


    }*/

    public function getActiveDate()
    {
        return date('Y-m-d',$this->start_date).' - '. date('Y-m-d',$this->end_date);

    }
    public function getDisplayStatus()
    {
        
        if($this->status==$this::STATUS_INACTIVE){
            return 'Inactive';
        }else if($this->status==$this::STATUS_ACTIVE){
            $currentTime=time();
            if($currentTime > $this->start_date && $currentTime < $this->end_date )
            {
                return 'Active';
            }else{
                return 'Inactive';
            }

        }
        //return date('yy-m-d',$this->start_date).' - '. date('yy-m-d',$this->end_date);

    }

    // get all active promotion  ads 
    public function getActivePromotionalAd($countryId,$categoryId){
        $currentTime=time();
        $query = $this->find()->where(['status'=>$this::STATUS_ACTIVE]);
        $query->joinWith(['promotionalAdCategory']);
        $query->andWhere(['<','start_date',$currentTime]);
        $query->andWhere(['>','end_date',$currentTime]);
        if($countryId>0){
            $query->andWhere(['country_id'=>$countryId]);
        }
        if($categoryId>0){
            $query->andWhere(['promotional_ad_category.category_id'=>$categoryId]);
        }
        return $query->all();
    }


    public function getPromotionalAdCategory()
    {
        return $this->hasMany(PromotionalAdCategory::className(), ['promotional_ad_id'=>'id']);
    }


    public function getCountry()
    {
        return $this->hasOne(Country::className(), ['id'=>'country_id']);
    }

    

    

}
