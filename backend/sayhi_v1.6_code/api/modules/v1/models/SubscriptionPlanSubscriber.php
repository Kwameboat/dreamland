<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;
use api\modules\v1\models\User;
use api\modules\v1\models\SubscriptionPlanUser;


class SubscriptionPlanSubscriber extends ActiveRecord
{
    


    public $subscription_plan_subscriber;

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscription_plan_subscriber';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','type','post_id','subscription_plan_user_id','subscribe_to_user_id','subcriber_id','expiry_time','created_at','updated_at'], 'integer'],
            [['subscription_plan_value'], 'number'],
           // [['subscription_plan'], 'safe'],
            [[ 'subscription_plan_user_id' ], 'required','on'=>'subscribe'],
            
            
       
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->subcriber_id       =   Yii::$app->user->identity->id;
          
        }else{
            $this->updated_at = time();
        }
        return parent::beforeSave($insert);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['subscriptionPlanUser'] = (function ($model) {
            return @$model->subscriptionPlanUser;
        });
        return $fields;
    }
    public function extraFields()
    {
        return ['subscribedPlanStatus','subscriberDetail','subscriptionUserDetail'];
    }
    public function addUpdate($plans){
        $userId     =     Yii::$app->user->identity->id;
        foreach($plans as $plan){
            $subscriptionPlanId =  $plan['subscription_plan_id'];
            $value = $plan['value'];

            $modelSubscriptionPlanUser = $this->find()->where(['subscription_plan_id'=>$subscriptionPlanId,'user_id'=>$userId])->one();
            if(!$modelSubscriptionPlanUser){
                $modelSubscriptionPlanUser = new SubscriptionPlanUser();
                $modelSubscriptionPlanUser->subscription_plan_id = $subscriptionPlanId;
            }
            $modelSubscriptionPlanUser->value = $value;
            if($value){
                $modelSubscriptionPlanUser->save();
            }
            
        }
    }
    public function getSubscribedPlanStatus()
    {
        if($this->expiry_time > time()){//active
            $subscribeStatus=1;    
        }else{ // expired
           $subscribeStatus=2;
        }           
       
        return $subscribeStatus;

    }
    
    /*public function getSubscriptionPlan()
    {
        return $this->hasOne(SubscriptionPlan::className(), ['id' => 'subscription_plan_id']);

    }*/
    public function getSubscriptionPlanUser()
    {
        return $this->hasOne(SubscriptionPlanUser::className(), ['id' => 'subscription_plan_user_id']);

    }
    public function getSubscriberDetail()
    {
        return $this->hasOne(User::className(), ['id' => 'subcriber_id']);

    }
    public function getSubscriptionUserDetail()
    {
        return $this->hasOne(User::className(), ['id' => 'subscribe_to_user_id']);

    }
    
    
    
}
