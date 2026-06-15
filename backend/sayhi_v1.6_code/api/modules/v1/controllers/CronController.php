<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Ad;
use api\modules\v1\models\AdSubscription;
use Yii;
use DateTime;
use yii\rest\ActiveController;
use api\modules\v1\models\Post;
use api\modules\v1\models\Subscription;
use api\modules\v1\models\Payment;
use api\modules\v1\models\User;
// use api\modules\v1\models\Order;
// use api\modules\v1\models\OrderSellerDetail;
use api\modules\v1\models\Notification;
// use api\modules\v1\models\AdPromotion;
use api\modules\v1\models\PostPromotion;
use api\modules\v1\models\GiftHistory;
use api\modules\v1\models\StreamerAwardSetting;
use api\modules\v1\models\StreamerAwardHistory;
use api\modules\v1\models\Setting;
use api\modules\v1\models\ChatMessage;
use api\modules\v1\models\ChatMessageUser;





/**
 * Cron
 *

 */
class CronController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\post';

    public function actions()
    {
        $actions = parent::actions();

        // disable default actions
        unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);

        return $actions;
    }


    /**
     * update ad exprity and subscription expiry
     */

    /*
   public function actionAdStatus(){

       $modelAd    = new Ad();    
       $modelSubscription   = new Subscription();    


       $curentTime = time();
    ///  udpate ad       
       $modelAd->updateAll(['status'=>Ad::STATUS_EXPIRED],['and',['status'=>Ad::STATUS_ACTIVE],['<','expire_date',$curentTime]]);

       /// remover feature tag whose feature expry date
       $modelAd->updateAll(['featured'=>Ad::FEATURED_NO],['and',['status'=>Ad::STATUS_ACTIVE,'featured'=>Ad::FEATURED_YES],['<','featured_exp_date',$curentTime]]);
      //echo Ad::FEATURED_YES;
      
       // update subscription

       $modelSubscription->updateAll(['status'=>Subscription::STATUS_EXPIRED],['and',['status'=>Subscription::STATUS_ACTIVE],['<','expiry_date',$curentTime]]);

       $response['message']='ok';
     // $response['ad']= $result;
       return $response;

   }*/

    public function actionTesting()
    {
        $currentTime = time();
        $msg = 'U2FsdGVkX18g3ZV7w1n1t1ICsAQ62w759IwjL5dPM/lZsUu1WT92DYBBUNmNpbWN/gllXqukXKKFCBdbJlMIidfY6TQOGCQkeeYU4VA0B7bmNrfd/XWyYXCmD1DG2XUf0BrqFN2u0zARPgVBnGMPFA==';
       //imamge
        $password = 'bbC2H19lkVbQDfakxcrtNMQdd0FloLyw';
        $encryptedDataFromFlutter = $msg;
        $passphrase = $password;
        $decryptedData = $this->decryptAESFlutter($encryptedDataFromFlutter, $passphrase);
        $decryptedData = substr($decryptedData, 0, strpos($decryptedData, "}")+1);
        $decryptedData = json_decode($decryptedData, true);
        print_r($decryptedData);
        $messageType = $decryptedData['messageType'];
        $mediaTypeArr=[];
        $mediaTypeArr[]=2;
        $mediaTypeArr[]=3;
        $mediaTypeArr[]=4;
        if(in_array($messageType,$mediaTypeArr) ){
           
            $fileUrl = $decryptedData['image'];
            $fileUrl = parse_url($fileUrl, PHP_URL_PATH);
            $filename = basename($fileUrl);
            Yii::$app->fileUpload->deleteFile(Yii::$app->fileUpload::TYPE_CHAT,$filename);
        }
    }



        
    function decryptAESFlutter($encryptedBase64, $passphrase) {
        $encryptedBytesWithSalt = base64_decode($encryptedBase64);

        // Extract salt and encrypted data
        $salt = substr($encryptedBytesWithSalt, 8, 8);
        $encryptedData = substr($encryptedBytesWithSalt, 16);


        list($key, $iv) = $this->deriveKeyAndIV($passphrase, $salt);

        $decrypted = openssl_decrypt(
            $encryptedData,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );  
        $decrypted = substr($decrypted, 0, strpos($decrypted, "}")+1);
        return   $decrypted;
       
    }

    function deriveKeyAndIV($passphrase, $salt) {
        $password = $passphrase;//$this->createUint8ListFromString($passphrase);
        $concatenatedHashes = '';
        $currentHash = '';
        $enoughBytesForKey = false;
        $preHash = '';
    
        while (!$enoughBytesForKey) {
            if (!empty($currentHash)) {
                $preHash = $currentHash . $password . $salt;
            } else {
                $preHash = $password . $salt;
            }
            $currentHash = md5($preHash, true);
            $concatenatedHashes .= $currentHash;
            if (strlen($concatenatedHashes) >= 48) {
                $enoughBytesForKey = true;
            }
        }
    
        $keyBytes = substr($concatenatedHashes, 0, 32);
        $ivBytes = substr($concatenatedHashes, 32, 16);
    
        return [$keyBytes, $ivBytes];
    }
    
   
    function createUint8ListFromString($string) {
        return array_values(unpack('C*', $string));
    }


  
    
    public function actionProcessTwice()
    {

        //#region delete post after 30 dyas
        $modelSetting = new Setting();
        $modelPost = new Post();

        $settingResult = $modelSetting->find()->one();
        $postDeletePeriod = $settingResult->post_delete_period;

        $currentTime = time();
        $postDeletePeriodFinal = $currentTime - $postDeletePeriod;
        if ($postDeletePeriod > 0) {

            //Post::TYPE_NORMAL,Post::TYPE_COMPETITION,Post::TYPE_RESHARE_POST
            //$modelPost->updateAll(['status'=>Post::STATUS_DELETED],['and',['status'=>Post::STATUS_ACTIVE,'type'=>[Post::TYPE_RESHARE_POST]],['<','created_at',$postDeletePeriodFinal]]);

            $results = $modelPost->find()->select(['id', 'type', 'created_at'])
                ->where(['status' => Post::STATUS_ACTIVE, 'type' => [Post::TYPE_NORMAL, Post::TYPE_COMPETITION, Post::TYPE_RESHARE_POST]])
                ->andWhere(['<', 'created_at', $postDeletePeriodFinal])
                ->asArray()->all();

            $ids = [];
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (count($ids) > 0) {
                $modelPost->updateAll(['status' => Post::STATUS_DELETED], ['IN', 'id', $ids]);
            }
            // print_r($ids);
        }
        //#endregion 
        //#region delete chat 

        $modelChatMessage = new ChatMessage();
        $modelChatMessageUser = new ChatMessageUser();
        
        $results = $modelChatMessage->find()->select(['id', 'delete_time', 'status','message'])
            //->where(['status' => Post::STATUS_ACTIVE, 'type' => [Post::TYPE_NORMAL,Post::TYPE_COMPETITION,Post::TYPE_RESHARE_POST]])
            ->where(['<>', 'status' , ChatMessage::STATUS_DELETED])
            ->andWhere(['>', 'delete_time', 0])
            ->andWhere(['<', 'delete_time', $currentTime])
            ->asArray()->all();

        $ids = [];
        $passphrase = 'bbC2H19lkVbQDfakxcrtNMQdd0FloLyw';
        foreach ($results as $result) {
            $messageId = $result['id'];

            $encryptedData = $result['message'];
            $decryptedData = $this->decryptAESFlutter($encryptedData, $passphrase);
            $decryptedData = substr($decryptedData, 0, strpos($decryptedData, "}")+1);
            $decryptedData = @json_decode($decryptedData, true);
            $messageType = (int)@$decryptedData['messageType'];
            $mediaTypeArr=[];
            $mediaTypeArr[]=2;
            $mediaTypeArr[]=3;
            $mediaTypeArr[]=4;
            $mediaTypeArr[]=5;
            $mediaTypeArr[]=6;
            $mediaTypeArr[]=16;
            if(in_array($messageType,$mediaTypeArr) ){
               
                $fileUrl = @$decryptedData['image'];
                $fileUrl = parse_url($fileUrl, PHP_URL_PATH);
                $filename = basename($fileUrl);
                Yii::$app->fileUpload->deleteFile(Yii::$app->fileUpload::TYPE_CHAT,$filename);
                $fileVideoUrl = @$decryptedData['video'];
                if($fileVideoUrl){
                    $fileVideoUrl = parse_url($fileVideoUrl, PHP_URL_PATH);
                    $filename = basename($fileVideoUrl);
                    Yii::$app->fileUpload->deleteFile(Yii::$app->fileUpload::TYPE_CHAT,$filename);

                }
            }
            $ids[] = $messageId;
           
        }
        if(count($ids)>0){
            $messageIdsChuck =array_chunk($ids, 100);
            foreach($messageIdsChuck as $messageIds){
                $modelChatMessage->deleteAll(['id'=>$messageIds]);
                $modelChatMessageUser->deleteAll(['chat_message_id'=>$messageIds]);
               // $modelChatMessage->updateAll(['status' => ChatMessage::STATUS_DELETED], ['IN', 'id', $messageIds]);
               // $modelChatMessageUser->updateAll(['status' => ChatMessage::STATUS_DELETED], ['IN', 'chat_message_id', $messageIds]);
            }
        }
        //#endregion 
        $response['message'] = 'ok';
        return $response;
    }




    
    public function actionProcessTwice_backup()
    {

        //#region delete post after 30 dyas
        $modelSetting = new Setting();
        $modelPost = new Post();

        $settingResult = $modelSetting->find()->one();
        $postDeletePeriod = $settingResult->post_delete_period;

        $currentTime = time();
        $postDeletePeriodFinal = $currentTime - $postDeletePeriod;
        if ($postDeletePeriod > 0) {

            //Post::TYPE_NORMAL,Post::TYPE_COMPETITION,Post::TYPE_RESHARE_POST
            //$modelPost->updateAll(['status'=>Post::STATUS_DELETED],['and',['status'=>Post::STATUS_ACTIVE,'type'=>[Post::TYPE_RESHARE_POST]],['<','created_at',$postDeletePeriodFinal]]);

            $results = $modelPost->find()->select(['id', 'type', 'created_at'])
                ->where(['status' => Post::STATUS_ACTIVE, 'type' => [Post::TYPE_NORMAL, Post::TYPE_COMPETITION, Post::TYPE_RESHARE_POST]])
                ->andWhere(['<', 'created_at', $postDeletePeriodFinal])
                ->asArray()->all();

            $ids = [];
            foreach ($results as $result) {
                $ids[] = $result['id'];
            }
            if (count($ids) > 0) {
                $modelPost->updateAll(['status' => Post::STATUS_DELETED], ['IN', 'id', $ids]);
            }
            // print_r($ids);
        }
        //#endregion 
        //#region delete chat 

        $modelChatMessage = new ChatMessage();
        $modelChatMessageUser = new ChatMessageUser();
        
        $results = $modelChatMessage->find()->select(['id', 'delete_time', 'status','message'])
            //->where(['status' => Post::STATUS_ACTIVE, 'type' => [Post::TYPE_NORMAL,Post::TYPE_COMPETITION,Post::TYPE_RESHARE_POST]])
            ->where(['<>', 'status' , ChatMessage::STATUS_DELETED])
            ->andWhere(['>', 'delete_time', 0])
            ->andWhere(['<', 'delete_time', $currentTime])
            ->asArray()->all();

        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result['id'];
        }
        if(count($ids)>0){
            //print_r($ids);
            $messageIdsChuck =array_chunk($ids, 100);
            foreach($messageIdsChuck as $messageIds){
                $modelChatMessage->updateAll(['status' => ChatMessage::STATUS_DELETED], ['IN', 'id', $messageIds]);
                $modelChatMessageUser->updateAll(['status' => ChatMessage::STATUS_DELETED], ['IN', 'chat_message_id', $messageIds]);
            }
        }
        //#endregion 
        $response['message'] = 'ok';
        return $response;
    }












    /**
     * this cron can be made available of pending amount for seller order (one time a day)
     */
    public function actionProcessPayment()
    {

        $modelPayment = new Payment();
        $modelUser = new User();
        $currentTime = time();
        //echo $endTime =  strtotime('-3 day', $currentTime);
        $endTime = $currentTime - 3600;
        $results = Payment::find()->select(['id', 'amount', 'user_id', 'created_at'])
            // ->where(['processed_status'=>Payment::PROCESSED_STATUS_PENDING])
            ->andWhere(['<', 'created_at', $endTime])
            ->asArray()->all();


        $userPayment = [];
        $paymentIds = [];

        foreach ($results as $result) {
            $innerArray = [];
            $userId = $result['user_id'];
            $amount = $result['amount'];
            $found_key = array_search($userId, array_column($userPayment, 'userId'));
            if (is_int($found_key)) {
                $preAmount = $userPayment[$found_key]['amount'];
                $userPayment[$found_key]['amount'] = $preAmount + $amount;
            } else {
                $innerArray['userId'] = $userId;
                $innerArray['amount'] = $amount;
                $userPayment[] = $innerArray;
            }
            $paymentIds[] = $result['id'];
        }
        if (count($userPayment) > 0) {
            foreach ($userPayment as $record) {
                $resultUser = $modelUser->findOne($record['userId']);
                $resultUser->available_balance = $resultUser->available_balance + $record['amount'];
                $resultUser->save(false);
            }
        }
        if (count($paymentIds) > 0) {

            $modelPayment->updateAll(['processed_status' => Payment::PROCESSED_STATUS_COMPLETED], ['IN', 'id', $paymentIds]);

        }

        $response['message'] = 'ok';
        $response['paymentIds'] = $paymentIds;


        return $response;

    }

    /**
     * this cron can will work once a day 
     * this will work for ad promotion , if ad is expired then mark is complted and check total spend and refund if any amount is not yet used
     */
    public function actionPostPromotionComplete()
    {


        // $modelPayment           = new Payment();  
        $modelUser = new User();
        $modelAdPromotion = new PostPromotion();

        $currentTime = time();

        $results = PostPromotion::find()
            ->select('post_promotion.*')
            ->where(['IN', 'post_promotion.status', [PostPromotion::STATUS_PAUSED, PostPromotion::STATUS_ACTIVE]])
            ->andwhere(['<', 'post_promotion.expiry', $currentTime])
            ->all();





        $userPayment = [];
        $paymentIds = [];

        foreach ($results as $result) {

            $totalSpend = $result->totalSpend;
            $refundAmount = $result->total_amount - $totalSpend;

            $result->total_spend = $totalSpend;
            $result->status = PostPromotion::STATUS_COMPLETED;
            if ($result->save(false)) {
                if ($refundAmount > 0) {
                    $userId = $result->post->user_id;
                    $resultUser = $modelUser->findOne($userId);
                    $resultUser->available_balance = $resultUser->available_balance + $refundAmount;
                    if ($resultUser->save(false)) {
                        $modelPayment = new Payment();
                        $modelPayment->user_id = $userId;
                        $modelPayment->post_promotion_id = $result->id;
                        $modelPayment->amount = $refundAmount;
                        $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type = Payment::PAYMENT_TYPE_PROMOTION_REFUND;
                        $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
                        // $modelPayment->status               =  Payment::PROCESSED_STATUS_COMPLETED;
                        $modelPayment->save(false);
                    }
                }

            }


        }

        $response['message'] = 'ok';


        return $response;

    }


    public function actionStreamerAward()
    {
        // Get the current timestamp

        $modelStreamerAwardSetting = new StreamerAwardSetting();
        $resultAwardSetting = $modelStreamerAwardSetting->getAwardSetting();
        $modelUser = new User();
        $model = new GiftHistory();
        $modelSetting = new Setting();

        //  $weekStart = strtotime('last Monday', $today); // Get the timestamp of the previous Monday
        $weekStart = strtotime('monday this week');
        // Set the date to the next Sunday
        $today = new DateTime(); // Get today's timestamp
        $today->modify('next Sunday');
        $today->setTime(23, 59, 59);
        $weekEnd = $today->getTimestamp();

        $results = $model->find()
            ->select(['id', 'count(id) as total_gifts', 'reciever_id', 'SUM(coin) AS coin ', 'send_on_type', 'live_call_id', 'created_at'])
            ->where(['send_on_type' => GiftHistory::SEND_TO_TYPE_LIVE])
            ->andWhere(['>=', 'created_at', $weekStart])
            ->andWhere(['<=', 'created_at', $weekEnd])
            ->groupBy(['reciever_id'])
            ->orderBy(['SUM(coin)' => SORT_DESC])
            ->limit(10)
            ->asArray()->all();

        $position = 1;
        foreach ($results as $result) {
            $recieverId = $result['reciever_id'];
            $key = array_search($position, array_column($resultAwardSetting, 'position_id'));
            $awardCoin = 0;
            if (is_int($key)) {
                $awardCoin = $resultAwardSetting[$key]['award_coin'];

            }
            if ($awardCoin > 0) {
                $modelStreamerAwardHistory = new StreamerAwardHistory();
                $modelStreamerAwardHistory->user_id = $recieverId;
                $modelStreamerAwardHistory->coin = $awardCoin;
                $modelStreamerAwardHistory->position_number = $position;

                if ($modelStreamerAwardHistory->save()) {

                    //// add coin in receiver account
                    $resultUser = $modelUser->findOne($recieverId);
                    $resultUser->available_balance = $resultUser->available_balance + $awardCoin;
                    if ($resultUser->save(false)) {
                        $modelPayment = new Payment();
                        $modelPayment->type = Payment::TYPE_COIN;
                        $modelPayment->user_id = $recieverId;
                        $modelPayment->streamer_award_history_id = $modelStreamerAwardHistory->id;
                        $modelPayment->coin = $awardCoin;
                        $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type = Payment::PAYMENT_TYPE_STREAMING_AWARD;
                        $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
                        $modelPayment->status = Payment::PROCESSED_STATUS_COMPLETED;
                        $modelPayment->save(false);
                    }

                    //// deduct coin in admin account
                    $settingResult = $modelSetting->find()->one();
                    $settingResult->available_coin = $settingResult->available_coin - $awardCoin;
                    $adminId = 1;
                    if ($settingResult->save(false)) {
                        $modelPayment = new Payment();
                        $modelPayment->type = Payment::TYPE_COIN;
                        $modelPayment->user_id = $adminId;
                        $modelPayment->streamer_award_history_id = $modelStreamerAwardHistory->id;
                        $modelPayment->coin = $awardCoin;
                        $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;
                        $modelPayment->payment_type = Payment::PAYMENT_TYPE_STREAMING_AWARD;
                        $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
                        $modelPayment->status = Payment::PROCESSED_STATUS_COMPLETED;
                        $modelPayment->save(false);
                    }
                }
            }
            $position++;
        }
        $response['message'] = 'ok';
        return $response;

    }


    /**
     * update ad exprity and subscription expiry
     */
    public function actionAdStatus()
    {

        $modelAd = new Ad();
        $modelSubscription = new AdSubscription();


        $curentTime = time();
        ///  udpate ad       
        $modelAd->updateAll(['status' => Ad::STATUS_EXPIRED], ['and', ['status' => Ad::STATUS_ACTIVE], ['<', 'expire_date', $curentTime]]);

        /// remover feature tag whose feature expry date
        $modelAd->updateAll(['featured' => Ad::FEATURED_NO], ['and', ['status' => Ad::STATUS_ACTIVE, 'featured' => Ad::FEATURED_YES], ['<', 'featured_exp_date', $curentTime]]);
        //echo Ad::FEATURED_YES;

        // update subscription

        $modelSubscription->updateAll(['status' => AdSubscription::STATUS_EXPIRED], ['and', ['status' => AdSubscription::STATUS_ACTIVE], ['<', 'expiry_date', $curentTime]]);

        $response['message'] = 'ok';
        // $response['ad']= $result;
        return $response;

    }
    




}