<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\PromotionalAdCategory;

class PromotionalAd extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const AD_TYPE_IMAGE=1;
    const AD_TYPE_VIDEO=2;
   
    
   
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
            [['status', 'id','ad_type','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['name','image','video'], 'string', 'max' => 250],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            [['videoFile'], 'file', 'skipOnEmpty' => true,'extensions' => 'mp4','maxSize' => '9048000'],
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

    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
     
       $fields['imageUrl'] = 'imageUrl';
       $fields['videoUrl'] = 'videoUrl';
       $fields['country_name'] = (function($model){
        return  @$model->country->name;
       });
       $fields['category'] = (function($model){
        return  @$model->categories;
       });
        return $fields;
    }
    
    public function extraFields()
    {
        return ['promotionalAdCategory'];
    }
    
    
    public function getImageUrl(){

        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD,$this->image);
        
    }

    
    
    
    public function getVideoUrl(){
        
  
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_PROMOTIONAL_AD,$this->video);
        
    }
    
  
    /**
     * RELEATION START
     */
    public function getPromotionalAdCategory()
    {
        return $this->hasMany(PromotionalAdCategory::className(), ['promotional_ad_id'=>'id']);
        
    }

    public function getCategories()
    {
        return $this->hasMany(Category::className(), ['id' => 'category_id'])
            ->viaTable('promotional_ad_category', ['promotional_ad_id' => 'id']);
    }

    public function getCountry(){

        return $this->hasOne(Country::className(), ['id' => 'country_id']);

    }

}
