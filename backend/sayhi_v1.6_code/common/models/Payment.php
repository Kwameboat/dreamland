<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;

//use api\modules\v1\models\Package;
//use api\modules\v1\models\Ad;
use common\models\User;
use common\models\Package;

class Payment extends \yii\db\ActiveRecord
{
    
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

    
    const  TYPE_PRICE                        =1;
    const  TYPE_COIN                        =2;


    
    const STATUS_COMPLETED = 10;
    const STATUS_FAILD = 0;
    const STATUS_REFUND = 9;
    const STATUS_PENDING = 8;
   
    

    
   
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
            [['id','type','user_id','package_id','transaction_type','payment_type','payment_mode','created_at','live_tv_id', 'gift_history_id', 'event_ticket_booking_id', 'dating_subscription_id', 'post_promotion_id', 'transaction_type', 'payment_type', 'payment_mode',  'amount', 'coin','status','reference_id'], 'integer'],
            [['amount'], 'number'],
            [['transaction_id','remarks'], 'string'],
          //  [['package_id','amount','transaction_id'], 'required','on'=>'packageSubscription'],
          //  [['package_id'], 'checkPackage','on'=>'packageSubscription'],

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
            'live_tv_id'=> Yii::t('app', 'Live Tv'),
            'gift_history_id'=> Yii::t('app', 'Gift history'),
            'event_ticket_booking_id'=> Yii::t('app', 'Event ticket booking'),
            'dating_subscription_id'=> Yii::t('app', 'Dating subscription'),
            'post_promotion_id'=> Yii::t('app', 'Post promotion'),
            'transaction_id'=> Yii::t('app', 'transaction_id'),
            'amount'=> Yii::t('app', 'Amount'),
            'coin'=> Yii::t('app', 'Coin'),
            'status'=> Yii::t('app', 'Status'),
            'remarks'=> Yii::t('app', 'Remarks'),
            'reference_id'=> Yii::t('app', 'Reference id'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
           // $this->user_id =   Yii::$app->user->identity->id;
          
        }

        
        return parent::beforeSave($insert);
    }


    public function getLastTweleveMonth()
    {
        $month =  strtotime("+1 month");
        for ($i = 1; $i <= 12; $i++) {
            $months[(int)date("m", $month)] = date("M", $month);
            $month = strtotime('+1 month', $month);
        }
        return $months;
        
    }

    public function getLastTweleveMonthPayments()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, sum(amount) as total FROM payment where transaction_type=1 and from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();
        foreach($months as $key => $month){
            $found_key = array_search($key, array_column($res, 'month'));  
            $totalAd =0;
            if(is_int($found_key)){
                if($res[$found_key]['total']){
                    $totalAd =   round($res[$found_key]['total']);
                }
            }
            $totalAds[]=$totalAd;
            $monthArr[]=$month;

        }
        $output=[];

        $output['data'] = $totalAds;
        $output['dataCaption'] = $monthArr;
        return $output;

        
    }

    public function getTotalEarning()
    {
        
           
        return Payment::find()->where(['transaction_type'=>self::TRANSACTION_TYPE_CREDIT])->sum('amount');
        
    }
    public function getTotalEarningLastMonth()
    {
        $currentTime = time();
        $endTime =  strtotime('-30 day', $currentTime);
           
        return Payment::find()->where(['transaction_type'=>self::TRANSACTION_TYPE_CREDIT])->andWhere(['>','created_at',$endTime])->sum('amount');
        
    }

    
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }


    public function getPaymentTypeString()
    {
       if($this->payment_type==Payment::PAYMENT_TYPE_PACKAGE){
           return 'Package Subscription';
       }else if($this->payment_type==Payment::PAYMENT_TYPE_FEATURE_AD){
           return 'Feature Ad';    
       }else if($this->payment_type==Payment::PAYMENT_TYPE_BANNER_AD){
        return 'Banner Ad';    
      }else if($this->payment_type==Payment::PAYMENT_TYPE_REFUND){
        return 'Refund';    
      }
    }

    public function getPaymentModeString()
    {
       if($this->payment_mode==Payment::PAYMENT_MODE_IN_APP_PURCHASE){
           return 'Inapp Purchase';
       }else if($this->payment_mode==Payment::PAYMENT_MODE_PAYPAL){
           return 'Paypal';    
       }else if($this->payment_mode==Payment::PAYMENT_MODE_WALLET){
            return 'Wallet';    
        }else if($this->payment_mode==Payment::PAYMENT_MODE_STRIPE){
            return 'Stripe';    
        }else if($this->payment_mode==Payment::PAYMENT_MODE_RAZORPAY){
            return 'Rozarpay';    
        }else if($this->payment_mode==Payment::PAYMENT_MODE_PACKAGE_COUPON){
            return 'Coupon';    
        }else if($this->payment_mode==Payment::PAYMENT_MODE_DIRECT_ASSIGN_BY_ADMIN){
            return 'Admin';    
        }else if($this->payment_mode==Payment::PAYMENT_MODE_FLUTTERWAVE){
            return 'FlutterWave';    
        }
    }

    public function getTransactionTypeString()
    {
        if($this->transaction_type==$this::TRANSACTION_TYPE_CREDIT){
            return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Credit').'</button>';      
            
        }else{
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Debit').'</button>'; 
        }
    }
    public function getTypeString()
    {
        
        
    
        
        if($this->payment_type==$this::PAYMENT_TYPE_GIFT_ADMIN_COMMISSION){
            return 'Gift Commission'; 
        }else if($this->payment_type==$this::PAYMENT_TYPE_STREAMING_AWARD){
            return 'Streaming Award'; 
        }
    }

    
    public function getStatusString()
    {
        if($this->status==$this::STATUS_FAILD){
            return'<button type="button" class="btn btn-sm pending_btn">'.Yii::t('app','Faild').'</button>'; 
        }else if($this->status==$this::STATUS_PENDING){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Pending').'</button>'; 
        }else if($this->status==$this::STATUS_COMPLETED){
            return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Completed').'</button>';      
        }else if($this->status==$this::STATUS_REFUND){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Refund').'</button>'; 
        }
    }

    public function getPackageName()
    {
        return $this->hasOne(Package::className(), ['id'=>'package_id']);
        
    }

    public function getType()
    {
       if($this->type==Payment::TYPE_PRICE){
           return 'Price';
       }else if($this->type==Payment::TYPE_COIN){
           return 'Coin';    
       }
    }

}
