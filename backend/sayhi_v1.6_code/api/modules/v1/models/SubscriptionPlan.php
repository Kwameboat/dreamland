<?php
namespace api\modules\v1\models;
use \yii\db\ActiveRecord;
use Yii;


class SubscriptionPlan extends ActiveRecord
{
    


    

   
  //  public $imageFile;

    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscription_plan';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','expiry_day'], 'integer'],
            [['name'], 'string', 'max' => 256]
       
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

      
        return $fields;
    }
    public function getExpiryTime($planId=null)
    {
        if($planId){
            $model =  new SubscriptionPlan();
            $result  = $model->findOne($planId);
            $totalDay = $result->expiry_day;
        }else{
            $totalDay = $this->expiry_day;
        }
       
        return  strtotime("+$totalDay days", time());
    
    }
    
    
    
}
