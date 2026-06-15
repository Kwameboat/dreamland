<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;



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
            [['event_id','ticket_type','limit'], 'required','on'=>['create']],            
            [['id','event_id','ticket_type','limit'], 'required','on'=>['updateTicket']],
            
            

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
        /*$fields['available_ticket'] = (function ($model) {
            if($model->status !=10){
               return 0; 
            }else{
                
                return $model->available_ticket;
            }
        });*/
        return $fields;
    }
   
    

}
