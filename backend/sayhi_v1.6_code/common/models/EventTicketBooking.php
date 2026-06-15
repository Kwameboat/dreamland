<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Event;
use common\models\Payment;
use common\models\EventTicket;
use app\models\User;


/**
 * This is the model class 
 *
 */
class EventTicketBooking extends \yii\db\ActiveRecord
{
    const STATUS_PURCHASED = 1;
    const STATUS_CANCELLED = 9;
    const STATUS_COMPLETED = 10;
    
    const BOOKING_USER_TYPE_REGISTERED = 1;
    const BOOKING_USER_TYPE_UNREGISTERED = 2;
    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_ticket_booking';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
          
            
            [['id','event_id','event_ticket_id','user_id','ticket_qty','is_check_in','created_at','booking_user_type'], 'integer'],
            [['coupon','notes','user_first_name','user_last_name','user_email','user_country_code','user_phone'], 'string'],
            [['coupon_discount_value','ticket_amount','paid_amount'], 'number'],
            
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => Yii::t('app', 'Event'),
            'status' => Yii::t('app', 'Status'),
            'user_id' => Yii::t('app', 'User'),
            'created_at' => Yii::t('app', 'Booked At'),
            'event_ticket_id' => Yii::t('app', 'Ticket'),
            'user_first_name' => Yii::t('app', 'User Name'),
            
            
            
            
            
        ];
    }
    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
        }else{
            $this->updated_at = time();
        }
        return parent::beforeSave($insert);
    }

    
    public function getStatus()
    {
       if($this->status==$this::STATUS_CANCELLED){
           return 'Cancelled';
       }else {
           return 'Booked';    
       }
    }
    public function getCheckInData()
    {
        return array(1 => 'Yes', 0 => 'No');
    }

    public function getStatusButton()
    {
        if($this->status==$this::STATUS_CANCELLED){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Cancelled').'</button>'; 
       
           
        }else {
            
            return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Booked').'</button>';     
        }
        //return'<button type="button" class="btn btn-sm pending_btn">'.Yii::t('app','Pending').'</button>'; 
        //return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Blocked').'</button>'; 
       
    }
  
    public function getTicket()
    {
        return $this->hasOne(EventTicket::className(), ['id' => 'event_ticket_id']);

    }
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);

    }
    public function getPayment()
    {
        return $this->hasMany(Payment::className(), ['event_ticket_booking_id' => 'id']);

    }
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }

    public function getTicketUserName()
    {  
        $userNameOnTicket =  $this->user_first_name.' '.$this->user_last_name;
        $userNameOnTicket= trim($userNameOnTicket);
        if(!$userNameOnTicket){
            $userNameOnTicket = '--';
        }
        return $userNameOnTicket;
       
    }

    public function getTicketDetail()
    {
        return $this->hasOne(EventTicket::className(), ['id' => 'event_ticket_id']);

    }

    public function getUserTicketEmail()
    {
        if($this->booking_user_type==$this::BOOKING_USER_TYPE_REGISTERED){
            return $this->user->email;
        }else{
            return $this->user_email;
        }
    }
    public function getUserTicketPhone()
    {
        if($this->booking_user_type==$this::BOOKING_USER_TYPE_REGISTERED){
            
            if($this->user){
                return $this->user->getPhone();
            }
            
        }else{
            if($this->user_phone){
                return $this->user_country_code.' '.$this->user_phone;
            }else{
                return '--';
            }
            
        }
    }

    

    
  
   



    

}
