<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;


class DatingUserSubscription extends ActiveRecord
{
    
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

  
    const IS_DEFAULT_YES=1;
    const IS_DEFAULT_NO=0;
    
    const DATING_SUBSCRIPTION_ONE_WEEK=1;
    const DATING_SUBSCRIPTION_ONE_MONTH=2;
    const DATING_SUBSCRIPTION_THREE_MONTH=3;
    const DATING_SUBSCRIPTION_SIX_MONTH=4;
    const DATING_SUBSCRIPTION_ONE_YEAR=5;
    

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dating_user_subscription';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','dating_subscription_id', 'user_id','start_date','expiry_date','status', 'created_at'], 'integer'],
        ];
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

        // remove fields that contain sensitive information
        unset($fields['created_at'], $fields['created_by'], $fields['updated_at'], $fields['updated_by']);
      
        return $fields;
    }

    
    public function getProfileLimitBySubscriptionPackage($userId)
    {

        $userSubscriptionResult = $this->find()->where(['status'=>DatingUserSubscription::STATUS_ACTIVE , 'user_id'=>$userId])->orderBy(['id'=>SORT_DESC])->one();
        if(!empty($userSubscriptionResult)){
          $subscriptionId =  $userSubscriptionResult['dating_subscription_id'];
          $userSubscriptionEndDate = $userSubscriptionResult['expiry_date'];
          $currentDate = strtotime("now");
          if($userSubscriptionEndDate >= $currentDate){
           $subscriptionDetail =  DatingSubscriptionPackage::find()->where(['id'=>$subscriptionId])->one();
           if(!empty($subscriptionDetail)){
             return $numberOfProfiles = $subscriptionDetail['number_of_profiles'];
           }
          }
        }
    }

    

    /**
     * RELEATION START
     */
    
    
}
