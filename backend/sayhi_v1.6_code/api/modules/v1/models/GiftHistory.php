<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;


use api\modules\v1\models\Gift;
use api\modules\v1\models\Post;



class GiftHistory extends \yii\db\ActiveRecord
{
    const SEND_TO_TYPE_LIVE=1;
    const SEND_TO_TYPE_PROFILE=2;
    const SEND_TO_TYPE_POST=3;
    const SHOW_TOP_GIFT_RECIEVER_LIMIT =15;
    const POST_TYPE_TIMELINE_GIFT =2; 


    public $totalGift;
    public $totalCoin;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gift_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            

            [[ 'id','reciever_id','sender_id','gift_id','send_on_type','live_call_id','battle_id','post_id','created_at','post_type'], 'integer'],
            [['gift_id','reciever_id','send_on_type' ], 'required','on'=>['sendGift']],
            [['totalGift','totalCoin'], 'safe'],
            //[['id','reciever_id','send_on_type' ], 'required','on'=>['sendGift']],
            [['coin','coin_actual'], 'number'],
            [['gift_id','reciever_id','send_on_type','post_type' ], 'required','on'=>['sendTimelineGift']],
            
            
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
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        $fields['totalGift'] = (function($model){
            return @$model->totalGift;
        });
        $fields['totalCoin'] = (function($model){
            return @(int)$model->totalCoin;
        });
        return $fields;
    }


    public function extraFields()
    {
        return ['giftDetail','senderDetail','giftTimelineDetail','totalGift'];
    }

    
    public function getGiftDetail()
    {
        return $this->hasOne(Gift::className(), ['id'=>'gift_id']);
    }

    public function getSenderDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }
    public function getRecieverDetail()
    {
        return $this->hasOne(User::className(), ['id'=>'reciever_id']);
        
    }
   
    public function getGiftTimelineDetail()
    {
        return $this->hasOne(GiftTimeline::className(), ['id'=>'gift_id']);
    }

    

}
