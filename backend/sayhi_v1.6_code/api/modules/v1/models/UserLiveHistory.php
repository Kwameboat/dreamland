<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\GiftHistory;
use api\modules\v1\models\LiveCallViewer;
use api\modules\v1\models\UserLiveBattle;


class UserLiveHistory extends \yii\db\ActiveRecord
{
    const STATUS_ONGOING=1;
    const STATUS_COMPLETED=2;

    const POINT_NEW_USER_JOIN = 10;
    const POINT_NEW_GIFT = 5;
    const POINT_NEW_COMMENT = 1;
    
    const TOTAL_POINT_FOR_TENDING= 20;
    const MAXIMUM_RADIUS_FOR_NEAR_BY= 50;
    


    public $latitude;
    public $longitude;
    public $is_near_by;
    public $is_battle;
    public $is_new_user;
    public $is_trending;
    public $total_point;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_live_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','user_id','start_time','end_time','total_time','status','total_comment','is_near_by','is_battle','is_new_user','is_trending','total_point','is_monetized','price_credits'], 'integer'],
            [['latitude','longitude','is_near_by','is_battle','is_new_user','is_trending','total_point','live_title','channel_name','token'], 'safe']
            
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
            $this->start_time = time();
        }

        
        return parent::beforeSave($insert);
    }
    
    
    
    public function fields()
    {
        $fields = parent::fields();

        
        $fields['share_link'] = (function($model){
        
            return Yii::$app->params['siteUrl'] . Yii::$app->urlManagerFrontend->baseUrl.'/user-live-history/share-live/?id='.$model->channel_name;
        });
       
        return $fields;
    }
    public function extraFields()
    {
        return ['giftSummary','userdetails','totalJoinedUsers'];
    }


    public function getGiftSummary(){

        $modelGiftHistory =  new GiftHistory();
       
        $result = $modelGiftHistory->find()
        ->select(['count(id) as totalGift','sum(coin) as totalCoin'])
        ->where(['live_call_id'=>$this->id,'send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE])->asArray()->one();
        
        $totalGift = (int)$result['totalGift'];
        $totalCoin = (int)$result['totalCoin'];
        
        $response=[
            'totalGift'=>$totalGift,
            'totalCoin'=>$totalCoin

        ];
        return $response;

     }

     public function getUser()
     {
         return $this->hasMany(User::className(), ['id'=>'user_id']);
     }  
   
     public function getFollower()
     {
         return $this->hasMany(Follower::className(), ['user_id'=>'id']);
     }  

     public function getTotalJoinedUsers()
     {
         return (int)$this->hasMany(LiveCallViewer::className(), ['live_call_id'=>'id'])->andOnCondition(['live_call_viewer.is_ban' => 0])->count();
     }  
     public function getLiveCallViewer()
     {
         return $this->hasMany(LiveCallViewer::className(), ['live_call_id'=>'id'])->andOnCondition(['live_call_viewer.is_ban' => 0]);
     }  
     
     public function getUserdetails()
     {
         return $this->hasMany(User::className(), ['id'=>'user_id'])->select(['id','name','username','image','cover_image']);
     } 
     public function getBattle()
     {
         return $this->hasOne(UserLiveBattle::className(), ['user_live_history_id'=>'id'])->andOnCondition(['user_live_battle.status' =>UserLiveBattle::STATUS_ONGOING ]);
     } 
     public function getRecievedGift()
     {
         return $this->hasMany(GiftHistory::className(), ['live_call_id'=>'id'])->andOnCondition(['send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE]);
     }  

     
    

}
