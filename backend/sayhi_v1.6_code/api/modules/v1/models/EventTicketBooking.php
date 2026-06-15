<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\EventTicket;
use api\modules\v1\models\EventEvent;
use api\modules\v1\models\Payment;
use api\modules\v1\models\User;

class EventTicketBooking extends \yii\db\ActiveRecord
{
    const STATUS_PURCHASED = 1;
    const STATUS_CANCELLED = 9;
    const STATUS_COMPLETED = 10;

    public $payments;
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
             
            [['id','event_id','event_ticket_id','user_id','gifted_to','ticket_qty','created_at'], 'integer'],
            [['coupon','image','user_first_name','user_last_name'], 'string'],
            [['coupon_discount_value','ticket_amount','paid_amount'], 'number'],
            [['event_id','event_ticket_id','ticket_qty','ticket_amount','paid_amount'], 'required','on'=>'buyTicket'],
            [['payments'], 'safe'],
            [['id'], 'required','on'=>'cancelBooking'],
            [['id','image'], 'required','on'=>'attachImage'],
            
            [['id','gifted_to'], 'required','on'=>'giftTicket'],

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

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            
          
        }

        
        return parent::beforeSave($insert);
    }

   
    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'ticketDetail';
        $fields[] = "viewTicketUrl";
        $fields[] = "imageUrl";
        return $fields;
    }
    public function extraFields()
    {
        return ['event','payment','giftedToUser','giftedByUser'];
    }
    public function getViewTicketUrl()
    {
        return Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl.'/backend/web/index.php?r=site/ticket-view&id='.$this->id;
    }


    public function getTicketDetail()
    {
        return $this->hasOne(EventTicket::className(), ['id' => 'event_ticket_id']);

    }
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);

    }

    public function getGiftedToUser()
    {
        if($this->gifted_to){
            return $this->hasOne(User::className(), ['id' => 'gifted_to']);
        }

    }
    public function getGiftedByUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getPayment()
    {
        return $this->hasMany(Payment::className(), ['event_ticket_booking_id' => 'id']);

    }
    public function getImageUrl()
    {
        if($this->image){
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_EVENT ,$this->image);
           
        }else{
            return '';
        }
        
    }
   
    

}
