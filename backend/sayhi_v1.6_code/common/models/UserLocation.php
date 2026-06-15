<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class UserLocation extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    const TYPE_USER = 1;
    const TYPE_AD = 2;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_location';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['country_id', 'state_id', 'city_id','custom_location'], 'required'],
            [['user_id', 'country_id', 'state_id', 'city_id', 'created_at'], 'integer'],
            [['country_name', 'state_name', 'city_name', 'custom_location'], 'string', 'max' => 256],
            [['latitude', 'longitude'], 'string', 'max' => 100],
            [['country_id', 'state_id', 'city_id','custom_location'], 'required', 'on' => 'create'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'user_id' => Yii::t('app','User ID'),
            'country_id' => Yii::t('app','Country'),
            'state_id' => Yii::t('app','State'),
            'city_id' => Yii::t('app','City'),
            'country_name' => Yii::t('app','Country Name'),
            'state_name' => Yii::t('app','State Name'),
            'city_name' => Yii::t('app','City Name'),
            'custom_location' => Yii::t('app','Location Name'),
            'latitude' => Yii::t('app','Latitude'),
            'longitude' => Yii::t('app','Longitude'),
            'created_at' => Yii::t('app','Created At'),
        ];
    }


    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
          
        }

        return parent::beforeSave($insert);
    }

    

}
