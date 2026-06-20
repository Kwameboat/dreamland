<?php
namespace api\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use api\modules\v1\models\User;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use yii\imagine\Image;
use api\modules\v1\models\Payment;
use api\modules\v1\models\Package;
use api\modules\v1\models\Subscription;
use api\modules\v1\models\ReportedUser;
use api\modules\v1\models\Follower;
use api\modules\v1\models\UserSearch;
use api\modules\v1\models\BlockedUser;

use api\modules\v1\models\UserSetting;
use api\modules\v1\models\UserLanguage;
use api\modules\v1\models\UserInterest;
use api\modules\v1\models\ProfileView;
use api\modules\v1\models\Post;
use api\modules\v1\models\Story;
use api\modules\v1\models\Audio;
use api\modules\v1\models\Country;
use api\modules\v1\models\State;
use api\modules\v1\models\City;
use api\modules\v1\models\UserLoginLog;
use api\modules\v1\models\BlockedIp;

//use Twilio\Rest\Client;


/**
 * User Controller API
 *

 */
class UserController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\user';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function actions()
    {

        //$headers = apache_request_headers();
        /*$headers =Yii::$app->request->headers;
        print_r($headers);
        die;*/


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
            'only' => ['profile', 'view', 'profile-update', 'update-token', 'update-location', 'logout', 'update-password', 'update-payment-detail', 'update-profile-image', 'nearest-user', 'update-mobile', 'verify-otp', 'search-user', 'find-friend', 'report-user', 'sugested-user', 'push-notification-status', 'delete-account', 'add-setting', 'profile-visibility', 'update-profile-cover-image', 'view-counter','agent','show-chat-online-status' ],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }
    /**
     * Login user
     */
    public function actionLogin()
    {

        $model          = new User();
        $model->scenario = 'login';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        // check blocked ip        
        $loginIp = @trim($params['login_ip']);
        if (BlockedIp::isIpBlocked($loginIp)) {
            $response['statusCode'] = 401;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['blocked'];
            $response['errors'] = $errors;
            return $response;
        }
        // end check blocked ip


        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        

        $user = $model->checkLogin();
        if ($user) {


            if ($user->is_email_verified == User::IS_EMAIL_VERIFIED_YES) {

                /*$response['statusCode']=401;
                 $errors['message'][] = "You have not verfified your email, Please verified your and setup you new password from forgot password";
                 $response['errors']=$errors;
                 //$response['message']='Email/password incorrect';
                 return $response;
                 }*/
                $authKey = Yii::$app->security->generateRandomString();
                $user->auth_key = $authKey;
                $user->last_active = time();
                $user->device_token = $params['device_token'] ?? '';
                $user->device_type = $params['device_type'];
                $user->device_token_voip_ios = $params['device_token_voip_ios'] ?? null;


                if ($user->save(false)) {
                    
                    $loginMode =  UserLoginLog::LOGIN_MODE_MANUALLY;
                    //region user login log
                    $modelUserLoginLog = new UserLoginLog();
                    $modelUserLoginLog->attributes = $params;
                    $modelUserLoginLog->user_id =$user->id;
                    $modelUserLoginLog->login_mode = $loginMode;
                    $modelUserLoginLog->save(false);
                    //regionend

                    $userProfile = $model->getProfile($user->id);
                    $response['message'] = 'Looged in successfully';
                    $response['user'] = $userProfile;
                    $response['auth_key'] = $userProfile->auth_key;
                    return $response;

                }

            } else {


                
                $otp = mt_rand(100000, 999999);
                $token = md5(time() . rand(10, 100));
                $expirytTime = time() + 900;
                $token = $token . '-1';
                $token = $token . '_' . $expirytTime;
                $user->password_reset_token = $token;
                $user->verification_with = User::VERIFICATION_WITH_EMAIL;
                /*if(Yii::$app->params['siteMode']==2){
                $otp=Yii::$app->params['testOtp'];
                }*/
                $user->verification_token = $otp;

                if ($user->save(false)) {

                    //$from = Yii::$app->params['senderEmail'];
                    $fromMail = Yii::$app->params['senderEmail'];
                    $fromName = Yii::$app->params['senderName'];
                    $from = array($fromMail => $fromName);

                    Yii::$app->mailer->compose('register', [
                            'username' => $user->username,
                            'otp' => $otp
                        ])
                        ->setSubject('Registration confirmation')
                        ->setFrom($from)
                        ->setTo($user->email)
                        //->setHtmlBody('Hi ' . $user->username . '<br>Please use following OTP Code confirm you registration.<br> OTP Code is : ' . $otp)
                        ->send();

                    $response['statusCode'] = 401;
                    $errors['message'][] = Yii::$app->params['apiMessage']['user']['emailNotVerified'];
                    $response['token'] = $token;
                    $response['errors'] = $errors;
                    return $response;
                }

            }


        } else {
            $response['statusCode'] = 401;
            $errors['message'][] = "Email/password incorrect";
            $response['errors'] = $errors;
            //$response['message']='Email/password incorrect';
            return $response;

        }


    }
    /**
     * logout
     */

    public function actionLogout()
    {

        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);



        $model->auth_key = NULL;
        $model->device_token = NULL;
        $model->device_token_voip_ios = NULL;
        $model->is_chat_user_online = 0;


        if ($model->save(false)) {


            $response['message'] = 'User logout successfully';
            return $response;

        }

    }

    /**
     * Login user
     */
    public function actionLoginSocial()
    {

        $model = new User();
        $model->scenario = 'loginSocial';
        $request = Yii::$app->request;
        $params = $request->bodyParams;

        // check blocked ip        
        $loginIp = @trim($params['login_ip']);
        if (BlockedIp::isIpBlocked($loginIp)) {
            $response['statusCode'] = 401;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['blocked'];
            $response['errors'] = $errors;
            return $response;
        }
        // end check blocked ip


        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

      
        $users = $model->checkLoginSocail($params);

       
        if (isset($users['emailAlreadyExist'])) {
            $response['statusCode'] = 422;
            $errors['message'][] = "User already register with this email";
            $response['errors'] = $errors;
            return $response;
        }

        if (isset($users['usernameAlreadyExist'])) {
            $response['statusCode'] = 422;
            $errors['message'][] = "Username already exist";
            $response['errors'] = $errors;
            return $response;
        }


        $user = $users['users_details'];



        if (isset($users['login_first_time'])) {
            $loginTime = 1;
        } else {
            $loginTime = 0;
        }
        if ($user) {


            if ($user->status != User::STATUS_ACTIVE) {
                $response['statusCode'] = 422;
                $errors['message'][] = "Please contact to admin";
                $response['errors'] = $errors;
                return $response;
            }

            $authKey = Yii::$app->security->generateRandomString();
            $user->auth_key = $authKey;
            $user->last_active = time();
            $user->device_token = $params['device_token'] ?? '';
            $user->device_type = $params['device_type'];
            $user->device_token_voip_ios = $params['device_token_voip_ios'] ?? null;
            $user->is_login_first_time = $loginTime; // if user login more than one time 


            if ($user->save(false)) {
                
                $loginMode =  UserLoginLog::LOGIN_MODE_SOCIAL;
                //region user login log
                $modelUserLoginLog = new UserLoginLog();
                $modelUserLoginLog->attributes = $params;
                $modelUserLoginLog->user_id =$user->id;
                $modelUserLoginLog->login_mode = $loginMode;
                $modelUserLoginLog->save(false);
                //regionend
             

                $userProfile = $model->getProfile($user->id);

                $response['message'] = 'Looged in successfully';
                $response['user'] = $userProfile;
                $response['auth_key'] = $userProfile->auth_key;
                $response['is_login_first_time'] = $userProfile->is_login_first_time;
                return $response;

            }



        } else {
            $response['statusCode'] = 401;
            $errors['message'][] = "Something is wrong to login";
            $response['errors'] = $errors;
            return $response;

        }


    }

    /**
     * Register user
     */
    public function actionRegister()
    {
        $model = new User();
        $modelPackage = new Package();
        $model->scenario = 'register';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        //$model->country_code =   $model->getValidPhoneCode($model->country_code);
        //$model->phone =  $model->getValidPhone($model->phone);

        // check blocked ip        
        $loginIp = @trim($params['login_ip']);
        if (BlockedIp::isIpBlocked($loginIp)) {
            $response['statusCode'] = 401;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['blocked'];
            $response['errors'] = $errors;
            return $response;
        }
        // end check blocked ip
        
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        
       // $model->role = $model::ROLE_CUSTOMER;
        $model->status = $model::STATUS_ACTIVE;
        if($model->role == $model::ROLE_AGENT){
            $model->status = $model::STATUS_PENDING;
        }
        //   $model->name =  'Guest';
        $defaultPackage = $modelPackage->getDefaultPackage();
        if ($defaultPackage) {
            $model->available_coin = $defaultPackage->coin;
        }

        
        $otp = mt_rand(100000, 999999);
        $token = md5(time() . rand(10, 100));
        $expirytTime = time() + 900;
        $token = $token . '-1';
        $token = $token . '_' . $expirytTime;
        $model->password_reset_token = $token;

        /*if(Yii::$app->params['siteMode']==2){
          $otp=Yii::$app->params['testOtp'];
        }*/

        $model->verification_token = $otp;

        

        if ($model->save()) {

            //$from = Yii::$app->params['senderEmail'];
            $fromMail = Yii::$app->params['senderEmail'];
            $fromName = Yii::$app->params['senderName'];
            $from = array($fromMail => $fromName);
           
            Yii::$app->mailer->compose('register', [
                    'username' => $model->username,
                    'otp' => $otp
                ])
                ->setSubject('Registration confirmation')
                ->setFrom($from)
                ->setTo($model->email)
                ->send();

            $response['message'] = 'Your have registeted successfully, Please verified you email to complete the registration.';
            $response['token'] = $token;
     
            return $response;

           

        }else{
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response; 
        }
    }

    /**
     * delete account
     */

    public function actionDeleteAccount()
    {

        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);



        $model->status = $model::STATUS_DELETED;
        $model->auth_key = NULL;

        if ($model->save(false)) {


            $response['message'] = 'Account deleted successfully';
            return $response;

        }

    }


    public function actionCheckUsername()
    {
        $model = new User();


        $model->scenario = 'checkUsername';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;

        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        } else {
            $response['message'] = 'Username available';
            return $response;

        }





    }

    /**
     * verify registration OTP
     */
    public function actionVerifyRegistrationOtp()
    {
        //$id = Yii::$app->user->identity->id;
        $model = new User();
        $model->scenario = 'verifyRegistrationOtp';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
       
        $tokenExprity = @explode('_', $model->token)[1];
        $tokenStr = @explode('_', $model->token)[0];
        $validatingType = @explode('-', $tokenStr)[1];


        if (time() > $tokenExprity) {
            $response['statusCode'] = 422;
            $errors['message'][] = 'Your token has been expired';
            $response['errors'] = $errors;
            return $response;
        }

        $user = $model->find()->where(['password_reset_token' => $model->token, 'verification_token' => $model->otp, 'status' => [User::STATUS_ACTIVE,User::STATUS_PENDING]])->one();
        if ($user) {

            $authKey = Yii::$app->security->generateRandomString();
            $user->auth_key = $authKey;

            $user->password_reset_token = null;
            $user->verification_token = null;
            $loginMode = UserLoginLog::LOGIN_MODE_MANUALLY;
            if ($validatingType == 2) {
                $user->is_phone_verified = $model::IS_PHONE_VERIFIED_YES;
                $loginMode = UserLoginLog::LOGIN_MODE_PHONE_NUMBER;
            } else {
                $user->is_email_verified = $model::IS_EMAIL_VERIFIED_YES;
            }
            if ($user->save(false)) {

                //region user login log
                $modelUserLoginLog = new UserLoginLog();
                $modelUserLoginLog->attributes = $params;
                $modelUserLoginLog->user_id = $user->id;
                $modelUserLoginLog->login_mode = $loginMode;

                $modelUserLoginLog->save(false);
                //regionend
               
                $userProfile = $model->getProfile($user->id);
                //echo $userProfile->auth_key;
                if($userProfile->status == User::STATUS_PENDING){
                   // $response['statusCode'] = 422;
                   $response['message'] = Yii::$app->params['apiMessage']['user']['adminApprovalPending'];
                   return $response;

                }else{
                    $userProfile->auth_key = $userProfile->auth_key;
                    $response['message'] = 'Looged in successfully';
                    $response['user'] = $userProfile;
                    $response['auth_key'] = $userProfile->auth_key;
                    return $response;
                }
                

            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = 'Verification process failed';
            $response['errors'] = $errors;
            return $response;
        }
    }




    /**
     * Forgot password request
     */
    public function actionForgotPasswordRequest()
    {

        $model = new User();
        $model->scenario = 'forgotPassword';

        $request = Yii::$app->request;

        $params = $request->bodyParams;

        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $verification_with = $params['verification_with'];
        $user = [];
        if ($verification_with == User::VERIFICATION_WITH_EMAIL) { // email
            $user = $model->find()->where(['email' => $params['email'], 'status' => User::STATUS_ACTIVE])->one();
        } else if ($verification_with == User::VERIFICATION_WITH_PHONE) { // phone number
            $user = $model->find()->where(['country_code' => $params['country_code'], 'phone' => $params['phone'], 'status' => User::STATUS_ACTIVE])->one();
        }

        if ($user) {

            $otp = mt_rand(100000, 999999);
            ///$user->password_hash = Yii::$app->security->generatePasswordHash($);
            $token = md5(time() . rand(10, 100));

            $expirytTime = time() + 900;
            $token = $token . '_' . $expirytTime;
            $user->password_reset_token = $token;

            if ((Yii::$app->params['siteMode'] == 2) && $verification_with == User::VERIFICATION_WITH_PHONE) { // test mode and with mobile number verification
                $otp = Yii::$app->params['testOtp'];
            }

            $user->verification_token = $otp;
            $user->verification_with = $verification_with;

            if ($user->save(false)) {

                if ($verification_with == User::VERIFICATION_WITH_EMAIL) { // email
                    $fromMail = Yii::$app->params['senderEmail'];
                    $fromName = Yii::$app->params['senderName'];
                    $from = array($fromMail => $fromName);
                     Yii::$app->mailer->compose('password_forgot', [
                            'username' => $user->username,
                            'otp' => $otp
                        ])
                        ->setSubject('Passowrd Reset')
                        ->setFrom($from)
                        ->setTo($user->email)
                       // ->setHtmlBody($htmlBody)
                       ->send();
                       
                    $response['message'] = Yii::$app->params['apiMessage']['user']['sentEmailForgotPassword'];
                    $response['token'] = $token;
                    return $response;

                } else if ($verification_with == User::VERIFICATION_WITH_PHONE) { // phone number

                    /*if (Yii::$app->params['siteMode'] == 1 || Yii::$app->params['siteMode'] == 3) { // sent msg on live mode
                        
                        $sid = Yii::$app->params['twilioSid'];
                        $tokenTwilio = Yii::$app->params['twilioToken'];
                        $smsFromTwilio = Yii::$app->params['smsFromTwilio'];
                        $twilio = new Client($sid, $tokenTwilio);

                        $toNumber = '+' . $params['country_code'] . $params['phone'];

                        $otpString = "OTP:" . $otp;
                        $message = $twilio->messages
                            ->create(
                                $toNumber,
                                // to
                                ["from" => $smsFromTwilio, "body" => $otpString]
                            );
                        if ($message->sid) {

                            $response['message'] = "OTP has been sent on your mobile for confirmation.";
                            $response['token'] = $token;
                            return $response;

                        } else {

                            $response['statusCode'] = 422;
                            $errors['message'][] = "Sending otp is failed, Please try again";
                            $response['errors'] = $errors;
                            return $response;

                        }

                    } else {
                        $response['message'] = "OTP has been sent on your mobile for confirmation.";
                        $response['token'] = $token;
                        return $response;
                    }*/


                    $phoneInput=[];
                    $phoneInput['countryCode'] = $params['country_code'];
                    $phoneInput['phoneNumber'] = $params['phone'];
                    
                    $otpString = "Verification OTP : " . $otp;
                    $smsData=[];
                    $smsData['message']=$otpString;
                    
                    $isSuccess = Yii::$app->sms->sendSms($phoneInput,$smsData);
                    $otpPublic = '';
                
                    if($isSuccess){
                        if(Yii::$app->params['siteMode'] != 1) {
                            $otpPublic = $otp;
                        }
                    
                        $response['message'] =  "OTP has been sent on your mobile for confirmation.";
                        $response['token'] = $token;
                        $response['otp'] = $otpPublic;
                        return $response;

                    } else {

                        $response['statusCode'] = 422;
                        $errors['message'][] = "Sending otp is failed, Please try again";
                        $response['errors'] = $errors;
                        return $response;

                    }

                    

                }

            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['notRegisterWithUs'];
            $response['errors'] = $errors;
            return $response;

        }
    }

    /**
     * Resend OTP
     */
    public function actionResendOtp()
    {
        $model = new User();
        $model->scenario = 'resendOtp';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        $tokenExprity = @explode('_', $model->token)[1];
        if (time() > $tokenExprity) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['tokenExpired'];
            $response['errors'] = $errors;
            return $response;
        }

        $user = $model->find()->where(['password_reset_token' => $model->token, 'status' => User::STATUS_ACTIVE])->one();
        if ($user) {
            $otp = $user->verification_token;
            if ($user->verification_with == User::VERIFICATION_WITH_EMAIL) { // email
                $fromMail = Yii::$app->params['senderEmail'];
                $fromName = Yii::$app->params['senderName'];
                $from = array($fromMail => $fromName);
                Yii::$app->mailer->compose('resend_otp', [
                            'username' => $user->username,
                            'otp' => $otp
                        ])
                    ->setSubject('One Time OTP')
                    ->setFrom($from)
                    ->setTo($user->email)
                    ->send();
                $response['message'] = Yii::$app->params['apiMessage']['user']['otpSentEamilSuccess'];
                return $response;

            } else if ($user->verification_with == User::VERIFICATION_WITH_PHONE) { // phone number
                
                $phoneInput=[];
                $phoneInput['countryCode'] = $user->country_code;
                $phoneInput['phoneNumber'] = $user->phone;
                
                $otpString = "Verification OTP : " . $otp;
                $smsData=[];
                $smsData['message']=$otpString;

                $isSuccess = Yii::$app->sms->sendSms($phoneInput,$smsData);
            
                if($isSuccess){
                    $response['message'] = Yii::$app->params['apiMessage']['user']['otpSentMobileSuccess'];
                    return $response;
                } else {

                    $response['statusCode'] = 422;
                    $errors['message'][] = "Sending otp is failed, Please try again";
                    $response['errors'] = $errors;
                    return $response;

                }
                

                /*if (Yii::$app->params['siteMode'] == 1 || Yii::$app->params['siteMode'] == 3) { // sent msg on live mode

                    $sid = Yii::$app->params['twilioSid'];
                    $token = Yii::$app->params['twilioToken'];
                    $smsFromTwilio = Yii::$app->params['smsFromTwilio'];
                    $twilio = new Client($sid, $token);

                    $toNumber = '+' . $user->country_code . $user->phone;

                    $otpString = "OTP:" . $otp;
                    $message = $twilio->messages
                        ->create(
                            $toNumber,
                            // to
                            ["from" => $smsFromTwilio, "body" => $otpString]
                        );
                    if ($message->sid) {


                        $response['message'] = Yii::$app->params['apiMessage']['user']['otpSentMobileSuccess'];
                        return $response;

                    } else {

                        $response['statusCode'] = 422;
                        $errors['message'][] = "Sending otp is failed, Please try again";
                        $response['errors'] = $errors;
                        return $response;

                    }

                } else {
                    $response['message'] = Yii::$app->params['apiMessage']['user']['otpSentMobileSuccess'];
                    return $response;
                }*/
            }


        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;

        }
    }


    /**
     * Forgot password request verify OTP
     */
    public function actionForgotPasswordVerifyOtp()
    {
        $model = new User();
        $model->scenario = 'forgotPasswordVerifyOtp';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        $tokenExprity = @explode('_', $model->token)[1];
        if (time() > $tokenExprity) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['tokenExpired'];
            $response['errors'] = $errors;
            return $response;
        }

        $user = $model->find()->where(['password_reset_token' => $model->token, 'verification_token' => $model->otp, 'status' => User::STATUS_ACTIVE])->one();
        if ($user) {


            
            $otp = mt_rand(100000, 999999);
            $token = md5(time() . rand(10, 100));
            $expirytTime = time() + 900;
            $token = $token . '_' . $expirytTime;
            $user->password_reset_token = $token;
            $user->verification_token = null;

            if ($user->save(false)) {
                $response['message'] = Yii::$app->params['apiMessage']['user']['optVerifyToChangePassword'];
                $response['token'] = $token;
                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['optVerifyFailed'];
            $response['errors'] = $errors;
            return $response;

        }
    }

    /**
     * Forgot password set NEw password
     */
    public function actionSetNewPassword()
    {
        $model = new User();
        $model->scenario = 'forgotPasswordNewPassword';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        $tokenExprity = @explode('_', $model->token)[1];
        if (time() > $tokenExprity) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['tokenExpired'];
            $response['errors'] = $errors;
            return $response;
        }

        $user = $model->find()->where(['password_reset_token' => $model->token, 'status' => User::STATUS_ACTIVE])->one();
        if ($user) {

            $user->password_hash = Yii::$app->security->generatePasswordHash($model->password);
            $user->auth_key = null;
            $user->password_reset_token = null;
            if ($user->verification_with == User::VERIFICATION_WITH_EMAIL) { // email
                $user->is_email_verified = User::IS_EMAIL_VERIFIED_YES;
            }
            $user->verification_with = null;
            //  $user->last_password_updated_at = time();

            if ($user->save(false)) {
                $response['message'] = Yii::$app->params['apiMessage']['user']['passwordChanged'];
                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;

        }
    }


    /**
     * my Profile
     *      */
    public function actionProfile()
    {

        $model = new User();
        $modelPackage = new Package();
        $id = Yii::$app->user->identity->id;
        $userProfile = $model->getFullProfileMy($id);
        //$response['user']= $userProfile;
        $response['user'] = $userProfile;
        return $response;


    }


    /**
     * user Profile 
     */
    public function actionView($id)
    {
        $model = new User();
        $modelPackage = new Package();
        $userProfile = $model->getFullProfile($id);

        $response['user'] = $userProfile;
        return $response;


    }


    /**
     * Profile user
     */
    public function actionProfileUpdate()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        $model->scenario = 'profileUpdate';    
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
 
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        if ($model->save(false)) {
            if ($model->language_id) {
                try {
                    $modelUserLanguage = new UserLanguage();
                    $modelUserLanguage->updateUserLanguage($id, $model->language_id);
                } catch (\Throwable $e) {
                    Yii::warning('Profile language update skipped: ' . $e->getMessage(), __METHOD__);
                }
            }
            if ($model->interest_id) {
                try {
                    $modelUserInterest = new UserInterest();
                    $modelUserInterest->updateUserInterest($id, $model->interest_id);
                } catch (\Throwable $e) {
                    Yii::warning('Profile interest update skipped: ' . $e->getMessage(), __METHOD__);
                }
            }
          

            $response['message'] = 'Profile Updated successfully';
            return $response;

        }

        return [
            'statusCode' => 422,
            'message' => 'Could not save profile.',
        ];

    }


    /**
     * udate user device tokens
     */
    public function actionUpdateToken()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);


        $model->load(Yii::$app->getRequest()->getBodyParams(), '');


        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        if ($model->save(false)) {


            $response['message'] = 'Device token Updated successfully';
            return $response;

        }

    }

    /**
     * udate user location
     */
    public function actionUpdateLocation()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);


        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        $params = Yii::$app->getRequest()->getBodyParams();
        $country = @$params['country'];
        $state = @$params['state'];
        $city = @$params['city'];

        if ($country) {
            $countryDetails = Country::find()->where(['name' => $country])->one();
            if ($countryDetails) {
                $model->country_id = $countryDetails->id;

            } else {
                // country id is not set auto increment then i am using this method
                $lastCountryData = Country::find()->orderBy(["country.id" => SORT_DESC])->one();

                $lastID = $lastCountryData->id + 1; // last id + 1
                Yii::$app->db
                    ->createCommand()
                    ->insert('country', ['id' => $lastID, 'name' => $country])
                    ->execute();
                $lastInsertedData = Country::find()->where(['name' => $country])->orderBy(["country.id" => SORT_DESC])->one();
                $model->country_id = $lastInsertedData->id;

            }

        }

        if ($state) {
            $stateDetails = State::find()->where(['name' => $state])->one();
            if ($stateDetails) {
                $model->state_id = $stateDetails->id;

            } else {
                // country id is not set auto increment then i am using this method
                $laststateData = State::find()->orderBy(["state.id" => SORT_DESC])->one();

                $lastID = $laststateData->id + 1; // last id + 1
                Yii::$app->db
                    ->createCommand()
                    ->insert('state', ['id' => $lastID, 'name' => $state, 'country_id' => $model->country_id])
                    ->execute();
                $stateInsertedData = State::find()->where(['name' => $state])->orderBy(["state.id" => SORT_DESC])->one();
                $model->state_id = $stateInsertedData->id;

            }
        }

        if ($city) {
            $cityDetails = City::find()->where(['name' => $city])->one();
            if ($cityDetails) {
                $model->city_id = $cityDetails->id;

            } else {
                // country id is not set auto increment then i am using this method
                $lastCityData = City::find()->orderBy(["city.id" => SORT_DESC])->one();

                $lastID = $lastCityData->id + 1; // last id + 1
                Yii::$app->db
                    ->createCommand()
                    ->insert('city', ['id' => $lastID, 'name' => $city, 'state_id' => $model->state_id, 'country_id' => $model->country_id])
                    ->execute();
                $cityInsertedData = City::find()->where(['name' => $city])->orderBy(["city.id" => SORT_DESC])->one();
                $model->city_id = $cityInsertedData->id;

            }
        }


        // exit;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        if (!$model->latitude) {
            $model->location = null;
            $model->latitude = null;
            $model->longitude = null;
        }
        if ($model->save(false)) {


            $response['message'] = 'User location updated successfully';
            return $response;

        }

    }








    /**
     * Profile user
     */
    public function actionPushNotificationStatus()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);

        $model->scenario = 'pushNotificationStatusUpdate';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');


        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        if ($model->save(false)) {

            $response['message'] = 'Notification status Updated successfully';
            return $response;

        }

    }



    /**
     * Profile user
     */
    public function actionUpdatePaymentDetail()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);

        $model->scenario = 'profilePaymentDetail';

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        if ($model->save(false)) {

            $response['message'] = 'Payment detail Updated successfully';
            return $response;

        }

    }




    /**
     * update mobile
     */
    public function actionUpdateMobile()
    {
        $id = Yii::$app->user->identity->id;
        $model = new User();
        $model->scenario = 'updateMobile';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $string = $params['phone'] . '#' . $params['country_code'];

        $token = base64_encode($string);
        $otp = mt_rand(100000, 999999);
        if (Yii::$app->params['siteMode'] == 2) { // test mode and with mobile number verification
            $otp = Yii::$app->params['testOtp'];
        }
        $modelRes = $this->findModel($id);
        $modelRes->verification_token = $otp;

        if ($modelRes->save(false)) {
            //$token1=  base64_decode($token);


            //=============
            
            $phoneInput=[];
            $phoneInput['countryCode'] = $params['country_code'];
            $phoneInput['phoneNumber'] = $params['phone'];
            
            $otpString = "Verification OTP : " . $otp;
            $smsData=[];
            $smsData['message']=$otpString;

            $isSuccess = Yii::$app->sms->sendSms($phoneInput,$smsData);
            $otpPublic = '';
        
            if($isSuccess){
                if(Yii::$app->params['siteMode'] != 1) {
                    $otpPublic = $otp;
                }
            
                $response['message'] = "OTP has been sent on your mobile for confirmation.";
                $response['verify_token'] = $token;
                $response['otp'] = $otpPublic;
                return $response;

            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = "Sending otp is failed, Please try again";
                $response['errors'] = $errors;
                return $response;

            }

            /*
            if (Yii::$app->params['siteMode'] == 1 || Yii::$app->params['siteMode'] == 3) { // sent msg on live mode

                $sid = Yii::$app->params['twilioSid'];
                $tokenTwilio = Yii::$app->params['twilioToken'];
                $smsFromTwilio = Yii::$app->params['smsFromTwilio'];
                $twilio = new Client($sid, $tokenTwilio);

                $toNumber = '+' . $params['country_code'] . $params['phone'];

                $otpString = "OTP:" . $otp;
                $message = $twilio->messages
                    ->create(
                        $toNumber,
                        // to
                        ["from" => $smsFromTwilio, "body" => $otpString]
                    );
                if ($message->sid) {

                    $response['message'] = "OTP has been sent on your mobile for confirmation.";
                    $response['verify_token'] = $token;
                    return $response;

                } else {

                    $response['statusCode'] = 422;
                    $errors['message'][] = "Sending otp is failed, Please try again";
                    $response['errors'] = $errors;
                    return $response;

                }

            } else {
                $response['message'] = "OTP has been sent on your mobile for confirmation.";
                $response['verify_token'] = $token;
                return $response;
            }*/


        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;

        }

    }




    /**
     * update mobile without verifcation
     */
    public function actionChangeMobile()
    {
        $id = Yii::$app->user->identity->id;
        $model = new User();
        $model->scenario = 'updateMobile';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $string = $params['phone'] . '#' . $params['country_code'];

        $token = base64_encode($string);
        $otp = mt_rand(100000, 999999);

        $modelRes = $this->findModel($id);
        $modelRes->phone = $params['phone'];
        $modelRes->country_code = $params['country_code'];
        if ($modelRes->save(false)) {
            $response['message'] = "Mobile number has been updated successfully";
            return $response;

        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = "Process failed, Please try again";
            $response['errors'] = $errors;
            return $response;
        }

    }

    /**
     * update mobile and verify
     */
    public function actionVerifyOtp()
    {



        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        $model->scenario = 'verifyMobileOtp';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $verify_token = $params['verify_token'];
        $token_arr = explode('#', base64_decode($verify_token));


        $model->verification_token = null;
        $model->phone = $token_arr[0];
        $model->country_code = $token_arr[1];
        $model->is_phone_verified = 1;


        if ($model->save(false)) {
            $response['message'] = "Phone has been updated successfully";
            return $response;
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = "Phone update process failed";
            $response['errors'] = $errors;
            return $response;
        }

    }

    /**
     * update password
     */
    public function actionUpdatePassword()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        $model->scenario = 'changePassword';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        $password = $params['password'];
        $oldPassword = $params['old_password'];
        if (!$model->validatePassword($oldPassword, $model->password_hash)) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['oldPasswordIncorrect'];
            $response['errors'] = $errors;
            return $response;
        }

        $model->password_hash = Yii::$app->security->generatePasswordHash($password);
        //$model->last_password_updated_at = time();
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['user']['passwordChanged'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;

        }

    }

    /**
     * update  Profile image user
     */
    public function actionUpdateProfileImage()
    {
        try {
            $id = Yii::$app->user->identity->id;
            $model = $this->findModel($id);

            $model->scenario = 'updateProfileImage';

            if (!Yii::$app->request->isPost) {
                return ['statusCode' => 405, 'message' => 'Method not allowed.'];
            }

            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->imageFile = UploadedFile::getInstanceByName('imageFile');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            if (!$model->imageFile) {
                return ['statusCode' => 422, 'message' => 'imageFile is required.'];
            }

            $type = Yii::$app->fileUpload::TYPE_USER;
            $files = Yii::$app->fileUpload->uploadFile($model->imageFile, $type, false);
            if (empty($files[0]['file'])) {
                return [
                    'statusCode' => 422,
                    'message' => !empty($files[0]['isProhabited'])
                        ? 'Photo was blocked by content moderation.'
                        : 'Could not save profile photo — storage may be misconfigured.',
                ];
            }

            $model->image = $files[0]['file'];

            if ($model->save(false)) {
                return ['message' => 'Profile image updated successfully'];
            }

            return ['statusCode' => 422, 'message' => 'Could not update profile image.'];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'statusCode' => 500,
                'message' => 'Could not upload profile photo. ' . $e->getMessage(),
            ];
        }
    }

    /**
     * nearest user
     */
    public function actionNearestUser()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new User();
        $model = $this->findModel($userId);
        $location = $model->userLocation;
        $cityIds = [];
        foreach ($model->userLocation as $location) {
            $cityIds[] = $location->city_id;
        }
        $cityIds = array_unique($cityIds);

        $user = $model->find()
            ->innerJoinWith('userLocation')
            ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.description', 'user.phone', 'user.image'])
            ->where(['user_location.city_id' => $cityIds, 'user.status' => User::STATUS_ACTIVE, 'user.role' => User::ROLE_CUSTOMER])
            ->andWhere(['<>', 'user.id', $userId])
            ->all();




        $response['message'] = 'User list found successfully';
        $response['user'] = $user;
        return $response;



    }


    /**
     * nearest user
     */
    public function actionSearchUser()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new User();
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId);


        //$model->scenario ='searchUser';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }


        $is_following_user = @(int) Yii::$app->getRequest()->getBodyParams()['is_following_user'];
        $is_follower_user = @(int) Yii::$app->getRequest()->getBodyParams()['is_follower_user'];
        $is_popular_user = @(int) Yii::$app->getRequest()->getBodyParams()['is_popular_user'];


        $query = $model->find()
            //->select(['user.id','user.name','user.username','user.email','user.description','user.phone','user.image'])
            ->select(['user.id', 'user.name', 'user.email','user.unique_id', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'user.dob', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.location', 'user.latitude', 'user.longitude','user.profile_visibility'])
            ->where(['user.role' => User::ROLE_CUSTOMER])
            ->andwhere(['user.status' => User::STATUS_ACTIVE])
            ->andWhere(['NOT', ['user.id' => $userIdsBlockedMe]]);

        if ($model->name) {
            $query->andWhere(['like', 'user.name', $model->name]);
        } elseif ($is_following_user) {

            $query->innerJoinWith('follower');
            $query->andWhere(['follower.follower_id' => $userId]);
        } elseif ($is_follower_user) {

            $query->innerJoinWith('following');
            $query->andWhere(['follower.user_id' => $userId]);
        }

        $user = $query->all();



        $response['message'] = 'User list found successfully';
        $response['user'] = $user;
        return $response;



    }





    /**
     * sugested user
     */
    public function actionSugestedUser()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new User();
        //$model->scenario ='searchUser';

        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId);

        $modelFollower = new Follower();

        $followingUserIds = $modelFollower->getFollowingUserIDs($userId);
        $followingUserIds[] = $userId;


        $query = $model->find()
            ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'user.dob', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.location', 'user.latitude', 'user.longitude','user.profile_visibility'])
            ->addSelect(['sum(post.popular_point) as totalpopularPoint'])
            ->where(['user.role' => User::ROLE_CUSTOMER])
            ->andwhere(['user.status' => User::STATUS_ACTIVE])
            ->andWhere(['NOT', ['user.id' => $followingUserIds]])
            ->andWhere(['NOT', ['user.id' => $userIdsBlockedMe]])
            ->joinWith('userPost')
            ->groupBy('user.id')
            ->orderBy('totalpopularPoint desc')
            ->limit(10);

        $topUser = $query->all();


        /////top winners


        $query = $model->find()
            ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'user.dob', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.location', 'user.latitude', 'user.longitude','user.profile_visibility'])
            //  ->addSelect(['sum(competition_user.is_winner) as totalWinner'])
            ->addSelect(['count(competition_winner_position.winner_user_id) as totalWinner'])
            ->where(['user.role' => User::ROLE_CUSTOMER])
            ->andwhere(['user.status' => User::STATUS_ACTIVE])
            ->andWhere(['NOT', ['user.id' => $followingUserIds]])
            ->andWhere(['NOT', ['user.id' => $userIdsBlockedMe]])
            // ->joinWith('userCompetition')
            ->joinWith('competitionWinnerUser')

            ->groupBy('user.id')

            ->orderBy('totalWinner desc')
            ->limit(10);

        $topWinner = $query->all();

        $response['message'] = 'User list found successfully';
        $response['topUser'] = $topUser;
        $response['topWinner'] = $topWinner;
        return $response;

    }


    /**
     * find a frined
     */
    public function actionFindFriend()
    {
        $userId = Yii::$app->user->identity->id;
        $modelSearch = new UserSearch();

        //  print_r(Yii::$app->request->queryParams);
        // $model->load(Yii::$app->request->queryParams, '');
        $result = $modelSearch->searchFindFriend(Yii::$app->request->queryParams);



        $response['message'] = 'User list found successfully';
        $response['user'] = $result;
        return $response;



    }
    
      /**
     * find a agent and list
     */
    public function actionAgent(){
        $userId= Yii::$app->user->identity->id;
        $modelSearch = new UserSearch();
        
        $result = $modelSearch->searchFindAgent(Yii::$app->request->queryParams);
            
        $response['message']='Agent list found successfully';
        $response['user']=$result;
        return $response; 


        
    }


    /**
     * Report user
     */
    public function actionReportUser()
    {


        $model = new ReportedUser();
        $userId = Yii::$app->user->identity->id;

        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }
        $reportToUserId = @(int) $model->report_to_user_id;
        $totalCount = $model->find()->where(['report_to_user_id' => $reportToUserId, 'user_id' => $userId, 'status' => ReportedUser::STATUS_PENDING])->count();
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['user']['alreadyReported'];
            $response['errors'] = $errors;
            return $response;
        }
        $model->status = ReportedUser::STATUS_PENDING;
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['user']['reportedSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }

    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
    /**
     * Profile user
     */
    public function actionAddSetting()
    {
        $model = new UserSetting();
        $userId = Yii::$app->user->identity->id;
        try {
            if (Yii::$app->request->isPost) {
                $model->load(Yii::$app->getRequest()->getBodyParams(), '');
                $model->scenario = 'add_setting';
                if (!$model->validate()) {
                    $response['statusCode'] = 422;
                    $response['errors'] = $model->errors;
                    return $response;
                }
                $userSetting = $model->find()
                    ->where(
                        [
                            'user_setting.user_id' => $userId
                        ]
                    )->one();
                if ($userSetting) {
                    $userSetting->relation_setting = $model->relation_setting;
                    $userSetting->save();
                } else {
                    $model->save();
                }
                $response['message'] = 'User Setting added successfully';
                return $response;
            }
        } catch (ErrorException $e) {
            $response['statusCode'] = 500;
            $errors['message'] = 'error';
            $response['errors'] = $e->getMessage();
            return $response;
        }

    }

    /**
     * Profile user
     */
    public function actionProfileVisibility()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        $model->scenario = 'profileVisibility';

        // $request = Yii::$app->request;
        // $params = $request->bodyParams;
        // $model->attributes=$params;


        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        if ($model->save(false)) {
            $response['message'] = 'Profile Visibility Updated successfully';
            return $response;

        }

    }

    public function actionViewCounter()
    {
        $model = new ProfileView();

        $userId = @(int) Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $postId = @(int) $model->reference_id;
        $source_type = @(int) $model->source_type;
        if ($source_type == ProfileView::SOURCE_TYPE_POST) {
            $postData = Post::find()->where(['id' => $postId])->one();
        } elseif ($source_type == ProfileView::SOURCE_TYPE_REELS) {
            // $postData = Audio::find()->where(['id' => $postId])->one();
        } elseif ($source_type == ProfileView::SOURCE_TYPE_STORY) {
            $postData = Story::find()->where(['id' => $postId])->one();
        }

        if (!empty($postData)) {
            $postUserId = @(int) $postData->user_id;
        } else {
            $response['message'] = 'Data Not Found.';
            return $response;
        }
        // exit("fk");
        if (!empty($postUserId)) {
            $followerData = follower::find()->where(['user_id' => $postUserId, 'follower_id' => $userId])->andWhere(['!=', 'type', follower::FOLLOW_REQUEST])->one();
        }
        $isfollower = '';
        if (!empty($followerData)) {
            $isfollower = 1;
        } else {
            $isfollower = 0;
        }

        $userModel = new User();
        $userResult = $userModel->getFullProfileMy($userId);
        $dob = $userResult->dob;
        if (!empty($dob)) {
            $age = (date('Y') - date('Y', strtotime($dob)));
        } else {
            $age = 0;
        }

        $model->age = $age;
        $model->gender = $userResult->sex;
        $model->country_id = $userResult->country_id;
        $model->profile_category_id = $userResult->profile_category_type;
        $model->is_follower = $isfollower;
        $model->impression_count = ProfileView::IMPRESSION_COUNT;

        // $totalCount = $model->find()->where(['post_id' => $postId, 'user_id' => $userId])->count();

        $result = $model->find()->where(['reference_id' => $postId, 'user_id' => $userId, 'source_type' => $source_type])->one();
        if ($source_type == ProfileView::SOURCE_TYPE_POST) {
            $result = $model->find()->where(['reference_id' => $postId, 'user_id' => $userId, 'source_type' => $source_type])->one();
        } elseif ($source_type == ProfileView::SOURCE_TYPE_REELS) {
            $result = $model->find()->where(['reference_id' => $postId, 'user_id' => $userId, 'source_type' => $source_type])->one();
        } elseif ($source_type == ProfileView::SOURCE_TYPE_STORY) {
            $result = $model->find()->where(['reference_id' => $postId, 'user_id' => $userId, 'source_type' => $source_type])->one();
        }
        if (!empty($result)) {
            $post_view_Id = $result->id;
            $modelPostView = ProfileView::find()->where(['id' => $post_view_Id])->one();

            $old_impression_count = $result->impression_count;
            $total_impression_count = $old_impression_count + ProfileView::IMPRESSION_COUNT;
            $modelPostView->impression_count = $total_impression_count;
            if ($modelPostView->save(false)) {

            }


        }
        // if ($totalCount == 0) {
        else {
            $model->save(false);
            if ($source_type == ProfileView::SOURCE_TYPE_POST) {
                $modelPost = new Post();
                // $modelPost->updateViewCounter($postId);
            }


        }

        $response['message'] = 'ok';
        return $response;

    }

    /**
     * update  Profile image user
     */
    public function actionUpdateProfileCoverImage()
    {
        try {
            $id = Yii::$app->user->identity->id;
            $model = $this->findModel($id);
            $model->scenario = 'updateProfileCoverImage';

            if (!Yii::$app->request->isPost) {
                return ['statusCode' => 405, 'message' => 'Method not allowed.'];
            }

            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->imageFile = UploadedFile::getInstanceByName('imageFile');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }

            if (!$model->imageFile) {
                return ['statusCode' => 422, 'message' => 'imageFile is required.'];
            }

            $type = Yii::$app->fileUpload::TYPE_USER;
            $files = Yii::$app->fileUpload->uploadFile($model->imageFile, $type, false);
            if (empty($files[0]['file'])) {
                return [
                    'statusCode' => 422,
                    'message' => !empty($files[0]['isProhabited'])
                        ? 'Cover photo was blocked by content moderation.'
                        : 'Could not save cover photo — storage may be misconfigured.',
                ];
            }

            $model->cover_image = $files[0]['file'];

            if ($model->save(false)) {
                return ['message' => 'Profile cover image updated successfully'];
            }

            return ['statusCode' => 422, 'message' => 'Could not update cover image.'];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return [
                'statusCode' => 500,
                'message' => 'Could not upload cover photo. ' . $e->getMessage(),
            ];
        }
    }

    // Login With Phone Number
    public function actionLoginWithPhonenumber()
    {
       
        $model = new User();
        $model->scenario = 'loginWithMobile';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;

         // check blocked ip        
         $loginIp = @trim($params['login_ip']);
         if (BlockedIp::isIpBlocked($loginIp)) {
             $response['statusCode'] = 401;
             $errors['message'][] = Yii::$app->params['apiMessage']['common']['blocked'];
             $response['errors'] = $errors;
             return $response;
         }
         // end check blocked ip

        

        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }

        $phoneNumber = $params['phone'];
        $country_code = $params['country_code'];
        $phoneNumber =  $model->getValidPhone($phoneNumber);
        $country_code =  $model->getValidPhoneCode($country_code);
        
        $modelRes = $model->find()->where(['phone' => $phoneNumber, 'country_code' => $country_code])->andWhere(['!=', 'status', User::STATUS_DELETED])->one();
        if ($modelRes) {
            $modelRes->is_login_first_time = 0;
        }
        if (!$modelRes) {
            // $model =  new User();
            $modelPackage = new Package();
            $model->scenario = 'registerWithMobile';
            $request = Yii::$app->request;
            $params = $request->bodyParams;
            $model->attributes = $params;
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $model->role = $model::ROLE_CUSTOMER;
            $model->status = $model::STATUS_ACTIVE;
            $model->is_login_first_time = 1;
            $defaultPackage = $modelPackage->getDefaultPackage();
            if ($defaultPackage) {
                $model->available_coin = $defaultPackage->coin;
            }
            
            $username           =  $model->username;
            $name              =  $model->name;
            if($username){
                $user =    $model->findByUsername($username);    
                if($user){
                    $response['statusCode'] = 422;
                    $errors['message'][] = "Username already exist";
                    $response['errors'] = $errors;
                    return $response;
                }
            }else{
                $username = $model->generateUsername($name);
                
            }
            if(!$name){
                $name = $username;
            }
            $model->name = $name;
            $model->username = $username;
            $model->password = Yii::$app->security->generateRandomString();
            $model->country_code = $country_code;
            $model->phone = $phoneNumber;

            if ($model->save()) {
                $modelRes = $model->findOne($model->id);
            }

        }

        $otp = mt_rand(100000, 999999);
        $token = md5(time() . rand(10, 100));
        $expirytTime = time() + 900;
        $token = $token . '-2';
        $token = $token . '_' . $expirytTime;

        if (Yii::$app->params['siteMode'] == 2 || Yii::$app->params['siteMode'] == 3) { // test mode and with mobile number verification
            $otp = Yii::$app->params['testOtp'];
        }

        $modelRes->verification_token = $otp;
        $modelRes->password_reset_token = $token;
        if ($modelRes->save(false)) {

                $phoneInput=[];
                $phoneInput['countryCode'] = $country_code;
                $phoneInput['phoneNumber'] = $phoneNumber;
                
                $otpString = "Verification OTP : " . $otp;
                $smsData=[];
                $smsData['message']=$otpString;

                $isSuccess = Yii::$app->sms->sendSms($phoneInput,$smsData);

                $otpPublic = '';
                
                if($isSuccess){
                    if(Yii::$app->params['siteMode'] == 1) {
                        $msg =  "OTP has been sent on your mobile for confirmation.";
                    }else{
                        $msg =  "Use OTP for verification in demo. Your OTP is ".$otp;
                        $otpPublic = $otp;
                    }
                    
                  
                    

                    $response['message'] = $msg;
                    $response['verify_token'] = $token;
                    $response['otp'] = $otpPublic;
                    return $response;

                } else {

                    $response['statusCode'] = 422;
                    $errors['message'][] = "Sending otp is failed, Please try again";
                    $response['errors'] = $errors;
                    return $response;

                }
         

                /*$sid = Yii::$app->params['twilioSid'];
                $tokenTwilio = Yii::$app->params['twilioToken'];
                $smsFromTwilio = Yii::$app->params['smsFromTwilio'];
                $twilio = new Client($sid, $tokenTwilio);

                $toNumber = '+' . $country_code . $phoneNumber;

                $otpString = "Verification OTP:" . $otp;
                $message = $twilio->messages
                    ->create(
                        $toNumber,
                        // to
                        ["from" => $smsFromTwilio, "body" => $otpString]
                    );
                if ($message->sid) {

                    $response['message'] = "OTP has been sent on your mobile for confirmation.";
                    $response['verify_token'] = $token;
                    return $response;

                } else {

                    $response['statusCode'] = 422;
                    $errors['message'][] = "Sending otp is failed, Please try again";
                    $response['errors'] = $errors;
                    return $response;

                }*/

           
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;

        }

    }

    /**
     * login with mobile without otp on firebase
     */
   
     public function actionLoginPhonenumberWithoutOtp()
     {
 
         $model = new User();
         $model->scenario = 'loginWithMobile';
         $request = Yii::$app->request;
         $params = $request->bodyParams;
         $model->attributes = $params;
 
          // check blocked ip        
          $loginIp = @trim($params['login_ip']);
          if (BlockedIp::isIpBlocked($loginIp)) {
              $response['statusCode'] = 401;
              $errors['message'][] = Yii::$app->params['apiMessage']['common']['blocked'];
              $response['errors'] = $errors;
              return $response;
          }
          // end check blocked ip
 
         
 
         if (!$model->validate()) {
             $response['statusCode'] = 422;
             $response['errors'] = $model->errors;
             return $response;
         }
 
         $phoneNumber = $params['phone'];
         $country_code = $params['country_code'];
         $phoneNumber =  $model->getValidPhone($phoneNumber);
         $country_code =  $model->getValidPhoneCode($country_code);
         
         $modelRes = $model->find()->where(['phone' => $phoneNumber, 'country_code' => $country_code])->andWhere(['!=', 'status', User::STATUS_DELETED])->one();
         if ($modelRes) {
             $modelRes->is_login_first_time = 0;
             if($modelRes->status !=$model::STATUS_ACTIVE){
                $response['statusCode'] = 422;
                $errors['message'][] = "Please contact to admin";
                $response['errors'] = $errors;
                return $response;



             }
         }
         if (!$modelRes) {
             // $model =  new User();
             $modelPackage = new Package();
             $model->scenario = 'registerWithMobile';
             $request = Yii::$app->request;
             $params = $request->bodyParams;
             $model->attributes = $params;
             if (!$model->validate()) {
                 $response['statusCode'] = 422;
                 $response['errors'] = $model->errors;
                 return $response;
             }
             $model->role = $model::ROLE_CUSTOMER;
             $model->status = $model::STATUS_ACTIVE;
             $model->is_login_first_time = 1;
             $model->is_phone_verified = $model::IS_PHONE_VERIFIED_YES;
             $defaultPackage = $modelPackage->getDefaultPackage();
             if ($defaultPackage) {
                 $model->available_coin = $defaultPackage->coin;
             }
             
             $username           =  $model->username;
             $name              =  $model->name;
             if($username){
                 $user =    $model->findByUsername($username);    
                 if($user){
                     $response['statusCode'] = 422;
                     $errors['message'][] = "Username already exist";
                     $response['errors'] = $errors;
                     return $response;
                 }
             }else{
                 $username = $model->generateUsername($name);
                 
             }
             if(!$name){
                 $name = $username;
             }
             $model->name = $name;
             $model->username = $username;
             $model->password = Yii::$app->security->generateRandomString();
             $model->country_code = $country_code;
             $model->phone = $phoneNumber;

           
 
             if ($model->save(false)) {
                 $modelRes = $model->findOne($model->id);
             }
 
         }

         $userProfile = $model->getProfile($modelRes->id);
         $authKey = Yii::$app->security->generateRandomString();
         $userProfile->auth_key = $authKey;

         if($userProfile->save(false)){


            $loginMode =  UserLoginLog::LOGIN_MODE_PHONE_NUMBER;
            //region user login log
            $modelUserLoginLog = new UserLoginLog();
            $modelUserLoginLog->attributes = $params;
            $modelUserLoginLog->user_id =$userProfile->id;
            $modelUserLoginLog->login_mode = $loginMode;
            $modelUserLoginLog->save(false);
            //regionend
            
           // $userProfile->auth_key = $userProfile->auth_key;
            $response['message'] = 'Looged in successfully';
            $response['user'] = $userProfile;
            $response['auth_key'] = $userProfile->auth_key;
            return $response;
       
       
             
           
         } else {
 
             $response['statusCode'] = 422;
             $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
             $response['errors'] = $errors;
             return $response;
 
         }
 
     }
    public function actionLoginPhonenumberWithoutOtp_old()
    {
        //$id = Yii::$app->user->identity->id;
        $model = new User();
        $model->scenario = 'verifyPhonenuberLogin';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes = $params;
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
         // check blocked ip        
         $loginIp = @trim($params['login_ip']);
         if (BlockedIp::isIpBlocked($loginIp)) {
             $response['statusCode'] = 401;
             $errors['message'][] = Yii::$app->params['apiMessage']['common']['blocked'];
             $response['errors'] = $errors;
             return $response;
         }
         // end check blocked ip


         $phoneNumber = $params['phone'];
         $country_code = $params['country_code'];
         $phoneNumber =  $model->getValidPhone($phoneNumber);
         $country_code =  $model->getValidPhoneCode($country_code);
         
         $user = $model->find()->where(['phone' => $phoneNumber, 'country_code' => $country_code])->andWhere(['!=', 'status', User::STATUS_DELETED])->one();


       // $user = $model->find()->where(['country_code' => $model->country_code, 'phone' => $model->phone])->andWhere(['!=', 'status', User::STATUS_DELETED])->one();

        if(!$user) {

            $authKey = Yii::$app->security->generateRandomString();
            $user->auth_key = $authKey;

            $user->password_reset_token = null;
            $user->verification_token = null;
            // $user->is_email_verified = $model::IS_EMAIL_VERIFIED_YES;
            $user->is_phone_verified = $model::IS_PHONE_VERIFIED_YES;
            $user->is_login_first_time = 0;
            $user->role = $model::ROLE_CUSTOMER;
            $user->status = $model::STATUS_ACTIVE;

            $modelPackage = new Package();
            $defaultPackage = $modelPackage->getDefaultPackage();
            if ($defaultPackage) {
                $user->available_coin = $defaultPackage->coin;
            }

            if ($user->save(false)) {

                $userProfile = $model->getProfile($user->id);
                //echo $userProfile->auth_key;
                $userProfile->auth_key = $userProfile->auth_key;
                $response['message'] = 'Looged in successfully';
                $response['user'] = $userProfile;
                $response['auth_key'] = $userProfile->auth_key;
                return $response;

            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = 'Verification process failed';
            $response['errors'] = $errors;
            return $response;
        }
    }

      /**
     *  user chat online show status
     */
    public function actionShowChatOnlineStatus(){  
        $id = Yii::$app->user->identity->id;
         $model = $this->findModel($id);
         $model->scenario ='showChatOnlineStatus';
         $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        
         if(!$model->validate()) {
             $response['statusCode']=422;
             $response['errors']=$model->errors;
             return $response;
         }
         
         if($model->save(false)){          
             $response['message']='Profile show chat online status Updated successfully';
             return $response; 
 
         }
         
     }
 
}