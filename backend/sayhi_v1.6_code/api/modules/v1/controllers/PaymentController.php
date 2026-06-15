<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Ad;
use api\modules\v1\models\AdPackage;
use api\modules\v1\models\AdSubscription;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\Payment;
use api\modules\v1\models\WithdrawalPayment;
use api\modules\v1\models\WithdrawalPaymentSearch;
use api\modules\v1\models\PaymentSearch;
use api\modules\v1\models\Package;
use api\modules\v1\models\User;
use api\modules\v1\models\Setting;
use api\modules\v1\models\Notification;
use api\modules\v1\models\StripePayment;
use api\modules\v1\models\PaypalPayment;

use Braintree;






class PaymentController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\payment';


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
            'except' => [],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }


    public function actionPackageSubscription()
    {

        $model = new Payment();
        $modelPackage = new Package();


        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'packageSubscription';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        //// validation
        if ($model->transaction_id) {
            $ifAlready = $model->find()->where(['transaction_id' => $model->transaction_id])->count();
            if ($ifAlready) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['payment']['paymentAlreadyDone'];
                $response['errors'] = $errors;
                return $response;

            }


        }





        $packageId = @(int) $model->package_id;
        $packageResult = $modelPackage->findOne($packageId);


        $modelUser = User::findOne($userId);
        $modelUser->available_coin = $modelUser->available_coin + $packageResult->coin;


        if ($modelUser->save(false)) {



            $model->type = Payment::TYPE_COIN;
            $model->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
            $model->payment_type = Payment::PAYMENT_TYPE_PACKAGE;
            $model->payment_mode = Payment::PAYMENT_MODE_IN_APP_PURCHASE;
            $model->coin = $packageResult->coin;

            $amount = $model->amount;
            unset($model->amount);


            if ($model->save(false)) {

                $modelPaymentLog = new Payment();

                $modelPaymentLog->type = Payment::TYPE_PRICE;
                $modelPaymentLog->user_id = $model->user_id;
                $modelPaymentLog->package_id = $model->package_id;

                $modelPaymentLog->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;

                $modelPaymentLog->payment_type = Payment::PAYMENT_TYPE_PACKAGE;
                $modelPaymentLog->payment_mode = Payment::PAYMENT_MODE_IN_APP_PURCHASE;
                $modelPaymentLog->transaction_id = $model->transaction_id;
                $modelPaymentLog->amount = $amount;
                $modelPaymentLog->save(false);

                $response['message'] = 'Package subscribed successfully';
                return $response;
            }
        } else {
            $response['statusCode'] = 422;
            $response['message'] = 'Package not subscribed successfully';
            return $response;

        }
    }


    /**payment my history */

    public function actionPaymentHistory()
    {

        $userId = Yii::$app->user->identity->id;
        $modelUser = new User();

        /*$model = new \yii\base\DynamicModel([
            'month', 'string'
             ]);
        $model->addRule(['month'], 'required');
        $model->load(Yii::$app->request->queryParams, '');
        $model->validate();
        if ($model->hasErrors()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            
        }*/

        $modelSearch = new PaymentSearch();
        $result = $modelSearch->searchMyPayment(Yii::$app->request->queryParams);

        //  $resultUser = $modelUser->find()->select(['available_balance','available_coin'])->where(['id'=>$userId])->one();


        $response['message'] = Yii::$app->params['apiMessage']['common']['recordFound'];
        //$response['available_balance']=  $resultUser->available_balance;
        //$response['available_coin']=  $resultUser->available_coin;
        $response['payment'] = $result;

        return $response;
    }



    /**payment withdrawal request hostory */

    public function actionWithdrawalHistory()
    {

        //        $userId = Yii::$app->user->identity->id;
        $modelSearch = new WithdrawalPaymentSearch();
        $result = $modelSearch->searchMyWithdrawalPayment(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['common']['recordFound'];

        $response['payment'] = $result;
        return $response;
    }


    /**payment withdrawal request */

    public function actionWithdrawal()
    {

        $userId = Yii::$app->user->identity->id;
        $modelUser = new User();
        $resultUer = $modelUser->findOne($userId);
        $resultUer->available_balance;
        if ($resultUer->available_balance <= 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['payment']['amountNotAvailable'];
            $response['errors'] = $errors;
            return $response;
        }
        $withdrawalAmount = $resultUer->available_balance;
        $resultUer->available_balance = 0; //$resultUer->available_balance - $model->amount;
        if ($resultUer->save(false)) {
            $modelWithdrawPayment = new WithdrawalPayment();
            $modelWithdrawPayment->user_id = $userId;
            $modelWithdrawPayment->amount = $withdrawalAmount;
            $modelWithdrawPayment->save(false);


            $modelPayment = new Payment();
            $modelPayment->user_id = $userId;
            $modelPayment->type = Payment::TYPE_PRICE;
            $modelPayment->amount = $withdrawalAmount;

            $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;
            $modelPayment->payment_type = Payment::PAYMENT_TYPE_WITHDRAWAL;
            $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $modelPayment->save(false);



            $response['message'] = Yii::$app->params['apiMessage']['payment']['withdrawRequestSuccess'];

        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['payment']['withdrawFailed'];
            $response['errors'] = $errors;
        }


        return $response;
    }



    /**redeem coin */

    public function actionRedeemCoin()
    {


        $userId = Yii::$app->user->identity->id;
        $modelUser = new User();
        $resultUer = $modelUser->findOne($userId);

        $modelSetting = new Setting();
        $modelNotification = new Notification();

        $settingResult = $modelSetting->find()->one();
        $minCoinRedeem = (int) $settingResult->min_coin_redeem;

        $redeemCoin = (int) Yii::$app->getRequest()->getBodyParams()['redeem_coin'];


        if ($resultUer->available_coin < $redeemCoin) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['payment']['coinNotAvailable'];
            $response['errors'] = $errors;
            return $response;
        }

        //$resultUer->available_coin; 
        if ($redeemCoin < $minCoinRedeem) {
            $response['statusCode'] = 422;


            $replaceContent['COIN'] = $minCoinRedeem;
            $message = $modelNotification->replaceContent(Yii::$app->params['apiMessage']['payment']['coinMinRequired'], $replaceContent);

            $errors['message'][] = $message;
            $response['errors'] = $errors;
            return $response;
        }


        $totalPrice = $redeemCoin * $settingResult->per_coin_value;
        $resultUer->available_balance = $resultUer->available_balance + $totalPrice;
        $resultUer->available_coin = $resultUer->available_coin - $redeemCoin;
        if ($resultUer->save(false)) {


            // redeem coin from wallet

            $modelPayment = new Payment();
            $modelPayment->user_id = $userId;
            $modelPayment->type = Payment::TYPE_COIN;
            $modelPayment->coin = $redeemCoin;

            $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;
            $modelPayment->payment_type = Payment::PAYMENT_TYPE_REDEEM_COIN;
            $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $modelPayment->save(false);

            // add price in wallet 

            $modelPayment = new Payment();
            $modelPayment->user_id = $userId;
            $modelPayment->type = Payment::TYPE_PRICE;
            $modelPayment->amount = $totalPrice;

            $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
            $modelPayment->payment_type = Payment::PAYMENT_TYPE_REDEEM_COIN;
            $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $modelPayment->save(false);





            $response['message'] = Yii::$app->params['apiMessage']['payment']['coinRedeemSuccess'];

        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['payment']['coinRedeemFailed'];
            $response['errors'] = $errors;
        }


        return $response;
    }


    public function actionPaymentIntent()
    {
        //  $stripeCustomerId = Yii::$app->user->identity->stripe_customer_id;

        $model = new \yii\base\DynamicModel([
            'amount', 'currency',
        ]);
        $model->addRule(['currency', 'amount'], 'required');
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        $model->validate();
        if ($model->hasErrors()) {

            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;

        }
        //Yii::$app->my->welcome();
        $inputData = [];
        $inputData['amount'] = $model->amount;
        $inputData['currency'] = $model->currency;
        /*$inputData['stripeCustomerId']              =    $stripeCustomerId;
        $inputData['description']                   =    'order payment';
        $inputData['payment_method_types']          =    'card';

        $inputData['shipping_name']                 =    $model->shipping_name;
        $inputData['shipping_address_line1']        =    $model->shipping_address_line1;
        $inputData['shipping_address_postal_code']  =    $model->shipping_address_postal_code;
        $inputData['shipping_address_city']         =    $model->shipping_address_city;
        $inputData['shipping_address_state']         =    $model->shipping_address_state;
        $inputData['shipping_address_country']      =    $model->shipping_address_country;*/


        $stripePayment = new StripePayment();
        $clientSecret = $stripePayment->getPaymentIntend($inputData);
        $response['client_secret'] = $clientSecret;
        $response['publishable_key'] = $stripePayment->publishableKey;
        $response['message'] = 'ok';
        return $response;
    }


    public function actionPaypalClientToken()
    {

        $paypalModel = new PaypalPayment();

        $clientToken = $paypalModel->getClientToken();
        $response['client_token'] = $clientToken;
        $response['message'] = 'ok';
        return $response;
        // return $response; 
    }

    public function actionPaypalPayment()
    {
        $userId = Yii::$app->user->identity->id;
        $modelUser = new User();
        $resultUer = $modelUser->findOne($userId);
        $paypalModel = new PaypalPayment();


        $model = new \yii\base\DynamicModel([
            'amount', 'payment_method_nonce', 'device_data'
        ]);
        $model->addRule(['amount', 'payment_method_nonce', 'device_data'], 'required');
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        $model->validate();
        if ($model->hasErrors()) {

            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;

        }

        $inputData = [];
        $inputData['amount'] = $model->amount;
        $inputData['paymentMethodNonce'] = $model->payment_method_nonce;
        $inputData['deviceData'] = $model->device_data;

        $result = $paypalModel->getMakePayment($inputData);

        if ($result['status'] == 'success') {
            $paymentId = $result['paymentId'];
            $response['payment_id'] = $paymentId;
            $response['message'] = 'ok';
            return $response;

        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = 'Payment not successfully done';
            $response['errors'] = $errors;
            return $response;

        }



    }





    public function actionPaypalPayment_old()
    {
        $userId = Yii::$app->user->identity->id;
        $modelUser = new User();
        $resultUer = $modelUser->findOne($userId);
        $modelSetting = new Setting();


        $model = new \yii\base\DynamicModel([
            'amount', 'payment_method_nonce', 'device_data'
        ]);
        $model->addRule(['amount', 'payment_method_nonce', 'device_data'], 'required');
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        $model->validate();
        if ($model->hasErrors()) {

            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;

        }

        $inputData = [];
        $inputData['amount'] = $model->amount;
        $inputData['paymentMethodNonce'] = $model->payment_method_nonce;
        $inputData['deviceData'] = $model->device_data;


        /*$gateway = new Braintree\Gateway([
            'environment' => 'sandbox',
            'merchantId' => 'v4n2gh2n648g77qt',
            'publicKey' => '4x2kpwm7srr9p2kk',
            'privateKey' => '04d208045cdf31c67e22b8fc1f6ca3bb'
        ]);*/


        $gateway = new Braintree\Gateway([
            'environment' => 'sandbox',
            'merchantId' => '7c9bdgy6qzqnnm4s',
            'publicKey' => 'ndyxfyd7drtpvm6t',
            'privateKey' => '3f0558e35e33a8861088c6bf0932a932'
        ]);

        // echo $model->payment_method_nonce;

        /*$result = $gateway->customer()->create([
         'firstName' => 'Mike',
         'lastName' => 'Jones',
         'company' => 'Jones Co.',
         'email' => 'mike.jones@example.com',
         'phone' => '281.330.8004',
         'fax' => '419.555.1235',
         'website' => 'http://example.com'
     ]);
     
     $result->success;
     # true
     
     echo $result->customer->id;*/


        //  echo $clientToken = $gateway->clientToken()->generate();


        # Generated customer id

        //die;


        $result = $gateway->transaction()->sale([
            'amount' => $model->amount,
            'paymentMethodNonce' => $model->payment_method_nonce,
            'deviceData' => $model->device_data,
            'options' => [
                'submitForSettlement' => True
            ]
        ]);

        if ($result->success) {
            echo 'success';
            //echo $transaction = $result->transaction()->find('the_transaction_id');
            // print_r($result->transaction);

            echo '<br>';
            print_r($result->transaction['paypal']['paymentId']);
            // See $result->transaction for details
        } else {
            echo 'failed';
            // Handle errors
        }

        // print_r($result);


        /*$gateway = new Braintree\Gateway([
            'environment' => 'sandbox',
            'merchantId' => 'v4n2gh2n648g77qt',
            'publicKey' => '4x2kpwm7srr9p2kk',
            'privateKey' => '04d208045cdf31c67e22b8fc1f6ca3bb'
        ]);*/




        // return $response; 
    }

    public function actionAdPackageSubscription()
    {

        $model = new Payment();
        $modelPackage = new AdPackage();
        $modelSubscription = new AdSubscription();


        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'packageAdSubscription';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }
        $packageId = @(int) $model->ad_package_id;
        $packageResult = $modelPackage->findOne($packageId);
        $expiryDate = $modelSubscription->getExpirtyDate($packageResult->term);


        $model->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
        $model->payment_type = Payment::PAYMENT_TYPE_PACKAGE;
        $model->payment_mode = Payment::PAYMENT_MODE_PAYPAL;

        $isFail = false;
        if ($model->save(false)) {


            $modelSubscription->user_id = Yii::$app->user->identity->id;
            $modelSubscription->ad_package_id = $packageId;
            $modelSubscription->title = $packageResult->name;
            $modelSubscription->term = $packageResult->term;
            $modelSubscription->amount = $model->amount;
            $modelSubscription->ad_limit = $packageResult->ad_limit;
            $modelSubscription->ad_remaining = $packageResult->ad_limit;
            $modelSubscription->payment_mode = Payment::PAYMENT_MODE_PAYPAL;
            $modelSubscription->expiry_date = $expiryDate;
            if ($modelSubscription->save(false)) {
                $modelSubscription->updateAll(['status' => AdSubscription::STATUS_EXPIRED], ['and', ['user_id' => $userId], ['<>', 'id', $modelSubscription->id]]);

                $modelUser = User::findOne($userId);
                $modelUser->ad_package_id = $packageId;


                if ($modelUser->save(false)) {
                    $response['message'] = 'Ad Package subscribed successfully';
                    return $response;
                } else {
                    $isFail = true;
                }
            } else {
                $isFail = true;
            }

        } else {
            $isFail = true;
        }
        if ($isFail) {
            $response['statusCode'] = 422;
            $response['message'] = 'Ad Package not subscribed successfully';
            return $response;

        }

    }

    /**
     * featured ad payment
     */
    public function actionFeatureAd()
    {

        $model = new Payment();
        $modelAd = new Ad();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'featureAdPayment';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }



        $model->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
        $model->payment_type = Payment::PAYMENT_TYPE_FEATURE_AD;
        $model->payment_mode = Payment::PAYMENT_MODE_PAYPAL;
        $isFail = false;
        if ($model->save(false)) {

            $adId = @(int) $model->ad_id;



            $modelAd = Ad::findOne($adId);
            $modelAd->featured = Ad::FEATURED_YES;
            $modelAd->featured_exp_date = $modelAd->getAdFeaturedExpiry();

            if ($modelAd->save(false)) {
                $response['message'] = 'Ad marked as Featured successfully';
                return $response;
            } else {
                $isFail = true;
            }

        } else {
            $isFail = true;
        }
        if ($isFail) {
            $response['statusCode'] = 422;
            $response['message'] = 'Ad marked as Featured not successfully done';
            return $response;

        }

    }

    /**
     * featured ad payment
     */
    public function actionBannerAd()
    {

        $model = new Payment();
        $modelAd = new Ad();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'bannerAdPayment';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }
        $model->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
        $model->payment_type = Payment::PAYMENT_TYPE_BANNER_AD;
        $model->payment_mode = Payment::PAYMENT_MODE_IN_APP_PURCHASE;
        $isFail = false;
        if ($model->save(false)) {

            $adId = @(int) $model->ad_id;
            $modelAd = Ad::findOne($adId);
            $modelAd->is_banner_ad = Ad::IS_BANNER_AD_YES;
            $modelAd->package_banner_id = $model->package_banner_id;


            if ($modelAd->save(false)) {
                $response['message'] = 'Ad marked in banner ad successfully';
                return $response;
            } else {
                $isFail = true;
            }

        } else {
            $isFail = true;
        }
        if ($isFail) {
            $response['statusCode'] = 422;
            $response['message'] = 'Ad marked in banner ad not successfully done';
            return $response;

        }

    }

    public function actionSendCoin()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new \yii\base\DynamicModel([
            'coin', 'receiver_id',
        ]);
        $model->addRule(['coin', 'receiver_id'], 'required');
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        $model->validate();
        if ($model->hasErrors()) {

            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;

        }
        $modelUser = new User();

        $resultUser = $modelUser->findOne($userId);


        if ($model->coin > $resultUser->available_coin) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['payment']['notEnoughBalance'];
            $response['errors'] = $errors;
            return $response;

        }
        $isProcess=false;
        $userGetCoin = $model->coin;
        //for sender 
        $resultUser->available_coin = $resultUser->available_coin - $model->coin;
        if ($resultUser->save(false)) {
            $isProcess=true;
            $modelPayment = new Payment();
            $modelPayment->type = Payment::TYPE_COIN;
            $modelPayment->user_id = $userId;
            $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;
            $modelPayment->payment_type = Payment::PAYMENT_TYPE_COIN_TRANSFER;
            $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $modelPayment->coin = $model->coin;
            $modelPayment->detail_reference_id = $model->receiver_id;
            $modelPayment->save(false);

        }
        //for reciever 
        $resultRecieverUser = $modelUser->findOne($model->receiver_id);

        $resultRecieverUser->available_coin = $resultRecieverUser->available_coin + $userGetCoin;
        if ($resultRecieverUser->save(false)) {
            $modelPayment = new Payment();
            $modelPayment->type = Payment::TYPE_COIN;
            $modelPayment->user_id = $model->receiver_id;
            $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
            $modelPayment->payment_type = Payment::PAYMENT_TYPE_COIN_TRANSFER;
            $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $modelPayment->coin = $userGetCoin;
            $modelPayment->detail_reference_id = $userId;
            $modelPayment->save(false);

        }
        // send notification 
        $userIds = [];
        $userIds[] = $model->receiver_id;

        if ($userIds) {
            $modelNotification = new Notification();
            $notificationInput = [];
            $notificationData = Yii::$app->params['pushNotificationMessage']['coinRecieved'];
            $replaceContent = [];

            $replaceContent['COIN'] = $userGetCoin;
            $replaceContent['USERNAME'] = $resultUser->username;
            $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);

            $notificationInput['referenceId'] = $userId;
            $notificationInput['userIds'] = $userIds;
            $notificationInput['notificationData'] = $notificationData;
            $modelNotification->createNotification($notificationInput);
            // end send notification 
        }
        if($isProcess){
            $response['message'] = Yii::$app->params['apiMessage']['payment']['coinSent'];
            return $response;
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
            return $response;
        }
    }

}