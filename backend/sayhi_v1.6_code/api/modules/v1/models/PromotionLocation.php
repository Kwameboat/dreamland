<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\AudienceKeyword;

class PromotionLocation extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;



    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'promotion_location';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [

            [['id', 'audience_id', 'location_id', 'status', 'created_at'], 'integer'],
            [['fullname','type'], 'string', 'max' => '256'],
            // [['name'], 'required', 'on' => 'create'],
            // [['name'], 'required', 'on' => 'update'],
            // [['keywords','interest'],'safe']
           
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'audience_id' => Yii::t('app', 'Audience'),
            'location_id' => Yii::t('app', 'Location Id'),
            'fullname' => Yii::t('app', 'Full Name'),
            'type' => Yii::t('app', 'type'),
            'status' => Yii::t('app', 'Status'),            
            'created_at'=> Yii::t('app', 'Created At'),
            
        ];
    }
   
    // public function beforeSave($insert)
    // {
    //     if ($insert) {
    //         $this->created_at = time();
    //         $this->created_by =   Yii::$app->user->identity->id;
    //         $this->user_id =   Yii::$app->user->identity->id;
          
    //     }else{
    //         $this->updated_at = time();
    //         $this->updated_by =   Yii::$app->user->identity->id;
          
            
    //     }

        
    //     return parent::beforeSave($insert);
    // }
    
    public function updatePromotionLocation($audienceId,$recordsKeyword,$type){
        $records=[];
        if($recordsKeyword){
            $records = explode(',',$recordsKeyword);
        }
       
        $values=[];
        foreach($records as $keyword){
            $location_id = trim($keyword);
            
       
            $dataInner['audience_id']          = $audienceId;
            $dataInner['location_id']          =  $location_id;
            if($type=='country'){

                $coutryName = $this->getCountry($location_id);            
                $dataInner['fullname']        =  $coutryName['name'];
                $dataInner['type']        =  $type;

            }if($type=='state'){

                $state = $this->getState($location_id);
                $dataInner['fullname']        =  $state;
                $dataInner['type']        =  $type;

            }if($type=='city'){

                $city = $this->getCity($location_id);
                $dataInner['fullname']        =  $city;
                $dataInner['type']        =  $type;

            }
            
            
            $dataInner['created_at']        =  time();
            $values[]=$dataInner;
        }
        
        $model =  new PromotionLocation();
        if($type=='country'){
            $model->deleteAll(['audience_id'=>$audienceId ,'type'=>'country']);
        }
        if($type=='state'){
            $model->deleteAll(['audience_id'=>$audienceId ,'type'=>'state']);
        }
        if($type=='city'){
            $model->deleteAll(['audience_id'=>$audienceId ,'type'=>'city']);
        }
       
        if(count($values)>0){

            Yii::$app->db
            ->createCommand()
            ->batchInsert('promotion_location', ['audience_id','location_id','fullname','type','created_at'],$values)
            ->execute();
        }
    }
    
    public function getCountry($countryID){
        return Country::find()->select('name')->where(['id'=>$countryID])->one();
    }

    public function getState($stateID){
        $state = State::find()->select(['name','country_id'])->where(['id'=>$stateID])->one();
        if($state){
            $countryId = $state['country_id'];
            $stateName = $state['name'];
            $coutry = $this->getCountry($countryId);
            $coutryName = $coutry['name'];
            return $stateName. ', '.$coutryName;
        }
        
    }

    public function getCity($cityID){
        $city = City::find()->select(['name','country_id'])->where(['id'=>$cityID])->one();
        if($city){
            $countryId = @$city['country_id'];
            $cityName = @$city['name'];
            $coutry = $this->getCountry($countryId);
            $coutryName = @$coutry['name'];
            return $cityName. ', '.$coutryName;

        }
        
    }
}
