<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\Event;


/**
 * This is the model class 
 *
 */
class EventTicket extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event_ticket';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
          
            
            [['status', 'id','event_id','limit','available_ticket','created_at','updated_at'], 'integer'],
            [['ticket_type'], 'string'],
            [['price'], 'number'],
            [[ 'ticket_type','event_id','limit' ], 'required','on'=>['create','update']],
            

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
            'limit' => Yii::t('app', 'Ticket Limit'),
            'ticket_type' => Yii::t('app', 'Ticket Type'),
            'price' => Yii::t('app', 'Price')
            
            
            
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

  
    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }
  
    
    public function getEvent()
    {
        return $this->hasOne(Event::className(), ['id' => 'event_id']);

    }

   



    

}
