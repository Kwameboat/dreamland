<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\Package;
use api\modules\v1\models\Ad;
use api\modules\v1\models\User;

class Payment extends \yii\db\ActiveRecord
{
    
    const  TYPE_PRICE                        =1;
    const  TYPE_COIN                         =2;

    const  TRANSACTION_TYPE_CREDIT  =1;
    const  TRANSACTION_TYPE_DEBIT   =2;

    const  PAYMENT_TYPE_PACKAGE             =1;
    const  PAYMENT_TYPE_AWARD               =2;
    const  PAYMENT_TYPE_WITHDRAWAL          =3;
    const  PAYMENT_TYPE_WITHDRAWAL_REFUND   =4;
    const  PAYMENT_TYPE_LIVE_TV_SUBSCRIBE   =5;
    const  PAYMENT_TYPE_GIFT                =6;
    const  PAYMENT_TYPE_REDEEM_COIN         =7;
    const  PAYMENT_TYPE_EVENT_TICKET         =8;
    const  PAYMENT_TYPE_EVENT_TICKET_REFUND  =9;
    const  PAYMENT_TYPE_DATING_SUBSCRIPTION  =10;
    const  PAYMENT_TYPE_PROMOTION            =11;
    const  PAYMENT_TYPE_PROMOTION_REFUND     =12;
  
    const  PAYMENT_TYPE_GIFT_ADMIN_COMMISSION =13;
    const  PAYMENT_TYPE_STREAMING_AWARD      =14;
    const  PAYMENT_TYPE_CAMPAIGN             =15;
    const  PAYMENT_TYPE_FEATURE_AD          =16;
    const  PAYMENT_TYPE_BANNER_AD           =17;
    const  PAYMENT_TYPE_COIN_TRANSFER       =18;
    const  PAYMENT_TYPE_ADMIN_UPDATE        =19;
    const  PAYMENT_TYPE_USER_SUBSCRIPTIOM        =20;
    

    const  PAYMENT_MODE_IN_APP_PURCHASE      =1;
    const  PAYMENT_MODE_PAYPAL               =2;
    const  PAYMENT_MODE_WALLET               =3;
    const  PAYMENT_MODE_STRIPE               =4;
    const  PAYMENT_MODE_RAZORPAY             =5;
    const  PAYMENT_MODE_DATING_SUBSCRIPTION_PURCHASE      =6;
    const  PAYMENT_MODE_PACKAGE_COUPON       =7;
    const  PAYMENT_MODE_DIRECT_ASSIGN_BY_ADMIN =8;
    const  PAYMENT_MODE_FLUTTERWAVE         =9;
    const  PAYMENT_MODE_CASH                =10;

    const  PAYMENT_MODE_GPAY                =11;

    
    const  PROCESSED_STATUS_COMPLETED        = 10;

    public $package_banner_id;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','type','coin','user_id','package_id','transaction_type','payment_type','payment_mode','live_tv_id','gift_history_id','event_ticket_booking_id','created_at','dating_subscription_id','campaign_id','ad_package_id','ad_id','detail_reference_id'], 'integer'],
            [['amount'], 'number'],
            [['transaction_id'], 'string'],
            [['package_id','amount','transaction_id'], 'required','on'=>'packageSubscription'],
            [['package_id'], 'checkPackage','on'=>'packageSubscription'],
            [['dating_subscription_id'], 'required','on'=>'datingSubscription'],
            [['ad_package_id','amount','transaction_id'], 'required','on'=>'packageAdSubscription'],
            [['ad_id','amount','transaction_id','package_banner_id'], 'required','on'=>'bannerAdPayment'],
            [['ad_id','amount','transaction_id'], 'required','on'=>'featureAdPayment'],

            // [['campaign_id','amount'], 'required','on'=>'campaignPayment'],

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
            'package_id' => Yii::t('app', 'Package'),
            'transaction_type' => Yii::t('app', 'Transaction Type'),
            'payment_type' => Yii::t('app', 'Payment Type'),
            'payment_mode' => Yii::t('app', 'Payment Mode'),
            'created_at'=> Yii::t('app', 'Created At'),
            'campaign_id' => Yii::t('app', 'Campaign id'),
            
        ];
    }

    public function extraFields()
    {
        return ['userDetail','campaignDetails'];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id =   ($this->user_id) ? $this->user_id : Yii::$app->user->identity->id;
          
        }

        
        return parent::beforeSave($insert);
    }

    

    public function checkPackage($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
           
            $count= Package::find()->where(['id'=>$this->$attribute])->count();
            if($count <= 0){
                $this->addError($attribute, 'Invalid Package');     
            }
            
        }
       
    }


    public function checkAd($attribute, $params, $validator)
    {
        if(!$this->hasErrors()){
           
            $count= Post::find()->where(['id'=>$this->$attribute])->count();
            if($count <= 0){
                $this->addError($attribute, 'Invalid Post Ad');     
            }
            
        }
       
    }

    public function getUserDetail()
    {
        
     return $this->hasOne(User::className(), ['id'=>'user_id'])
           ->select(['user.id', 'user.username', 'user.email','user.bio','user.country_code','user.phone','user.country','user.sex','user.dob']);
    }

    public function getCampaignDetails()
    {
        return $this->hasOne(Campaign::className(), ['id'=>'campaign_id']);
    }

}
