<?php
namespace backend\controllers;

use common\models\User;
use backend\models\Ad;
use common\models\LoginForm;
use common\models\VerifyOtpForm;
use common\models\Payment;
use common\models\Post;
use common\models\PostComment;
use common\models\Audio;
use common\models\Competition;
use common\models\Setting;
use common\models\EventTicketBooking;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use common\models\Coupon;
use common\models\Club;
use common\models\Event;
use common\models\Story;
use common\models\SupportRequest;
use common\models\UserLiveHistory;
use yii\web\ForbiddenHttpException;




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
              
                'rules' => [
                    [
                        'actions' => ['login','verify-otp', 'error','ticket-view','forgot-password','forgot-password-verify-otp','reset-password'],
                        'allow' => true,
                      //  'ips' => ['::1s','127..1.1', '19.68.1.11'], // Allowed IP addresses
                    ],
                    [
                        'actions' => ['logout', 'index','verify'],
                        'allow' => true,
                        'roles' => ['@'],
                       // 'ips' => ['::1s', '192.18.1.01'], // Allowed IP addresses
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
                'layout' => 'error-simple',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $auth = Yii::$app->authPermission;
        $cache = Yii::$app->cache;

        $totalPost = $auth->can($auth::POST)
            ? $this->cachedStat($cache, 'post_count', fn () => (new Post())->getTotalPostCount())
            : 0;

        $userCount = $auth->can($auth::USER)
            ? $this->cachedStat($cache, 'user_count', fn () => (new User())->getUserCount())
            : 0;

        $latestUsers = $auth->can($auth::USER)
            ? $this->cachedStat($cache, 'latest_users', fn () => (new User())->getLatestUsers(), [])
            : [];

        $competitionCount = $auth->can($auth::COMPETITION)
            ? $this->cachedStat($cache, 'competition_count', fn () => (new Competition())->getCompetitionCount())
            : 0;

        $reelCount = $auth->can($auth::REEL)
            ? $this->cachedStat($cache, 'reel_count', fn () => (new Post())->getTotalReelsCount())
            : 0;

        $clubCount = $auth->can($auth::CLUB)
            ? $this->cachedStat($cache, 'club_count', fn () => (new Club())->getTotalClubCount())
            : 0;

        $eventCount = $auth->can($auth::EVENT)
            ? $this->cachedStat($cache, 'event_count', fn () => (new Event())->getTotalEventCount())
            : 0;

        $couponCount = $auth->can($auth::COUPON)
            ? $this->cachedStat($cache, 'coupon_count', fn () => (new Coupon())->getTotalCouponCount())
            : 0;

        $totalStory = $auth->can($auth::STORY)
            ? $this->cachedStat($cache, 'story_count', fn () => (new Story())->getStoryTotalCount())
            : 0;

        $firstGraph = $auth->can($auth::POST)
            ? $this->normalizeGraph($this->cachedStat($cache, 'graph_posts', fn () => (new Post())->getLastTweleveMonthPost(), []))
            : $this->normalizeGraph([]);

        $userGraph = $auth->can($auth::USER)
            ? $this->normalizeGraph($this->cachedStat($cache, 'graph_users', fn () => (new User())->getLastTweleveMonthUser(), []))
            : $this->normalizeGraph([]);

        $paymentGraph = $auth->can($auth::PAYMENT)
            ? $this->normalizeGraph($this->cachedStat($cache, 'graph_payments', fn () => (new Payment())->getLastTweleveMonthPayments(), []))
            : $this->normalizeGraph([]);

        $clubGraph = $auth->can($auth::CLUB)
            ? $this->normalizeGraph($this->cachedStat($cache, 'graph_clubs', fn () => (new Club())->getLastTweleveMonthClub(), []))
            : $this->normalizeGraph([]);

        $reelsGraph = $auth->can($auth::REEL)
            ? $this->normalizeGraph($this->cachedStat($cache, 'graph_reels', fn () => (new Post())->getLastTweleveMonthReels(), []))
            : $this->normalizeGraph([]);

        $storyGraph = $auth->can($auth::STORY)
            ? $this->normalizeGraph($this->cachedStat($cache, 'graph_stories', fn () => (new Story())->getLastTweleveMonthStory(), []))
            : $this->normalizeGraph([]);

        $postLatest = $auth->can($auth::POST)
            ? $this->cachedStat($cache, 'latest_posts', fn () => (new Post())->getLatestPost(), [])
            : [];

        $earnings = $auth->can($auth::PAYMENT)
            ? $this->cachedStat($cache, 'earnings', fn () => $this->buildEarningsSummary(), [
                'totalEarning' => 0,
                'totalEarningLastMonth' => 0,
                'lastMonthPercentage' => 0,
            ])
            : ['totalEarning' => 0, 'totalEarningLastMonth' => 0, 'lastMonthPercentage' => 0];

        $support = $auth->can($auth::USER)
            ? $this->cachedStat($cache, 'support_stats', fn () => (new SupportRequest())->getTotalSupportRequest(), [
                'totalSupport' => 0,
                'totalPendingSupport' => 0,
                'percentage' => 0,
            ])
            : ['totalSupport' => 0, 'totalPendingSupport' => 0, 'percentage' => 0];

        $liveHistory = $auth->can($auth::USER)
            ? $this->cachedStat($cache, 'live_history_stats', fn () => (new UserLiveHistory())->getTotalLiveHistory(), [
                'totallive' => 0,
                'totalCurrentLive' => 0,
                'percentage' => 0,
            ])
            : ['totallive' => 0, 'totalCurrentLive' => 0, 'percentage' => 0];

        return $this->render('index', [
            'totalPost' => $totalPost,
            'totalComment' => 0,
            'userCount' => $userCount,
            'totalCompetition' => $competitionCount,
            'reelCount' => $reelCount,
            'clubCount' => $clubCount,
            'eventCount' => $eventCount,
            'couponCount' => $couponCount,
            'firstGraph' => $firstGraph,
            'paymentGraph' => $paymentGraph,
            'userGraph' => $userGraph,
            'clubGraph' => $clubGraph,
            'totalStory' => $totalStory,
            'reelsGraph' => $reelsGraph,
            'storyGraph' => $storyGraph,
            'postLatest' => $postLatest,
            'latestUsers' => $latestUsers,
            'earnings' => $earnings,
            'support' => $support,
            'liveHistory' => $liveHistory,
        ]);
    }

    /**
     * @return array{totalEarning:int|float,totalEarningLastMonth:int|float,lastMonthPercentage:int|float}
     */
    private function buildEarningsSummary(): array
    {
        $modelPayment = new Payment();
        $totalEarning = $this->safeCall(fn () => $modelPayment->getTotalEarning(), 0);
        $totalEarning = round((float) $totalEarning);
        $totalEarningLastMonth = $this->safeCall(fn () => $modelPayment->getTotalEarningLastMonth(), 0);
        $totalEarningLastMonth = round((float) $totalEarningLastMonth);
        $lastMonthPercentage = $totalEarning > 0
            ? round($totalEarningLastMonth / $totalEarning * 100)
            : 0;

        return [
            'totalEarning' => $totalEarning,
            'totalEarningLastMonth' => $totalEarningLastMonth,
            'lastMonthPercentage' => $lastMonthPercentage,
        ];
    }

    /**
     * @template T
     * @param \yii\caching\CacheInterface $cache
     * @param string $key
     * @param callable():T $fn
     * @param mixed $default
     * @return T|mixed
     */
    private function cachedStat($cache, string $key, callable $fn, $default = 0)
    {
        try {
            return $cache->getOrSet('admin_dash:' . $key, function () use ($fn, $default) {
                return $this->safeCall($fn, $default);
            }, 120);
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return $this->safeCall($fn, $default);
        }
    }


 public function actionVerify()
{
    $this->layout = 'main-login';
    $model = new Setting();
    $model->scenario = 'verifyPurchaseCode';
    $result = $model->getSettingData();
    $result->user_p_id = 'bypassed';
    if ($result->save()) {
        Yii::$app->session->setFlash('success', 'Purchase code verification bypassed');
        return $this->goHome();
    }
    return $this->render('verify', ['model' => $model]);
}

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        $this->layout = 'main-login';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new LoginForm();
        $modelSetting = new Setting();

        

        if (isset($_COOKIE["username"]) && isset($_COOKIE["password"])) {

            $username = $_COOKIE["username"];
            $password = $_COOKIE["password"];

        } else {

            $username = null;
            $password = null;
        }

        if ($model->load(Yii::$app->request->post())) {

            try {
                $user = User::findByUsername($model->username);
                
                $data = Yii::$app->request->post();
                if ($user) {
                    if ($user->role == User::ROLE_ADMIN || $user->role == User::ROLE_SUBADMIN) {
                        $resUser = $model->login();
                        if($resUser){
                            $rememberMe = $data['LoginForm']['rememberMe'] ?? 0;

                            if ($rememberMe == 1) {
                                $hour = time() + 3600 * 24 * 30;
                                setcookie('username', $data['LoginForm']['username'], $hour);
                                setcookie('password', $data['LoginForm']['password'], $hour);
                            }
                            
                            $settingData = $modelSetting->getSettingData();
                            $isTwoFactorAuth = (int) ($settingData->is_two_factor_auth ?? 0);

                            if($isTwoFactorAuth){
                                $session = Yii::$app->session;
                                $session->set('loguser', $resUser);
                                $session->set('rememberMe', $rememberMe);
                                $otp = mt_rand(1000, 9999);
                                $token = md5(time() . rand(10, 100));
                                $expirytTime = time() + 900;
                            
                                $token = $token . '_' . $expirytTime;
                                $user->password_reset_token = $token;
                                $user->verification_token = $otp;

                                if ($user->save(false)) {
                                    $fromMail = Yii::$app->params['senderEmail'];
                                    $fromName = Yii::$app->params['senderName'];
                                    $from = array($fromMail => $fromName);
                                    Yii::$app->mailer->compose()
                                        ->setSubject('Admin Login confirmation')
                                        ->setFrom($from)
                                        ->setTo($user->email)
                                        ->setHtmlBody('Hi ' . $user->username . '<br>Please use following OTP Code confirm your admin login.<br> OTP Code is : ' . $otp)
                                        ->send();
                                        
                                        return $this->redirect(['verify-otp', 'token' => $token]);

                                }

                            }else{
                                Yii::$app->user->login($resUser, $rememberMe ? 3600 * 24 * 30 : 0);
                                $user->last_active = time();
                                $user->save(false);
                                try {
                                    $modelSetting->updateSettingData();
                                } catch (\Throwable $e) {
                                    Yii::warning($e->getMessage(), __METHOD__);
                                }
                                return $this->redirect(['site/index']);
                            }
                           
                        } else {

                            Yii::$app->session->setFlash('warning', "Invalid Data.");
                            return $this->goBack();
                        }
                    } else {
                        Yii::$app->session->setFlash('warning', "Invalid Data.");
                        return $this->goBack();

                    }
                } else {
                    Yii::$app->session->setFlash('warning', "Invalid Data.");
                    return $this->goBack();
                }
            } catch (\Throwable $e) {
                Yii::error($e->getMessage(), __METHOD__);
                Yii::$app->session->setFlash('error', 'Login failed temporarily. Please try again.');
                return $this->refresh();
            }

        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
                'username' => $username,
                'password' => $password,
            ]);
        }

    }

    public function actionVerifyOtp()
    {
        $this->layout = 'main-login';
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new VerifyOtpForm();
        $modelUser = new User();
        $modelSetting = new Setting();
      
        if ($model->load(Yii::$app->request->post())) {
            $token = @Yii::$app->request->get('token');
            $otp =  $model->otp;
            $tokenExprity = @explode('_', $token)[1];
            
            if (time() > $tokenExprity) {
               
               Yii::$app->session->setFlash('error', "Your token has been expired.");
               return $this->refresh();
                
            }
          
            $user = $modelUser->find()->where(['password_reset_token' => $token, 'verification_token' => $otp, 'status' => User::STATUS_ACTIVE, 'role' => [User::ROLE_ADMIN,User::ROLE_SUBADMIN]])->one();
            if ($user) {
                $user->password_reset_token = null;
                $user->verification_token = null;
                $user->last_active = time();
                if ($user->save(false)) {
                    $session = Yii::$app->session;
                    $user =  $session->get('loguser');
                    $rememberMe =  $session->get('rememberMe');
                    $modelSetting->updateSettingData();
                    Yii::$app->user->login($user, $rememberMe ? 3600 * 24 * 30 : 0);
                    return $this->goHome();

                }
            }else{
              
               Yii::$app->session->setFlash('error', "Invalid OTP.");
             
            }
        } 
        return $this->render('verify-otp', [
            'model' => $model
        ]);
        

    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionTicketView($id)
    {

        $this->layout = 'main-login';

        $modelEventTicketBooking = new EventTicketBooking();

        $model= $modelEventTicketBooking->findOne($id);
      

        
        
      //  $model='';

        return $this->render('ticket-view', [
            'model' => $model
            
        ]);


    }
    
    public function actionForgotPassword()
    {

        $this->layout = 'main-login';
        $model = new User();
      
      
        if ($model->load(Yii::$app->request->post())) {

            $user = User::findByUsername($model->username);

            $data = Yii::$app->request->post();
            if ($user) {
                if (($user->role == User::ROLE_ADMIN || $user->role == User::ROLE_SUBADMIN) && $user->status ==User::STATUS_ACTIVE ) {

                    $otp = mt_rand(1000, 9999);
                    $token = md5(time() . rand(10, 100));
                    $expirytTime = time() + 900;
                
                    $token = $token . '_' . $expirytTime;
                    $user->password_reset_token = $token;
                    $user->verification_token = $otp;

                    if ($user->save(false)) {
                        $fromMail = Yii::$app->params['senderEmail'];
                        $fromName = Yii::$app->params['senderName'];
                        $from = array($fromMail => $fromName);
                        Yii::$app->mailer->compose()
                            ->setSubject("Administrator forgot password")
                            ->setFrom($from)
                            ->setTo($user->email)
                            ->setHtmlBody("Hi " . $user->username . "<br>We have got reset admin password request, Please confirm .<br> OTP is : " . $otp)
                            ->send();
                            
                            return $this->redirect(['forgot-password-verify-otp', 'token' => $token]);

                    }

                   
                } else {
                    Yii::$app->session->setFlash('warning', "Invalid Data.");
                   

                }
            } else {
                Yii::$app->session->setFlash('warning', "Invalid Data.");
               
            }

        } 
            

            return $this->render('forgot-password', [
                'model' => $model
            ]);
      

    }

    public function actionForgotPasswordVerifyOtp()
    {
        $this->layout = 'main-login';
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new VerifyOtpForm();
        $modelUser = new User();
        $modelSetting = new Setting();
      
        if ($model->load(Yii::$app->request->post())) {
            $token = @Yii::$app->request->get('token');
            $otp =  $model->otp;
            $tokenExprity = @explode('_', $token)[1];
            
            if (time() > $tokenExprity) {
               
               Yii::$app->session->setFlash('error', "Your token has been expired.");
               return $this->refresh();
                
            }
          
            $user = $modelUser->find()->where(['password_reset_token' => $token, 'verification_token' => $otp, 'status' => User::STATUS_ACTIVE, 'role' => [User::ROLE_ADMIN,User::ROLE_SUBADMIN]])->one();
            if ($user) {
                $token = md5(time() . rand(10, 100));
                $expirytTime = time() + 900;
            
                $token = $token . '_' . $expirytTime;
                $user->password_reset_token = $token;
                $user->verification_token = null;
                
                if ($user->save(false)) {
                    return $this->redirect(['reset-password', 'token' => $token]);
                   

                }
            }else{
              
               Yii::$app->session->setFlash('error', "Invalid OTP.");
             
            }
        } 
        return $this->render('verify-otp', [
            'model' => $model
        ]);
        

    }

    public function actionResetPassword()
    {
      
        $this->layout = 'main-login';
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
     
        $model = new User();
        $modelUser = new User(); 
        $model->scenario = 'resetPassword';
       
      
        if ($model->load(Yii::$app->request->post() )) {
            $token = @Yii::$app->request->get('token');

            $tokenExprity = @explode('_', $token)[1];
            
            if (time() > $tokenExprity) {
               
               Yii::$app->session->setFlash('error', "Your token has been expired.");
               
               return $this->redirect(['login']);
                
            }
            $user = $modelUser->find()->where(['password_reset_token' => $token,'status' => User::STATUS_ACTIVE, 'role' => [User::ROLE_ADMIN,User::ROLE_SUBADMIN]])->one();
            if ($user) {
                $token = md5(time() . rand(10, 100));
                $expirytTime = time() + 900;
            
                $token = $token . '_' . $expirytTime;

                $user->password_hash = Yii::$app->security->generatePasswordHash($model->password);
                $user->password_reset_token = null;
                $user->verification_token = null;
                
                if ($user->save(false)) {
                    Yii::$app->session->setFlash('success', "Password updated successfully");
                    return $this->redirect(['login']);
                   

                }
            }else{
              
               Yii::$app->session->setFlash('error', "Invalid user.");
             
            }
        } 
        return $this->render('reset-password', [
            'model' => $model
        ]);
        

    }

    /**
     * @template T
     * @param callable(): T $fn
     * @param T $default
     * @return T
     */
    private function safeCall(callable $fn, $default = 0)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return $default;
        }
    }

    /**
     * @param mixed $graph
     * @return array{data: array, dataCaption: array}
     */
    private function normalizeGraph($graph): array
    {
        if (!is_array($graph)) {
            $graph = [];
        }

        return [
            'data' => $graph['data'] ?? [],
            'dataCaption' => $graph['dataCaption'] ?? [],
        ];
    }

}
