<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\AdPackage;

class AdSubscription extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 10;
    const STATUS_EXPIRED = 9;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ad_subscription';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','ad_package_id','title','term','ad_limit','ad_remaining','status','created_at','payment_mode','expiry_date'], 'integer'],
            [['amount'], 'number']
         
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
            'ad_package_id' => Yii::t('app', 'Package'),
            'title' => Yii::t('app', 'Title'),
            'ad_limit' => Yii::t('app', 'Ad Limit'),
            'ad_remaining' => Yii::t('app', 'Ad Remaining'),
            'status' => Yii::t('app', 'Status'),
            'payment_mode' => Yii::t('app', 'Payment Mode'),
            'created_at'=> Yii::t('app', 'Created At'),
            'expiry_date'=> Yii::t('app', 'Expiry Date'),
            
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
         
          
        }

        
        return parent::beforeSave($insert);
    }

    

    public function checkPackage($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
           
            $count= AdPackage::find()->where(['id'=>$this->$attribute])->count();
            if($count <= 0){
                $this->addError($attribute, 'Invalid Package');     
            }
            
        }
       
    }


    public function getExpirtyDate($term)
    {
        if($term==1){

            $expriyDate = strtotime("+1 week", time());
        }elseif($term==2){
            $expriyDate = strtotime("+1 month", time());
        }elseif($term==3){

            $expriyDate = strtotime("+1 year", time());
        }
        return $expriyDate;
    }

  

    public function getCurrentSubscription($userId)
    {
        
        return $this::find()->where(['user_id'=>$userId,'status'=>AdSubscription::STATUS_ACTIVE])->andWhere(['>','expiry_date',time()])->one();
        
       
    }


    public function getPackage()
    {
        return $this->hasOne(AdPackage::className(), ['id'=>'ad_package_id']);
    }



    



}
