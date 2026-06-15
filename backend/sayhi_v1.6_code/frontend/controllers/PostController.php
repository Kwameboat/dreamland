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
use yii\authclient\BaseClient;
/**
 * Site controller
 */
class PostController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup','share'],
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

    

        public function beforeAction($action) {

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
            if($shortName =='Facebook'){
                $userDetails = User::findByFb($socialId);
            }elseif($shortName =='Google'){
                $userDetails = User::findByGoogle($socialId);
            }
            
            
            if($userDetails){
                if($shortName =='Facebook'){
                  $identity = User::findOne(['email' => $userDetails['email'] , 'facebook' => $userDetails['facebook']]);
                }elseif($shortName =='Google'){
                  $identity = User::findOne(['email' => $userDetails['email'] , 'googleplus' => $userDetails['googleplus']]);
                }
               
                Yii::$app->user->login($identity);
                Yii::$app->session->setFlash('success', "Login Successfully.");
                // return $this->redirect(['site/welcome']);
            }else{
                $model = new User();
                $model->email = $socialuserEmail;
                $model->name = $socialUserfullName;
                if($shortName =='Facebook'){
                    $model->facebook = $socialId;
                }elseif($shortName =='Google'){
                    $model->googleplus = $socialId;
                }
               
                $model->role = User::ROLE_CUSTOMER;
                $model->created_at = time();
                if($model->save(false)){
                    if($shortName =='Facebook'){
                        $verifyuserdetails = $model->find()->where( ['email' => $socialuserEmail , 'facebook'=>$socialId] )->andWhere(['status' => User::STATUS_ACTIVE])->one();                
                        if($verifyuserdetails){
                            $identity = User::findOne(['email' => $verifyuserdetails['email'] , 'facebook' => $verifyuserdetails['facebook']]);
                        }
                    }elseif($shortName =='Google'){
                        $verifyuserdetails = $model->find()->where( ['email' => $socialuserEmail , 'googleplus'=>$socialId] )->andWhere(['status' => User::STATUS_ACTIVE])->one();                 
                        if($verifyuserdetails){
                            $identity = User::findOne(['email' => $verifyuserdetails['email'] , 'googleplus' => $verifyuserdetails['googleplus']]);
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
        
      
           
        $session = Yii::$app->session;
        $countryId = $session->get('countryId');
       /*
       
        $modelAd                    = new Ad();
        $modelBanner                = new Banner();
        $modelCategory              = new Category();
        $modelPromotionalBanner     = new PromotionalBanner();
        $modelCountry               = new Country();

        $bannerResult               =  $modelBanner->getAllBanner();
        $categoryResult             =  $modelCategory->getMainCategory();
        $promotionalBannerResult    =  $modelPromotionalBanner->getAllPromotionalBanner();
        $countryResult              =  $modelCountry->getCountryList();
       
       // print_r($countryResult);
        //die;
         /// featured ad 
        $query = Ad::find()
        ->innerJoinWith('locations')
        ->where(['ad.status' => Ad::STATUS_ACTIVE, 'ad.featured'=>'1'])
        ->orderby(['ad.created_at' => SORT_DESC]);
        $query->andFilterWhere([
            'user_location.country_id' => $countryId,
        ]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 4]
        ]);
        //$dataProvider->pagination->pageSize=1;


         /// deal of the day 

         $currentTime   =   time();
         $query = Ad::find()
         ->innerJoinWith('locations')
         ->where(['ad.status' => Ad::STATUS_ACTIVE])
         ->andWhere(['<','ad.deal_start_date',$currentTime])
         ->andWhere(['>','ad.deal_end_date',$currentTime])
         ->orderby(['ad.created_at' => SORT_DESC]);

         $query->andFilterWhere([
            'user_location.country_id' => $countryId,
        ]);

         $dataProviderDeal = new ActiveDataProvider([
             'query' => $query,
             'pagination' => ['pageSize' => 4]
         ]);
         //$dataProvider->pagination->pageSize=1;    */


         $bannerResult=[];


        return $this->render('index',[
            'bannerResult'=>$bannerResult,
           
        ]
        );
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
            
            $modelPost              = new Post();

            $modelSetting           = new Setting();
            $resultSetting          = $modelSetting->getSettingData();
                        

            $postResult = $modelPost->find()->where(['unique_id'=>$pid])->one();
            if(!$postResult){
                throw new BadRequestHttpException('Envalid request');

            }
            
            return $this->render('share',[
                'postResult'=>$postResult,
                'setting' =>$resultSetting
               
            ]);
            
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
       

       
    }

}