<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\PromotionLocation;
use common\models\PromotionInterest;
use common\models\UserProfileCategory;

class Audience extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;

    
    
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
            [['name', 'status'], 'required'],
           [['user_id', 'id','gender','age_start_range','age_end_range','location_type','radius','profile_category_type','status','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['latitude','longitude','name'], 'string', 'max' => 100]
            
           

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
            'user_id' => Yii::t('app', 'User Id'),
            'gender' => Yii::t('app', 'Gender'),
            'age_start_range' => Yii::t('app', 'Age start range'),
            'age_end_range' => Yii::t('app', 'Age end range'),
            'location_type' => Yii::t('app', 'Location type'),
            'radius' => Yii::t('app', 'Radius'),
            'profile_category_type' => Yii::t('app', 'Profile category type'),
            'created_at' => Yii::t('app', 'Created at'),
            'created_by' => Yii::t('app', 'Created by'),
            'updated_at' => Yii::t('app', 'Updated at'),
            'updated_by' => Yii::t('app', 'Updated by'),
            'latitude' => Yii::t('app', 'Latitude'),
            'longitude' => Yii::t('app', 'Longitude'),

            
        ];
    }
   
   

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }
    
    public function getPromotionInterest(){
        return $this->hasMany(PromotionInterest::className(), ['audience_id' => 'id']);
       }

    public function getPromotionCountry(){
    return $this->hasMany(PromotionLocation::className(), ['audience_id' => 'id'])->andWhere(['type'=>'country']);
    }

    public function getPromotionState(){
        return $this->hasMany(PromotionLocation::className(), ['audience_id' => 'id'])->andWhere(['type'=>'state']);
    }

    public function getPromotionCity(){
        return $this->hasMany(PromotionLocation::className(), ['audience_id' => 'id'])->andWhere(['type'=>'city']);
    }

    public function getProfileCategory(){
        return $this->hasOne(UserProfileCategory::className(), ['id' => 'profile_category_type']);
    }

}
