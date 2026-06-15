<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\AudienceKeyword;

class Audience extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    const LOCATION_TYPE_REGIONAL =1;
    public $keywords;
    public $interest;
    public $country_id;
    public $state_id;
    public $city_id;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'audience';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['id', 'user_id','status','age_start_range','age_end_range','radius', 'created_at', 'created_by', 'updated_at', 'updated_by','profile_category_type','location_type'], 'integer'],
            [['name','gender','latitude','longitude'], 'string', 'max' => '250'],
            [['name'], 'required', 'on' => 'create'],
            [['name'], 'required', 'on' => 'update'],
            [['keywords','interest','country_id','state_id','city_id'],'safe']
           
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'status' => Yii::t('app', 'Status'),
            'name' => Yii::t('app', 'Name'),
            'age_start_range' => Yii::t('app', 'Age start range'),
            'age_end_range' => Yii::t('app', 'Age end range'),
            'radius' => Yii::t('app', 'Radius'),
            'created_by' => Yii::t('app', 'Created by'),
            'updated_at' => Yii::t('app', 'Updated at'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'profile_category_type' => Yii::t('app', 'Profile Category Type'),
            'county_id' => Yii::t('app', 'County'),
            'state_id' => Yii::t('app', 'State'),
            'city_id' => Yii::t('app', 'City'),
            'location_type' => Yii::t('app', 'Location Type'),
            'latitude' => Yii::t('app', 'Latitude'),
            'Longitude' => Yii::t('app', 'Longitude'),
            'created_at'=> Yii::t('app', 'Created At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
            $this->user_id =   Yii::$app->user->identity->id;
          
        }else{
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;
          
            
        }

        
        return parent::beforeSave($insert);
    }


    public function fields()
    {
        $fields = parent::fields();

       
        return $fields;
    }
    public function extraFields()
    {
       
        return ['interestDetails','locationDetails'];
    }

    

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    
    public function getInterestDetails()
    {
        
        return $this->hasMany(PromotionInterest::className(), ['audience_id'=>'id']);
        
    }

    public function getPromotionInterest()
    {
        
        return $this->hasMany(PromotionInterest::className(), ['audience_id'=>'id']);
        
    }

    public function getLocationDetails()
    {
        
        return $this->hasMany(PromotionLocation::className(), ['audience_id'=>'id'])->select(['(location_id) as id','fullname','type']);
        
    }
    
    public function getPromotionLocation()
    {
        
        return $this->hasMany(PromotionLocation::className(), ['audience_id'=>'id']);
        
    }

}
