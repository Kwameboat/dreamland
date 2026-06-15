<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;
use api\modules\v1\models\SubscriptionPlan;


class SubscriptionPlanUser extends ActiveRecord
{
    


    public $subscription_plan;

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscription_plan_user';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','subscription_plan_id','user_id','type','created_at'], 'integer'],
            [['value'], 'number'],
            [['subscription_plan'], 'safe'],
            [[ 'subscription_plan' ], 'required','on'=>'addUpdate'],
            
            
       
        ];
    }
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id       =   Yii::$app->user->identity->id;
          
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

        $fields['subscriptionPlanName'] = (function ($model) {
            return @$model->subscriptionPlan->name;
        });
        return $fields;
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
    public function getSubscriptionPlan()
    {
        return $this->hasOne(SubscriptionPlan::className(), ['id' => 'subscription_plan_id']);

    }
}
