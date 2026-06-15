<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\GiftCategory;


/**
 * This is the model class 
 *
 */
class UserLiveHistory extends \yii\db\ActiveRecord
{
    const STATUS_ONGOING=1;
    const STATUS_COMPLETED=2;


    
    
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
            [['id', 'status'], 'required'],
            [['status', 'id','user_id','start_time','end_time','total_time'], 'integer'],
            [['channel_name','token'], 'string'],
           


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'is_paid' => Yii::t('app', 'Is Paid ?'),
            'coin' => Yii::t('app', 'Coin'),
            
            
        ];
    }
   
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

    public function getSenderUser()
    {
        return $this->hasOne(User::className(), ['id'=>'sender_id']);
        
    }

    public function getGiftDetails()
    {
        return $this->hasMany(GiftHistory::className(), ['live_call_id'=>'id'])->andOnCondition(['reciever_id'=>$this->user_id]);
        
    }

    public function getTotalCoinFromBattle($liveCallId,$battleId,$recieverId)
    {
      $result =  GiftHistory::find()->where(['live_call_id'=>$liveCallId,'battle_id'=>$battleId ,'reciever_id'=>$recieverId])->andWhere(['send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE])->all();
        // return $this->hasMany(GiftHistory::className(), ['live_call_id'=>'id'])->andOnCondition(['reciever_id'=>$this->user_id]);
        if($result>0){
            $total_coin=0.00;
            foreach($result as $giftData){
                $total_coin += number_format(round((float)$giftData->coin,2),2); 
            }
            return $total_coin;
        }
       
        
    }
    
    public function getTimeInHrs($totalTime){
        $seconds = $totalTime;
        $secs = $seconds % 60;
        $hrs = $seconds / 60;
        $mins = (int)@($hrs % 60);
        
        $hrs = $hrs / 60;
        
        return ( (int)$hrs . " hr :" . (int)$mins . " min :" . (int)$secs.' sec');
    }

    public function getUserName($userId){
        $result =  User::find()->where(['id'=>$userId,'status'=>User::STATUS_ACTIVE])->one();
        if($result){
          return $result->username;
        }
    }

    public function getTotalLiveHistory()
    {
        $totalLiveHistory =0;
        $totalCurrentLive = 0;
        $tatalCompletePercentage =0;    
        $totalLiveHistory = UserLiveHistory::find()->count();

        
        $totalCurrentLive = UserLiveHistory::find()->where(['status'=>UserLiveHistory::STATUS_ONGOING])->count();
        if($totalLiveHistory>0){

            $tatalCompletePercentage = (($totalLiveHistory-$totalCurrentLive)/$totalLiveHistory)*100;
        }else{
            $tatalCompletePercentage=100;
        }
       
        $response = array(
            "totallive" =>$totalLiveHistory,
            'totalCurrentLive'=>$totalCurrentLive,
            'percentage'=>$tatalCompletePercentage
        );
        return $response;
    }

}
