<?php
namespace api\modules\v1\models;

use Yii;
use yii\helpers\ArrayHelper;

use api\modules\v1\models\User;
use api\modules\v1\models\GiftHistory;
use api\modules\v1\models\LiveCallViewer;

//use api\modules\v1\models\Message;

class UserLiveBattle extends \yii\db\ActiveRecord
{
   
    const STATUS_PENDING = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;
    const STATUS_ONGOING = 4;
    const STATUS_COMPLETED = 10;





    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_live_battle';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {

        return [
            [['id', 'user_live_history_id','super_host_user_id','host_user_id', 'start_time', 'end_time', 'total_time','total_allowed_time', 'status'], 'integer']
            //[['message'], 'string']

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

  /*  public function beforeSave($insert)
    {
        if ($insert) {
            $this->start_time = time();
        }


        return parent::beforeSave($insert);
    }*/



    public function fields()
    {
        $fields = parent::fields();

        // $fields['username'] = (function($model){
        //     return @$model->userdetail;
        // });

        return $fields;
    }
    /*public function extraFields()
    {
        return ['giftSummary', 'userdetails', 'totalJoinedUsers'];
    }*/


    public function getBettleGiftSummary($battleId,$userId)
    {

        
        $modelGiftHistory = new GiftHistory();
        $modelUser = new User();

        $result = $modelGiftHistory->find()
            ->select(['gift_history.reciever_id,count(gift_history.id) as totalGift', 'sum(gift_history.coin) as totalCoin'])
            ->where(['gift_history.battle_id' => $battleId, 'gift_history.reciever_id' => $userId, 'gift_history.send_on_type' => GiftHistory::SEND_TO_TYPE_LIVE])
           
            ->asArray()->one();

        $userResult =     $modelUser->findOne($userId);
           
        $totalGift = (int) $result['totalGift'];
        $totalCoin = (int) $result['totalCoin'];
      
        $response = [
            "userId" => $userResult->id,
            "userName" => $userResult->username,
            "userImage" =>$userResult->picture,
            'totalGift' => $totalGift,
            'totalCoin' => $totalCoin
        ];
        return $response;

    }

    /*public function getUser()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id']);
    }

    public function getFollower()
    {
        return $this->hasMany(Follower::className(), ['user_id' => 'id']);
    }

    public function getTotalJoinedUsers()
    {
        return (int) $this->hasMany(LiveCallViewer::className(), ['live_call_id' => 'id'])->count();
    }

    public function getUserdetails()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])->select(['id', 'name', 'username', 'image', 'cover_image']);
    }*/



}