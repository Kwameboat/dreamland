<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\Event;
use api\modules\v1\models\EventSearch;
use api\modules\v1\models\EventCoupon;
use api\modules\v1\models\EventTicketBooking;
use api\modules\v1\models\EventTicket;
use api\modules\v1\models\EventGallaryImage;
use yii\web\UploadedFile;
use Twilio\Rest\Client;
use api\modules\v1\models\Payment;
use api\modules\v1\models\UserReaction;




/**
 * Controller API
 *
 
 */
class EventController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\event';   
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
    
    public function actions()
	{
		$actions = parent::actions();

		// disable default actions
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete'], $actions['view']);                    

		return $actions;
	}    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'except'=>[],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }


    public function actionIndex(){
        $model = new EventSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['event']=$result;
        return $response;
    }
    public function actionMyEvent(){
        $model = new EventSearch();
        $result = $model->searchMyEvent(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['event']=$result;
        return $response;
    }
    public function actionUpdateStatus()
    {
        $model = new Event();
        $model->scenario = 'updateStatus';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $id = @(int) $model->id;



        $modelEvent = $model->findOne($id);

        $userId = Yii::$app->user->identity->id;
        $allowStatus = [];
        $allowStatus[] = Event::STATUS_DEACTIVE;
        $allowStatus[] = Event::STATUS_ACTIVE;
        $newStatus =  Yii::$app->getRequest()->getBodyParams()['status'];
        $preSatus = $modelEvent->status;

        if ($modelEvent->created_by != $userId || !in_array($newStatus,$allowStatus)) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;
        }

        if($preSatus==$newStatus){
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionAlready'];
            $response['errors'] = $errors;
            return $response;
        }
        $preAllowStatus = [];
        $preAllowStatus[] = Event::STATUS_DEACTIVE;
        $preAllowStatus[] = Event::STATUS_ACTIVE;
        
        if (!in_array($preSatus,$preAllowStatus)) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;
        }
        if ($preSatus==Event::STATUS_DELETED) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;
        }

        $modelEvent->load(Yii::$app->getRequest()->getBodyParams(), '');
        
        if ($modelEvent->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['common']['actionSuccess'];
            return $response;
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
          
        }
    }

    public function actionCoupon(){
        $modelCoupon = new EventCoupon();
        $currentTime = time();
        $query = $modelCoupon->find()->where(['event_coupon.status'=>EventCoupon::STATUS_ACTIVE]);
        $query->andwhere(['>','event_coupon.expiry_date',$currentTime]);
        
        $result = $query->all();
        
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['coupon']=$result;
        return $response;
    }

    
    public function actionView($id)
    {
        $model = new Event();
        $result = $model->find()->where(['event.id'=>$id])
        ->one();
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['event'] = $result;
        return $response;

    }
    public function actionViewByUniqueId($unique_id)
    {

        
        $model = new Event();
        $result = $model->find()->where(['event.unique_id'=>$unique_id])
        ->one();
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['event'] = $result;
        return $response;


    }

    public function actionBuyTicket()
    {
        $userId                 = Yii::$app->user->identity->id;
       
        $model                  =     new EventTicketBooking();
        $modelEventTicket       =   new EventTicket();
        $modelEvent             =   new Event();
        $modelUserReaction       =   new UserReaction();

        
        $modelUser   =   new User();
        $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='buyTicket';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $currentTime=time();
           $eventId                 =  @(int) $model->event_id;
           $eventTicketId           =  @(int)$model->event_ticket_id;
           $resultEvent             = $modelEvent->find()->where(['id'=>$eventId,'status'=>Event::STATUS_ACTIVE])->one();
           $resultEventTicket       = $modelEventTicket->find()->where(['id'=>$eventTicketId,'status'=>EventTicket::STATUS_ACTIVE])->one();

          // print_r($resultEventTicket);

          
           
           if(!$resultEvent){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
           }

           if($currentTime > $resultEvent->end_date){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['eventClosed'];
                $response['errors']=$errors;
                return $response;
        
            }

          

            if(!$resultEventTicket){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
           }

           if($model->ticket_qty > $resultEventTicket->available_ticket){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['seatNotAvailable'];
                $response['errors']=$errors;
                return $response;
        
            }

             #region check availabe balance
            
             //if($model->is_paid==Order::IS_PAID_YES){
            foreach($model->payments as $payment){
                if($payment['payment_mode']==Payment::PAYMENT_MODE_WALLET && $payment['amount'] > $resultUser->available_balance ){
                    $response['statusCode']=422;
                    $errors['message'][] = Yii::$app->params['apiMessage']['event']['amountNotAvailable'];
                    $response['errors']=$errors;
                    return $response;
    

                }

            }
            // }           
             #endregion


           
            //resultUser
           
           $model->user_id       =   $userId;     

            if($model->save()){

                $resultEventTicket->available_ticket =  $resultEventTicket->available_ticket-$model->ticket_qty;
                $resultEventTicket->save();
                foreach($model->payments as $payment){
                    $modelPayment          = new Payment();
                    $modelPayment->type                 =  Payment::TYPE_PRICE;
                    $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                    $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_EVENT_TICKET;
                    $modelPayment->payment_mode         =  $payment['payment_mode'];
                    $modelPayment->amount               =  $payment['amount'];
                    $modelPayment->event_ticket_booking_id           =  $model->id;
                    $modelPayment->transaction_id        =  $payment['transaction_id'];
                    $modelPayment->save(false);
                    if($payment['payment_mode']==Payment::PAYMENT_MODE_WALLET){

                        $resultUser = $modelUser->findOne($userId);
                        $resultUser->available_balance   = $resultUser->available_balance - $payment['amount'];
                        $resultUser->save(false);

                    }
                }

                /// update going conter
                $referenceId        = $eventId;
                $type               = $modelUserReaction::TYPE_EVENT;
                $reactionResult     = $modelUserReaction->find()->where(['user_id'=>$userId,'type'=>$type,'reference_id'=>$referenceId])->one();
               
                if(!$reactionResult){
                    $reactionResult = $modelUserReaction;
                }
                $reactionResult->type           =   $modelUserReaction::TYPE_EVENT;
                $reactionResult->reference_id   =    $referenceId;

                $reactionResult->reaction   =   $modelUserReaction::REACTION_INTERESTING;
                $reactionResult->total_item =   $model->ticket_qty;
                $reactionResult->save(false);
                
                $response['message']=Yii::$app->params['apiMessage']['event']['buyTicketSuccessfully'];
                $response['id']=$model->id;
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            

            }

            
        }

       
        
    }


    public function actionMyBookedEvent(){
        $model = new EventSearch();
        $result = $model->searchMyBookedEvent(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['eventBooking']=$result;
        return $response;
    }

    
    public function actionCancelTicketBooking()
    {
        $userId                 = Yii::$app->user->identity->id;
       
        $model                  =     new EventTicketBooking();
        $modelEventTicket       =   new EventTicket();
        $modelEvent             =   new Event();

        
        
        $modelUser   =   new User();
       // $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='cancelBooking';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
            $modelEventTicketBooking = $model->findOne($model->id);
           

              
           if(!$modelEventTicketBooking){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            }

                
            if($modelEventTicketBooking->user_id !=$userId){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;
            }

            if($modelEventTicketBooking->status ==EventTicketBooking::STATUS_CANCELLED){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['alreadyCancelled'];
                $response['errors']=$errors;
                return $response;
            }

            $resultEvent = $modelEvent->findOne($modelEventTicketBooking->event_id);
            
            $currentTime=time();

            if($currentTime > $resultEvent->start_date){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['canNotCancelled'];
                $response['errors']=$errors;
                return $response;
            }

            $modelEventTicketBooking->status = EventTicketBooking::STATUS_CANCELLED;
            if($modelEventTicketBooking->save()){
                $paidAmount = $modelEventTicketBooking->paid_amount;
                $resultEventTicket = $modelEventTicket->findOne($modelEventTicketBooking->event_ticket_id);
                $resultEventTicket->available_ticket=  $resultEventTicket->available_ticket+ $modelEventTicketBooking->ticket_qty;
                $resultEventTicket->save();

                /// refund amount
                $resultUser = $modelUser->findOne($userId);
                $resultUser->available_balance   = $resultUser->available_balance + $paidAmount;
                if($resultUser->save(false)){

                    $modelPayment          = new Payment();
                    $modelPayment->type                     =  Payment::TYPE_PRICE;
                    $modelPayment->transaction_type         =  Payment::TRANSACTION_TYPE_CREDIT;
                    $modelPayment->payment_type             =  Payment::PAYMENT_TYPE_EVENT_TICKET_REFUND;
                    $modelPayment->payment_mode             =  Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->amount                   =  $paidAmount;
                    $modelPayment->event_ticket_booking_id  =  $model->id;
                    $modelPayment->save(false);
                }


                /// remove reaction
                $modelUserReaction       =   new UserReaction();

                $referenceId        = $modelEventTicketBooking->event_id;
                $type               = $modelUserReaction::TYPE_EVENT;
                $reactionUserId = ($modelEventTicketBooking->gifted_to)?$modelEventTicketBooking->gifted_to : $userId;
                $reactionResult     = $modelUserReaction->find()->where(['user_id'=>$reactionUserId,'type'=>$type,'reference_id'=>$referenceId])->one();
                if($reactionResult){
                    $reactionResult->delete();
                }
                
                $response['message']=Yii::$app->params['apiMessage']['event']['cancelTicketSuccessfully'];
                return $response; 


            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }

            
        }

       
        
    }

      
    public function actionDetailTicketBooking($id)
    {
        $userId                 = Yii::$app->user->identity->id;
        $model                  =     new EventTicketBooking();
        //$modelEventTicket       =   new EventTicket();
        //$modelEvent             =   new Event();
        $query = $model->find()
        ->where(['event_ticket_booking.id'=>$id])
        /*->andWhere([
            'or',
                ['event_ticket_booking.user_id'=>$userId],
                ['event_ticket_booking.gifted_to'=>$userId]
        ])*/
         ->joinWith('event')
        ->joinWith(['giftedByUser' => function($query) {
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }]);
        $result = $query->one();
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['eventBooking']=$result;
        return $response;

        
    }

    public function actionAttachImageTicketBooking()
    {
        $userId                 = Yii::$app->user->identity->id;
       
        $model                  =     new EventTicketBooking();
        $modelEventTicket       =   new EventTicket();
        $modelEvent             =   new Event();

        
        $modelUser   =   new User();
       
       
        $model->scenario ='attachImage';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
            $modelEventTicketBooking = $model->findOne($model->id);
              
           if(!$modelEventTicketBooking){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            }
            $modelEventTicketBooking->image = $model->image;
           
            if($modelEventTicketBooking->save(false)){
                $user = $modelUser->findOne($modelEventTicketBooking->user_id);
                $imageUrl =  $modelEventTicketBooking->imageUrl;
                $eventName = $modelEventTicketBooking->event->name;
                $eventAddress = $modelEventTicketBooking->event->address;
                
                $eventStartTime =  Yii::$app->formatter->asDateTime($modelEventTicketBooking->event->start_date);
                $ticketType   = $modelEventTicketBooking->ticketDetail->ticket_type;
                $ticketQty    = $modelEventTicketBooking->ticket_qty;
                $userFirstName = $modelEventTicketBooking->user_first_name;
                $userLastName = $modelEventTicketBooking->user_last_name;

                $userNameOnTicket =  $userFirstName.' '. $userLastName;
                $userNameOnTicket= trim($userNameOnTicket);
                if(!$userNameOnTicket){
                    $userNameOnTicket = $user->username;
                }
                if($user->email){
                    $fromMail = Yii::$app->params['senderEmail'];
                    $fromName = Yii::$app->params['senderName'];
                    $from = array($fromMail => $fromName);
                    
                 Yii::$app->mailer->compose()
                     ->setSubject('Ticket Booking Confirmation')
                     ->setFrom($from)
                     ->setTo($user->email)
                     ->setHtmlBody('Congratulation!! <br>Following is your ticket detail.<br><br><table style="width:100%">
                     <tr><td width="15%"><b>Name : </b></td><td>'.$userNameOnTicket.'</td></tr>
                     <tr><td ><b>Event : </b></td><td>'.$eventName.'</td></tr>
                     <tr><td ><b>Address : </b></td><td>'.$eventAddress.'</td></tr>
                     <tr><td ><b>Time : </b></td><td>'.$eventStartTime.'</td></tr>
                     <tr><td ><b>Ticket : </b></td><td>'.$ticketType.'</td></tr>
                     <tr><td ><b>Ticket Qty. : </b></td><td>'.$ticketQty.'</td></tr></table>')
                     ->attach($imageUrl)
                     ->send();
    
                }
               // if (Yii::$app->params['siteMode'] == 1 || Yii::$app->params['siteMode'] == 3) { // sent msg on live mode
                //$user->phone='test';
                if($user->phone) { // sent msg on live mode
                    
                    $phoneInput=[];
                    $phoneInput['countryCode'] = $user->country_code;
                    $phoneInput['phoneNumber'] = $user->phone;

                    $otpString = "Congratulation !! ".$userNameOnTicket."\n Ticket detail :  ".$eventName.",".$eventAddress." ".$eventStartTime."\n Qty. ".$ticketQty." ".$ticketType;
                    
                    
                    $smsData=[];
                    $smsData['message']=$otpString;
                    $smsData['mediaUrl']=$imageUrl;
                    
                    $isSuccess = Yii::$app->sms->sendSms($phoneInput,$smsData);
                   
                    /*
                    $sid = Yii::$app->params['twilioSid'];
                    $tokenTwilio = Yii::$app->params['twilioToken'];
                    $smsFromTwilio = Yii::$app->params['smsFromTwilio'];
                    $twilio = new Client($sid, $tokenTwilio);
                    
                    $toNumber = '+' . $user->country_code . $user->phone;
                    //$toNumber ='+919780696973';
                    $otpString = "Congratulation !! ".$userNameOnTicket."\n Ticket detail :  ".$eventName.",".$eventAddress." ".$eventStartTime."\n Qty. ".$ticketQty." ".$ticketType;

                    $message = $twilio->messages
                        ->create(
                            $toNumber,
                            // to
                            [
                                "from" => $smsFromTwilio, 
                                "body" => $otpString,
                                "mediaUrl" => [$imageUrl]
                            ]
                        );

                        */

                }

                $response['message']=Yii::$app->params['apiMessage']['common']['actionSuccess'];
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }
            
        }
        
    }


    public function actionGiftTicket()
    {
        $userId                 = Yii::$app->user->identity->id;
       
        $model                  =     new EventTicketBooking();
        $modelEventTicket       =   new EventTicket();
        $modelEvent             =   new Event();

        
        
        $modelUser   =   new User();
       // $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='giftTicket';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
            $modelEventTicketBooking = $model->findOne($model->id);
           

              
           if(!$modelEventTicketBooking){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            }

                
            if($modelEventTicketBooking->user_id !=$userId){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;
            }

            if($modelEventTicketBooking->status != EventTicketBooking::STATUS_PURCHASED){
              
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['canNotGift'];
                $response['errors']=$errors;
                return $response;
            }
            if($modelEventTicketBooking->gifted_to){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['alreadyGifted'];
                $response['errors']=$errors;
                return $response;
            }


            $resultEvent = $modelEvent->findOne($modelEventTicketBooking->event_id);
            
            $currentTime=time();

            if($currentTime > $resultEvent->start_date){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['event']['canNotGift'];
                $response['errors']=$errors;
                return $response;
            }
            $giftedToUserId =  $model->gifted_to;

            $modelEventTicketBooking->gifted_to = $model->gifted_to;
            if($modelEventTicketBooking->save()){

                $modelUserReaction       =   new UserReaction();

            

                /// update going conter
                $referenceId        = $modelEventTicketBooking->event_id;
                $type               = $modelUserReaction::TYPE_EVENT;
              
                $reactionResult     = $modelUserReaction->find()->where(['user_id'=>$userId,'type'=>$type,'reference_id'=>$referenceId])->one();

                if(!$reactionResult){
                    $reactionResult = $modelUserReaction;
                }
                $reactionResult->type           =   $modelUserReaction::TYPE_EVENT;
                $reactionResult->reference_id   =    $referenceId;

                $reactionResult->reaction   =   $modelUserReaction::REACTION_INTERESTING;
                $reactionResult->total_item =   $modelEventTicketBooking->ticket_qty;
                $reactionResult->user_id    =   $giftedToUserId;

                $reactionResult->save(false);

                
                $response['message']=Yii::$app->params['apiMessage']['event']['ticketGifted'];
               
                return $response; 


            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;

            }

            
        }

       
        
    }

    public function actionCreateEvent(){
        $model = new Event();
        $userId = Yii::$app->user->identity->id;
        
        $model->scenario ='create';
        
        $modelEventGallaryImage = new EventGallaryImage();
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        // echo "<pre>";
        // print_r($model->gallaryFile);
        // exit("dndkn");
        // $model->user_id = $userId;
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        // $model->imageFile = UploadedFile::getInstanceByName('imageFile'); 
        // $model->gallaryFile = UploadedFile::getInstanceByName('gallaryFile');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }

        if($model->imageFile){
            $model->image 		= 	  $model->imageFile;
        }
        $model->start_date              = strtotime($model->start_date);
        $model->end_date                = strtotime($model->end_date);
        $images =[];
        if($model->gallaryFile){        
            $galleryImg		= 	  $model->gallaryFile;          
            $images = explode(',', $galleryImg);
        }
        $model->created_by_source = Event::EVENT_CREATED_SOURCE_USER;
        if($model->save(false)){     
        if(count($images)>0){
            $modelEventGallaryImage->addPhoto($model->id,$images);
        }

            $response['message']='Event created successfully';
            $response['id'] = $model->id;
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Event not create successfully";
            $response['errors']=$errors;
            return $response; 
        }   
    }

    public function actionUpdate($id){
        // Yii::$app->getRequest()
        $model =  Event::find()->where(['id'=>$id])->one();//new Event();
        $userId = Yii::$app->user->identity->id;
        $model =  $this->findModel($id);
        $model->scenario ='create';
        
        $modelEventGallaryImage = new EventGallaryImage();
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        // $model->imageFile = UploadedFile::getInstanceByName('imageFile'); 
        // $model->gallaryFile = UploadedFile::getInstanceByName('gallaryFile');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }

        if($model->imageFile){
            $model->image 		= 	  $model->imageFile;
        }
        $model->start_date              = strtotime($model->start_date);
        $model->end_date                = strtotime($model->end_date);
        $images =[];
        if($model->gallaryFile){        
            $galleryImg		= 	  $model->gallaryFile;          
            $images = explode(',', $galleryImg);
        }
        $model->created_by_source = Event::EVENT_CREATED_SOURCE_USER;
        if($model->save(false)){     
        if(count($images)>0){
            $modelEventGallaryImage->addPhoto($model->id,$images);
        }

            $response['message']='Event updated successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Event updation failed";
            $response['errors']=$errors;
            return $response; 
        }   
    }

    public function actionCreateTicket(){
        $model = new EventTicket();
        $userId = Yii::$app->user->identity->id;
        
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $model->available_ticket	= $model->limit;
        $model->created_at              = strtotime("now");
       if($model->save(false)){        

            $response['message']='Event ticket created successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Event ticket not create successfully";
            $response['errors']=$errors;
            return $response; 
        }   
    }

    public function actionUpdateTicket(){
         $id=  Yii::$app->request->post('id');
        $model =  EventTicket::find()->where(['id'=>$id])->one();
        // $model = new EventTicket();
        if(empty($model)){
            $response['statusCode']=422;
            $errors['message'][] = "Event ticket id not found";
            $response['errors']=$errors;
            return $response;
        }
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='updateTicket';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $model->available_ticket	= $model->limit;
        $model->updated_at              = strtotime("now");
       if($model->save(false)){        

            $response['message']='Event ticket updated successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Event ticket updation failed";
            $response['errors']=$errors;
            return $response; 
        }   
    }
    
    
    public function actionTicketList(){
        $model =  new EventTicket();
        $modelRes= $model->find()->where(['status'=>EventTicket::STATUS_ACTIVE])->orderBy(['id'=>SORT_DESC])->all();
        $response['message']='ok';
        $response['ticketEventList']=$modelRes;
        return $response;
    }

    public function actionList(){
        $model =  new EventSearch();
        $currentTime = time();
        $modelRes= $model->find()->where(['status'=>Event::STATUS_ACTIVE])->andwhere(['>','event.end_date',$currentTime])->orderBy(['id'=>SORT_DESC])->all();
        $response['message']='ok';
        $response['eventList']=$modelRes;
        return $response;
    }


    
    public function actionCreateCoupon(){
        $model = new EventCoupon();
        $userId = Yii::$app->user->identity->id;
        
        $model->scenario ='create';
        
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }

        if($model->imageFile){
            $model->image 		= 	  $model->imageFile;
        }
        $model->expiry_date              = strtotime($model->expiry_date);
        $model->created_at                = strtotime("now");
        $model->created_by = $userId;
        if($model->save(false)){     
            $response['message']='Event Coupon created successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Event Coupon not create successfully";
            $response['errors']=$errors;
            return $response; 
        }   
    }

    public function actionUpdateCoupon(){
        $id=  Yii::$app->request->post('id');
        $model = EventCoupon::find()->where(['id'=>$id])->one();
        // $model = new EventCoupon();
        if(empty($model)){
            $response['statusCode']=422;
            $errors['message'][] = "Event Coupon id not found";
            $response['errors']=$errors;
            return $response;
        }
        $userId = Yii::$app->user->identity->id;
        
        $model->scenario ='update';
        
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }

        if($model->imageFile){
            $model->image 		= 	  $model->imageFile;
        }
        $model->expiry_date              = strtotime($model->expiry_date);
        $model->updated_at                = strtotime("now");
        $model->updated_by = $userId;
        if($model->save(false)){     
            $response['message']='Event Coupon updated successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Event Coupon not updated successfully";
            $response['errors']=$errors;
            return $response; 
        }   
    }


    protected function findModel($id)
    {
        if (($model = Event::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


