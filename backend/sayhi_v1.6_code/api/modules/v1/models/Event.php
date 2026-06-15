<?php
namespace api\modules\v1\models;

use Yii;

use api\modules\v1\models\EventGallaryImage;
use api\modules\v1\models\EventTicket;
use api\modules\v1\models\EventTicketBooking;
use api\modules\v1\models\Organization;
use api\modules\v1\models\UserReaction;

class Event extends \yii\db\ActiveRecord
{
    const COMMON_NO = 0;
    const COMMON_YES = 1;
    
    
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DEACTIVE = 9;


    const CURRENT_STATUS_UPCOMING = 1;
    const CURRENT_STATUS_ACTIVE = 2;
    const CURRENT_STATUS_COMPLETED = 3;
    const CURRENT_STATUS_CANCELLED = 4; // ticket booking cancelled
    const EVENT_CREATED_SOURCE_ADMIN = 0;
    const EVENT_CREATED_SOURCE_USER = 1;
    
    public $imageFile;
    public $gallaryFile;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','category_id','organisor_id','is_paid','created_at', 'created_by', 'updated_at', 'updated_by','created_by_source'], 'integer'],
            [['name','description','image','start_date', 'end_date','place_name','address','latitude','longitude','disclaimer','description'], 'string'],
     
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            

            [['imageFile','gallaryFile'], 'safe'],
            [['name','category_id','start_date', 'end_date','is_paid'], 'required','on'=>['create']],
            [['id','status'], 'required','on'=>['updateStatus']],
            

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
            $this->created_by = Yii::$app->user->identity->id;
            $uniqueId = time().rand(1,99999).Yii::$app->user->identity->id;
            $this->unique_id       =  md5($uniqueId);

        } else {
            $this->updated_at = time();
            $this->updated_by = Yii::$app->user->identity->id;

        }

        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'imageUrl';
        $fields[] = 'eventCurrentStatus';
        $fields[] = 'totalMembers';
        $fields[] = 'totalReaction';
        $fields[] = 'isReact';
        
        $fields['eventGallaryImages'] = (function ($model) {
            $imageArr = [];
            foreach ($model->eventGallaryImages as $img) {
                $imageArr[] = $img->imageUrl;
            }
            return $imageArr;
        });
        $fields['is_ticket_booked'] = (function($model){
            return (@$model->isTicketBooked) ? 1: 0;
        });
        $fields['share_link'] = (function($model){
        
            return Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl.'/event/view/?id='.$model->unique_id;
        });

        //


       
       // $fields[] = 'competitionImage';
        return $fields;
    }

    public function extraFields()
    {
        return ['eventTicket','createdByUser','eventOrganisor','myEventTicketBooking'];
    }

    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_EVENT,$this->image);
    }

    public function getEventCurrentStatus()
    {
        
        $eventCurrentStatus=1;
        $currentTime = time();
        if($this->status ==  Event:: STATUS_DEACTIVE ){
            $eventCurrentStatus = Event:: STATUS_DEACTIVE;
        }else if($this->start_date > $currentTime){
            $eventCurrentStatus = Event::CURRENT_STATUS_UPCOMING;
        }else if($this->start_date < $currentTime && $this->end_date > $currentTime){
            $eventCurrentStatus = Event::CURRENT_STATUS_ACTIVE;
        }else if($this->end_date < $currentTime){
            $eventCurrentStatus = Event::CURRENT_STATUS_COMPLETED;
        }
        return $eventCurrentStatus;
    }

    

    

    /**
     * RELEATION START
     */
    
    public function getEventGallaryImages()
    {
        return $this->hasMany(EventGallaryImage::className(), ['event_id' => 'id']);

    }
    public function getEventTicket()
    {
        return $this->hasMany(EventTicket::className(), ['event_id' => 'id']);

    }
    public function getEventOrganisor()
    {
        
        return $this->hasOne(Organization::className(), ['id' => 'organisor_id']);

    }

    
    public function getCreatedByUser()
    {
        
        return $this->hasOne(User::className(), ['id' => 'created_by'])->select(['id','name', 'username', 'email', 'image', 'id','role', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);

    }

    



    public function getIsTicketBooked()
    {
        
        return $this->hasOne(EventTicketBooking::className(), ['event_id' => 'id'])->andOnCondition(['event_ticket_booking.user_id' => @Yii::$app->user->identity->id,'event_ticket_booking.status' => [EventTicketBooking::STATUS_PURCHASED,EventTicketBooking::STATUS_COMPLETED]]);
        
        
    }

    public function getEventTicketBooking()
    {
        return $this->hasMany(EventTicketBooking::className(), ['event_id' => 'id']);
        
        
    }
    public function getMyEventTicketBooking()
    {
        return $this->hasMany(EventTicketBooking::className(), ['event_id' => 'id'])->andOnCondition(['event_ticket_booking.user_id' => @Yii::$app->user->identity->id]);
        
        
    }
    public function getTotalMembers()
    {
        return (int)$this->hasMany(EventTicketBooking::className(), ['event_id' => 'id'])->sum('ticket_qty');
        
    }
    public function getTotalReaction()
    {
       
        return (int)$this->hasMany(UserReaction::className(), ['reference_id' => 'id'])->andOnCondition(['user_reaction.type'=>UserReaction::TYPE_EVENT,'user_reaction.reaction'=>UserReaction::REACTION_INTERESTING ])->sum('total_item');
       
    }
    
    public function getIsReact()
    {
    
        $result=$this->hasOne(UserReaction::className(), ['reference_id' => 'id'])->andOnCondition(['user_reaction.type'=>UserReaction::TYPE_EVENT,'user_reaction.user_id' => @Yii::$app->user->identity->id])->asArray()->one();
        if($result){
            return $result['reaction'];
        }else{
            return 0;
        }
    }
    

    





}
