<?php
namespace api\modules\v1\models;
use Yii;

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
            [['user_id'], 'required'],
            [['user_id', 'country_id', 'state_id', 'city_id', 'created_at'], 'integer'],
            [['country_name', 'state_name', 'city_name', 'custom_location'], 'string', 'max' => 256],
            [['latitude', 'longitude'], 'string', 'max' => 100],
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
            'country_id' => Yii::t('app','Country ID'),
            'state_id' => Yii::t('app','State ID'),
            'city_id' => Yii::t('app','City ID'),
            'country_name' => Yii::t('app','Country Name'),
            'state_name' => Yii::t('app','State Name'),
            'city_name' => Yii::t('app','City Name'),
            'custom_location' => Yii::t('app','Custom Location'),
            'latitude' => Yii::t('app','Latitude'),
            'longitude' => Yii::t('app','Longitude'),
            'created_at' => Yii::t('app','Created At'),
        ];
    }

    public function updateUserLocation($userId,$locations,$type=UserLocation::TYPE_USER,$adId=null){
        //print_r($locations);
        $values=[];
        
        foreach($locations as $location){
          //  print_r($location);
            $locationValue['type']               =   $type;
            $locationValue['user_id']           =   $userId;
            $locationValue['ad_id']           =   $adId;
            $locationValue['country_id']        =   $location['country_id'];
            $locationValue['country_name']      =   $location['country_name'];
            $locationValue['state_id']          =   $location['state_id'];
            $locationValue['state_name']        =   $location['state_name'];
            $locationValue['city_id']           =   $location['city_id'];
            $locationValue['city_name']         =   $location['city_name'];
            $locationValue['latitude']         =   $location['latitude'];
            $locationValue['longitude']         =   $location['longitude'];
            $locationValue['custom_location']   =   $location['custom_location'];
            $locationValue['created_at']        =   time();

            $values[]=$locationValue;

        }   

        if(count($values)>0){

            if($type==UserLocation::TYPE_USER){
                $this->updateAll(['status'=>UserLocation::STATUS_DELETED],['user_id'=>$userId,'type'=>UserLocation::TYPE_USER]);
            }elseif($type==UserLocation::TYPE_AD){
                $this->updateAll(['status'=>UserLocation::STATUS_DELETED],['ad_id'=>$adId,'type'=>UserLocation::TYPE_AD]);
            }
         

            Yii::$app->db
            ->createCommand()
            ->batchInsert('user_location', ['type','user_id','ad_id','country_id','country_name','state_id','state_name','city_id','city_name','latitude','longitude','custom_location','created_at'],$values)
            ->execute();
        }
    }


    





}
