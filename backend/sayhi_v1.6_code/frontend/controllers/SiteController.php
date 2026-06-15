<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;


use common\models\User;
//use frontend\models\Ad;
use yii\data\ActiveDataProvider;
use common\models\PackageCoupon;
use backend\models\Package;
use common\models\Payment;
use yii\authclient\BaseClient;
use common\models\Setting;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
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

    public function onAuthSuccess($client)
    {

        $reflection = new \ReflectionObject($client);
        $className = $reflection->getName();
        $shortName = (new \ReflectionClass($className))->getShortName();
        $attributes = $client->getUserAttributes();
        $socialId = $attributes['id'];
        $socialuserEmail = $attributes['email'];
        $socialUserfullName = $attributes['name'];
        if ($shortName == 'Facebook') {
            $userDetails = User::findByFb($socialId);
        } elseif ($shortName == 'Google') {
            $userDetails = User::findByGoogle($socialId);
        }


        if ($userDetails) {
            if ($shortName == 'Facebook') {
                $identity = User::findOne(['email' => $userDetails['email'], 'facebook' => $userDetails['facebook']]);
            } elseif ($shortName == 'Google') {
                $identity = User::findOne(['email' => $userDetails['email'], 'googleplus' => $userDetails['googleplus']]);
            }

            Yii::$app->user->login($identity);
            Yii::$app->session->setFlash('success', "Login Successfully.");
            // return $this->redirect(['site/welcome']);
        } else {
            $model = new User();
            $model->email = $socialuserEmail;
            $model->name = $socialUserfullName;
            if ($shortName == 'Facebook') {
                $model->facebook = $socialId;
            } elseif ($shortName == 'Google') {
                $model->googleplus = $socialId;
            }

            $model->role = User::ROLE_CUSTOMER;
            $model->created_at = time();
            if ($model->save(false)) {
                if ($shortName == 'Facebook') {
                    $verifyuserdetails = $model->find()->where(['email' => $socialuserEmail, 'facebook' => $socialId])->andWhere(['status' => User::STATUS_ACTIVE])->one();
                    if ($verifyuserdetails) {
                        $identity = User::findOne(['email' => $verifyuserdetails['email'], 'facebook' => $verifyuserdetails['facebook']]);
                    }
                } elseif ($shortName == 'Google') {
                    $verifyuserdetails = $model->find()->where(['email' => $socialuserEmail, 'googleplus' => $socialId])->andWhere(['status' => User::STATUS_ACTIVE])->one();
                    if ($verifyuserdetails) {
                        $identity = User::findOne(['email' => $verifyuserdetails['email'], 'googleplus' => $verifyuserdetails['googleplus']]);
                    }

                }

                Yii::$app->user->login($identity);
                Yii::$app->session->setFlash('success', "Login Successfully.");
                // return $this->redirect(['site/welcome']);


            }
        }


    }
    public function actionIndex()
    {
        
        $modelSetting = new Setting();
        $resultSetting = $modelSetting->getSettingData();

        $session = Yii::$app->session;
        $bannerResult = [];
        return $this->render(
            'index',
            [
                'bannerResult' => $bannerResult,
                'setting' => $resultSetting

            ]
        );
    }
    /*
        public function actionLoadmore()
        {
            
            
           
            //\Yii::$app->language = 'en-US';
           // print_r(\Yii::$app->language);
            
            $modelAd         = new Ad();
            $modelBanner     = new Banner();
            $modelCategory     = new Category();
            $modelPromotionalBanner     = new PromotionalBanner();

            $bannerResult               =  $modelBanner->getAllBanner();
            $categoryResult             =  $modelCategory->getMainCategory();
            $promotionalBannerResult    =  $modelPromotionalBanner->getAllPromotionalBanner();
           // $featuedAdResult             =  $modelAd->getAllFeatureAdHome();

            
            $query = Ad::find()
            ->innerJoinWith('locations')
            ->where(['ad.status' => Ad::STATUS_ACTIVE, 'ad.featured'=>'1'])
            ->orderby(['ad.created_at' => SORT_DESC]);
            
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
            
                
            if (Yii::$app->request->isAjax) {
                return $this->renderAjax('_loadmore', [
                    'dataProvider' => $dataProvider,
                ]);
            }
        
            return $this->render('loadmore', [
                'dataProvider' => $dataProvider,
            ]);

            

            
        }
    */



    public function actionAboutUs()
    {

        return $this->render(
            'about-us',
            [

            ]
        );
    }

    // /**
    //  * Logs in a user.
    //  *
    //  * @return mixed
    //  */
    // public function actionLogin()
    // {


    //     $model = [];



    //         return $this->render('login', [
    //             'model' => $model,
    //         ]);


    // }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->redirect(['login']);
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    public function actionValidateFb()
    {
        $modelUser = new User();

        $social = Yii::$app->getModule('social');
        $fb = $social->getFb(); // gets facebook object based on module settings
        try {
            $helper = $fb->getRedirectLoginHelper();
            $accessToken = $helper->getAccessToken();
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // There was an error communicating with Graph
            return $this->render('validate-fb', [
                'out' => '<div class="alert alert-danger">' . $e->getMessage() . '</div>',
            ]);
        }

        if (isset($accessToken)) { // you got a valid facebook authorization token
            $response = $fb->get('/me?fields=id,name,email', $accessToken);
            $user = $response->getGraphUser();
            $input['type'] = 1;
            $input['name'] = $user->getName();
            $input['socialId'] = $user->getId();
            $input['email'] = $user->getEmail();
            $res = $modelUser->checkSocialUser($input);
            if ($res['status'] == 'success') {

                //  $res['id'];
                Yii::$app->user->login($modelUser->findOne($res['id']), 0);

                return $this->redirect(['user/dashboard']);

                //return $this->goHome();
            } else {

                Yii::$app->session->setFlash('error', $response->getGraphUser());

                return $this->redirect(['login']);
            }

            /* return $this->render('validate-fb', [
        'out' => '<legend>Facebook User Details</legend>' . '<pre>' . print_r($response->getGraphUser(), false) . '</pre>'
        ]);*/
        } elseif ($helper->getError()) {

            Yii::$app->session->setFlash('error', $helper->getError());
            return $this->redirect(['login']);
            // the user denied the request
            // You could log this data . . .
            /* return $this->render('validate-fb', [
        'out' => '<legend>Validation Log</legend><pre>' .
        '<b>Error:</b>' . print_r($helper->getError(), true) .
        '<b>Error Code:</b>' . print_r($helper->getErrorCode(), true) .
        '<b>Error Reason:</b>' . print_r($helper->getErrorReason(), true) .
        '<b>Error Description:</b>' . print_r($helper->getErrorDescription(), true) .
        '</pre>'
        ]);*/
        }

        Yii::$app->session->setFlash('error', 'Oops! Nothing much to process here.');
        return $this->redirect(['login']);

    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        $modelPackage = new Package();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $modelUserRes = $model->signup();
            if ($modelUserRes) {

                //$modelUser =  new User();
                // $modelUserRes    = $modelUser->findOne($model->id);
                $defaultPackage = $modelPackage->getDefaultPackage();
                if ($defaultPackage) {
                    $modelUserRes->package_id = $defaultPackage->id;
                }
                if ($modelUserRes->save(false)) {
                    $modelSubscription = new Subscription();
                    $expiryDate = $modelSubscription->getExpirtyDate($defaultPackage->term);

                    $modelSubscription->user_id = $modelUserRes->id;
                    $modelSubscription->package_id = $defaultPackage->id;
                    $modelSubscription->title = $defaultPackage->name;
                    $modelSubscription->term = $defaultPackage->term;
                    $modelSubscription->amount = $defaultPackage->price;
                    $modelSubscription->ad_limit = $defaultPackage->ad_limit;
                    $modelSubscription->ad_remaining = $defaultPackage->ad_limit;
                    $modelSubscription->payment_mode = Payment::PAYMENT_MODE_PAYPAL;
                    $modelSubscription->expiry_date = $expiryDate;
                    $modelSubscription->save(false);

                }

                Yii::$app->session->setFlash('success', Yii::t('app', 'Thank you for registration.'));
                return $this->redirect(['login']);
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {


                Yii::$app->session->setFlash('success', Yii::t('app', 'Email has been sent, please check your email for further instructions.'));

                return $this->redirect(['request-password-reset']);
            } else {
                Yii::$app->session->setFlash('error', Yii::t('app', 'Sorry, we are unable to reset password for the provided email address.'));
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'New password has been saved.'));
            return $this->redirect(['login']);

        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model,
        ]);
    }

    public function actionChangeLang($local)
    {

        $session = Yii::$app->session;
        $available_locales = [
            'ru-RU',
            'en-US',
            'ar-AE',
            'fr-FR'
        ];

        if (!in_array($local, $available_locales)) {
            throw new \yii\web\BadRequestHttpException();
        }

        //\Yii::$app->language = $local;
        $session->set('language', $local);


        return isset($_SERVER['HTTP_REFERER']) ? $this->redirect($_SERVER['HTTP_REFERER']) : $this->redirect(Yii::$app->homeUrl);

        //return $this->goBack();
    }


    public function actionChangeCountry($countryIso2, $nextPage = false)
    {

        $session = Yii::$app->session;
        $modelCountry = new Country();
        $result = $modelCountry->find()->where(['iso2' => $countryIso2])->one();
        if ($result) {
            $result->id;
            $session->set('countryId', $result->id);
            $session->set('countryName', $result->name);
        }

        $session->set('countryIso2', $countryIso2);

        if ($nextPage) {
            return $this->redirect(['./ad']);
        } else {
            return isset($_SERVER['HTTP_REFERER']) ? $this->redirect($_SERVER['HTTP_REFERER']) : $this->redirect(Yii::$app->homeUrl);
        }

        //return $this->goBack();
    }


    public function actionTest()
    {
        return $this->render('test', [
            //'model' => $model,
        ]);

    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {


        $model = [];

        $model = new User();
        // $model->scenario = 'create';
        $request = Yii::$app->request->post();
        $email = @$request['User']['email'];
        $verification_token = @$request['User']['verification_token'];

        $user = $model->find()->where(['email' => $email])->andWhere(['status' => User::STATUS_ACTIVE])->one();
        if ($user && $verification_token) {
            $verifyuserdetails = $model->find()->where(['email' => $email])->andWhere(['verification_token' => $verification_token])->andWhere(['status' => User::STATUS_ACTIVE])->one();
            if ($verifyuserdetails) {
                $identity = User::findOne(['email' => $email, 'verification_token' => $verification_token]);
                // echo "<pre>";
                // print_r($identity);
                // exit;
// logs in the user
                Yii::$app->user->login($identity);
                $user->verification_token = '';
                $user->verification_with = '';
                if ($user->save(false)) {
                    Yii::$app->session->setFlash('success', "OTP Verified Successfully.");
                    return $this->redirect(['site/welcome']);
                }

            } else {
                Yii::$app->session->setFlash('error', "Invaild OTP.");
                // return $this->redirect(['index']);
                return $this->redirect(['site/login', 'otpsent' => 1, 'email' => $user->email]);
            }
        } elseif ($user && $verification_token == '') {
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {

                $otp = mt_rand(1000, 9999);
                $token = md5(time() . rand(10, 100));
                $expirytTime = time() + 900;
                $token = $token . '-1';
                $token = $token . '_' . $expirytTime;
                // $user->password_reset_token = $token;
                $user->verification_with = 1;
                /*if(Yii::$app->params['siteMode']==2){
                $otp=Yii::$app->params['testOtp'];
                }*/
                $user->verification_token = $otp;

                if ($user->save(false)) {

                    //$from = Yii::$app->params['senderEmail'];
                    $fromMail = Yii::$app->params['senderEmail'];
                    $fromName = Yii::$app->params['senderName'];
                    $from = array($fromMail => $fromName);

                    Yii::$app->mailer->compose()
                        ->setSubject('Login confirmation')
                        ->setFrom($from)
                        ->setTo($user->email)
                        ->setHtmlBody('Hi ' . $user->username . '<br>Please use following OTP Code confirm you login.<br> OTP Code is : ' . $otp)
                        ->send();
                    Yii::$app->session->setFlash('success', "OTP sent your email. Please verify");
                    // return $this->redirect(['index']);
                    return $this->redirect(['site/login', 'otpsent' => 1, 'email' => $user->email]);
                }

            }
            return $this->render('login', [
                'model' => $model,
            ]);

        } else {
            if(Yii::$app->request->post()){
                Yii::$app->session->setFlash('error', "Email not found");
                return $this->redirect(['site/login']);
            }else{
                return $this->render('login', [
                    'model' => $model,
                ]);

            }
            
        }



    }

    public function actionRedeemCoupon()
    {

        $userId = @Yii::$app->user->identity->id;
        if (empty($userId)) {
            Yii::$app->session->setFlash('error', "Please Login");
            return $this->redirect(['site/login']);
        }
        $model = new PackageCoupon();
        $userModel = new User();
        $paymentModel = new Payment();
        $request = Yii::$app->request->post();


        $couponCode = @$request['PackageCoupon']['code'];
        if ($couponCode) {
            $couponCode = strtoupper($couponCode);
        }


        $couponDetail = $model->find()->where(['code' => $couponCode, 'is_used' => PackageCoupon::IS_USED_NO])->andWhere(['status' => PackageCoupon::STATUS_ACTIVE])->one();
        if ($couponDetail) {

            $package_id = @$couponDetail->package_id;
            $packageData = Package::find()->where(['id' => $package_id, 'status' => Package::STATUS_ACTIVE])->one();
            $packageCoin = $packageData->coin;

            // payment tranctio entery
            $paymentDetails = $paymentModel->find()->where(['id' => $userId])->one();
            $paymentModel->type = Payment::TYPE_COIN;
            $paymentModel->user_id = $userId;
            $paymentModel->package_id = $package_id;
            $paymentModel->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
            $paymentModel->payment_type = Payment::PAYMENT_TYPE_PACKAGE;
            $paymentModel->coin = $packageCoin;
            $paymentModel->payment_mode = Payment::PAYMENT_MODE_PACKAGE_COUPON;
            $paymentModel->created_at = time();

            if ($paymentModel->save()) {
                $userDetails = $userModel->find()->where(['id' => $userId])->one();
                if ($userDetails) {

                    $user_available_coin = @$userDetails->available_coin;
                    $totalCoin = $packageCoin + $user_available_coin;
                    $userDetails->available_coin = $totalCoin;

                    $userDetails->package_id = $package_id;
                    if ($userDetails->save()) {
                        $couponDetail->is_used = PackageCoupon::IS_USED_YES;
                        $couponDetail->status = PackageCoupon::STATUS_EXPIRED;
                        $couponDetail->save();
                        Yii::$app->session->setFlash('success', "Congratulations !! you have successfully redeem coupon and its reflected into your account.");
                        return $this->redirect(['site/redeem-coupon']);

                    }
                }
            }
        } else {
            if ($couponCode) {
                Yii::$app->session->setFlash('error', "Coupon code did not match");
            }

        }

        return $this->render('redeem-coupon', [
            'model' => $model,
        ]);
    }

    public function actionWelcome()
    {


        $userId = @Yii::$app->user->identity->id;
        if (empty($userId)) {
            Yii::$app->session->setFlash('error', "Please Login");
            return $this->redirect(['site/login']);
        }
        $session = Yii::$app->session;


        $bannerResult = [];


        return $this->render(
            'welcome',
            [
                'bannerResult' => $bannerResult,

            ]
        );
    }

    public function actionDeleteAccount($id)
    {
        $modelUser = new User();
        // $modelUser->checkPageAccess();

        $userModel = $modelUser->find()->where(['id' => $id])->one();
        $userModel->status = USER::STATUS_DELETED;
        $userModel->save(false);
        Yii::$app->session->setFlash('success', "You have successfully deleted your account.");
        return $this->redirect(['login']);
    }
}