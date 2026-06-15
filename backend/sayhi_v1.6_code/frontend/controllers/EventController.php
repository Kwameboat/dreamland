<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;


use common\models\User;
use yii\data\ActiveDataProvider;
use common\models\Post;
use common\models\Setting;
use common\models\Category;

use common\models\Event;
use common\models\EventTicketBooking;
use common\models\EventTicket;
use common\models\EventCoupon;
use common\models\Payment;
use yii\authclient\BaseClient;
use Twilio\Rest\Client;
use Stripe\Stripe;



/**
 * Site controller
 */
class EventController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup', 'share'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }



    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }



    public function beforeAction($action)
    {

        $session = Yii::$app->session;

        return true;

    }

    public function actionIndex($category_id=0)
    {
       
        
        $currentTime = time();

        $modelCategory = new Category();
        $modelEvent = new Event();
        $query = $modelCategory->find()
            ->select(['category.id', 'category.name', 'category.image', 'type'])
            ->joinWith([
                'event' => function ($query) {
                    $currentTime = time();
                    $query->andwhere(['>', 'end_date', $currentTime]);
                }
            ])
            ->where(['category.status' => $modelCategory::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_EVENT])->orderBy(['name' => SORT_ASC]);

        $resultCategory =    $query->all();


        $query = $modelEvent->find()->where(['status' => $modelEvent::STATUS_ACTIVE])
            ->andwhere(['>', 'end_date', $currentTime]);
            if($category_id>0){
                $query->andWhere(['category_id'=>$category_id]);
            }

        $resultEvent = $query->all();
        $modelSetting = new Setting();
        $resultSetting = $modelSetting->getSettingData();


        return $this->render(
            'index',
            [
                'resultCategory' => $resultCategory,
                'resultEvent' => $resultEvent,
                'setting' => $resultSetting,
                'category_id' =>$category_id

            ]
        );
    }
    public function actionView($id)
    {

        try {

            $modelEvent = new Event();
            $modelSetting = new Setting();
            $modelEventCoupon = new EventCoupon();
            
            $resultSetting = $modelSetting->getSettingData();


            $eventResult = $modelEvent->find()->where(['unique_id' => $id])->one();
            if (!$eventResult) {
                throw new BadRequestHttpException('Envalid request');

            }

            
            $currentTime = time();
            $query = $modelEventCoupon->find()->where(['event_coupon.status'=>EventCoupon::STATUS_ACTIVE]);
            $query->andwhere(['>','event_coupon.expiry_date',$currentTime]);
            
            $resultCoupon = $query->all();


            return $this->render('view', [
                'model' => $eventResult,
                'setting' => $resultSetting,
                'resultCoupon'=>$resultCoupon

            ]);

        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }



    }
    



    public function actionCheckout()
    {
       
        try {
           
            $modelEventTicketBooking =   new EventTicketBooking();
            $modelEventTicket       =   new EventTicket();
            $modelEvent             =   new Event();



            if (Yii::$app->request->post()) {
                $postData = Yii::$app->request->post(); // Get all POST data
               
                $currentTime=time();
                $token = $postData["c_tok"];
                $firstName = $postData["first_name"];
                $lastName = $postData["last_name"];
                $email = $postData["email"];
                $countryCode = $postData["country_code"];
                $phone = $postData["phone"];
               
                $eventTicketId = $postData["ticket_id"];
                $ticketQty = $postData["ticket_qty"];
                $amount = $postData["amount"];
                
                $grandAmount = $postData["grand_amount"];
                $couponCode = $postData["coupon_code"];
                $couponDiscount = $postData["coupon_discount"];


               
                if($phone){
                    $phone = str_replace(' ', '', $phone);
                    $phone = str_replace('+', '', $phone);
                    $phone = ltrim($phone, "0");  
                }else{
                    $countryCode=null;
                }
                
        
                if(!$token){
                   // show error no token available
                }
              
                $resultEventTicket       = $modelEventTicket->find()->where(['id'=>$eventTicketId,'status'=>EventTicket::STATUS_ACTIVE])->one();
               // echo '<pre>';
               // print_r($resultEventTicket);
               if(!$resultEventTicket){
                // Yii::$app->params['apiMessage']['common']['noRecord'];
                    Yii::$app->session->setFlash('error', Yii::$app->params['apiMessage']['common']['noRecord']);
                    return $this->redirect(['event']);
                
                }
                $eventId = $resultEventTicket->event_id;
                $resultEvent    = $modelEvent->find()->where(['id'=>$eventId,'status'=>Event::STATUS_ACTIVE])->one();
                
                if(!$resultEvent){
                    Yii::$app->session->setFlash('error', Yii::$app->params['apiMessage']['common']['noRecord']);
                    return $this->redirect(['event']);
                       
                }

                if($currentTime > $resultEvent->end_date){
                   
                   Yii::$app->session->setFlash('error', Yii::$app->params['apiMessage']['event']['eventClosed']);
                   return $this->redirect(['view','id'=>$resultEvent->unique_id]);

                        
                }
                
    
               if($ticketQty > $resultEventTicket->available_ticket){
                    Yii::$app->session->setFlash('error', Yii::$app->params['apiMessage']['event']['seatNotAvailable']);
                    return $this->redirect(['view','id'=>$resultEvent->unique_id]);
                    
                }
               
                $modelEvent = new Event();
                $modelSetting = new Setting();
                $resultSetting = $modelSetting->getSettingData();
                $secretKey = $resultSetting->stripe_secret_key;

                $stripe = new Stripe();
                $stripe->setApiKey($secretKey);
                
                
               try {
                    $amountPayment =  $amount*100;
                    $charge = \Stripe\Charge::create([
                        'amount' => $amountPayment, // Amount in cents
                        'currency' => 'usd',
                         'description' => 'Event ticket bought',
                        'source' => $token,
                    ]);
                     


                    $statusCharge= $charge->status;

                    //$statusCharge='succeededss';
                    if($statusCharge=='succeeded'){

                        


                        //$transactionId = "85897d834234";
                        $transactionId = $charge->id;
                        $modelEventTicketBooking->event_id          = $eventId;
                        $modelEventTicketBooking->event_ticket_id   = $eventTicketId;
                        $modelEventTicketBooking->user_first_name   = $firstName;
                        $modelEventTicketBooking->user_last_name    = $lastName;
                        $modelEventTicketBooking->user_email        = $email;
                        $modelEventTicketBooking->user_country_code      = $countryCode;
                        $modelEventTicketBooking->user_phone        = $phone;
                        $modelEventTicketBooking->ticket_qty        = $ticketQty;
                        $modelEventTicketBooking->ticket_amount     = $amount;
                        $modelEventTicketBooking->paid_amount       = $grandAmount;
                        $modelEventTicketBooking->coupon            = $couponCode;
                        $modelEventTicketBooking->coupon_discount_value   = $couponDiscount;

                        
                        $modelEventTicketBooking->booking_user_type = EventTicketBooking::BOOKING_USER_TYPE_UNREGISTERED;
                        

                        if($modelEventTicketBooking->save()){
                           
                            $resultEventTicket->available_ticket =  $resultEventTicket->available_ticket-$ticketQty;
                            $resultEventTicket->save();

                            $modelPayment                       = new Payment();
                            $modelPayment->type                 =  Payment::TYPE_PRICE;
                            $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                            $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_EVENT_TICKET;
                            $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_STRIPE;
                            $modelPayment->amount               =  $grandAmount;
                            $modelPayment->event_ticket_booking_id           =  $modelEventTicketBooking->id;
                            $modelPayment->transaction_id        =  $transactionId;
                            $modelPayment->save(false);
                        }
                        $eventName  = $modelEventTicketBooking->event->name;
                        $eventAddress = $modelEventTicketBooking->event->address;
                        $eventStartTime =  Yii::$app->formatter->asDateTime($modelEventTicketBooking->event->start_date);
                        $ticketType   = $modelEventTicketBooking->ticketDetail->ticket_type;
                        $userNameOnTicket =  $firstName.' '. $lastName;

                        $eventOrganisor = $resultEvent->eventOrganisor->name;
                        $bookingId = $modelEventTicketBooking->id;
                        $bookingStatus = 'Confirmed';
                        $bookingDate =   Yii::$app->formatter->asDateTime($modelEventTicketBooking->created_at);
                        $bookingTicketType = $ticketQty .' x '. $ticketType;
                        $paidPrice = '$'.$grandAmount;
                       
                       
                        //////////////////////////////////////////////////
                        // Create image resource
                        $eventNameArray =  $modelEvent->breakLine($eventName);
                        $eventAddressArray =  $modelEvent->breakLine($eventAddress);


                        $image = imagecreatefromjpeg("uploads/template/ticket.jpeg");
                        // Allocate colors
                        $white = imagecolorallocate($image, 255, 255, 255);
                        $black = imagecolorallocate($image, 0, 0, 0);
                        $blue = imagecolorallocate($image, 0, 102, 204);

                        // enven name
                    
                        $lineHeight = 22;
                        $startTopPossition = 108;
                        foreach($eventNameArray as $lineText){
                            $lineText= 
                            imagestring($image, 5, 68, $startTopPossition, $lineText, $blue);
                            $startTopPossition= $startTopPossition + $lineHeight;
                        }
                        // date time
                        imagestring($image, 5, 68, 200, $eventStartTime, $blue);

                        // event address
                        $startTopPossition = 290;
                        foreach($eventAddressArray as $lineText){
                            $lineText= 
                            imagestring($image, 5, 68, $startTopPossition, $lineText, $blue);
                            $startTopPossition= $startTopPossition + $lineHeight;
                        }
                        // organisor
                        imagestring($image, 5, 68, 380, $eventOrganisor, $blue);

                        // second block
                        imagestring($image, 5, 250, 512, $bookingId, $blue);
                        imagestring($image, 5, 250, 560, $bookingStatus, $blue);
                        imagestring($image, 5, 250, 612, $bookingDate, $blue);
                        imagestring($image, 5, 250, 660, $bookingTicketType, $blue);
                        imagestring($image, 5, 250, 705, $paidPrice, $blue);

                        // Output image
                //        header("Content-type: image/jpeg");
                        
                        $filename= time().'.jpg';
                        $folderLocation='uploads/temp/';
                        $fileLocation = $folderLocation.$filename;
                        $data['type'] = 2;//copy location
                        $data['sourceFileLocation'] = $fileLocation;
                        if (!file_exists($folderLocation)) {
                            mkdir($folderLocation, 0777, true);
                        }
                        //imagejpeg($image);
                        imagejpeg($image,$fileLocation);

                        ////upload on product storage
                        
                        $imageType =     Yii::$app->fileUpload::TYPE_EVENT;
                        $files = Yii::$app->fileUpload->uploadFile($image,$imageType,false,$data);
                        imagedestroy($image);
                        $imageName  =   $files[0]['file']; 
                        $imageUrl   =   $files[0]['fileUrl']; 
                        
                        $modelEventTicketBooking->image = $imageName;
                        $modelEventTicketBooking->save();

                        if($email){
                            $fromMail = Yii::$app->params['senderEmail'];
                            $fromName = Yii::$app->params['senderName'];
                            $from = array($fromMail => $fromName);
                           
                            
                            Yii::$app->mailer->compose()
                             ->setSubject('Ticket Booking Confirmation')
                             ->setFrom($from)
                             ->setTo($email)
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
                        if($phone) { // sent msg on live mode
                            $phoneInput=[];
                            $phoneInput['countryCode'] = $countryCode;
                            $phoneInput['phoneNumber'] = $phone;
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
                            
                            $toNumber = '+' . $countryCode . $phone;
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


                        Yii::$app->session->setFlash('success',Yii::$app->params['apiMessage']['event']['buyTicketSuccessfully']);
                        return $this->redirect(['index']);


                    }


                   

                    // Payment successful
                    //echo 'Payment successful!';
                } catch (\Stripe\Exception\CardException $e) {
                    // Payment failed
                    Yii::$app->session->setFlash('error',$e->getError()->message);
                    return $this->redirect(['index']);
                    //echo 'Payment failed: ' . $e->getError()->message;
                }

                return $this->render('checkout', [
                    'setting' => $resultSetting

                ]);
            }

        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }



    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionShare($pid)
    {

        try {

            $modelPost = new Post();

            $modelSetting = new Setting();
            $resultSetting = $modelSetting->getSettingData();


            $postResult = $modelPost->find()->where(['unique_id' => $pid])->one();
            if (!$postResult) {
                throw new BadRequestHttpException('Envalid request');

            }

            return $this->render('share', [
                'postResult' => $postResult,
                'setting' => $resultSetting

            ]);

        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }



    }

}