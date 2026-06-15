<?php
namespace frontend\controllers;

use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\imagine\Image;
use yii\web\Controller;
use yii\web\UploadedFile;
use frontend\models\ChangePassword;
use yii\data\ActiveDataProvider;
use frontend\models\Ad;
use common\models\Follower;
use common\models\Message;
use yii\helpers\Json;
use yii\web\Response;


/**
 * Site controller
 */
class UserController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup','detail','user-follower','user-following'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout', 'dashboard', 'profile', 'update-profile-image','update-mobile','update-add-mobile','update-add-mobile-firebase','verify-otp','detail','user-follower','user-following','follow-unfollow-user','my-follower','my-following'],
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
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        //  echo $hash = Yii::$app->getSecurity()->generatePasswordHash('123123');
        return $this->render('index');
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionDashboard()
    {

        $userId = Yii::$app->user->identity->id;
        $model = new Ad();
        $modelMessage = new Message();
        $query = $model->find()
            ->where(['<>','status',Ad::STATUS_DELETED])
            ->andWhere(['user_id'=>$userId])
            ->orderBy(['created_at'=>SORT_DESC]);

         $query->andwhere(['status'=>Ad::STATUS_ACTIVE]);
       
        $adStats =  $model->getAdCountStats();

        $totalMessage = $modelMessage->getTotalMessage();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' =>false
        ]);

            
        return $this->render('dashboard',[
            'model' => $model,
            'dataProvider' => $dataProvider,
            'adStats'=>$adStats,
            'totalMessage'=>$totalMessage,
            'type'=>'actuve'
        ]);
    }

    public function actionProfile()
    {
        $modelChangePassword = new ChangePassword();
        
        $userId = Yii::$app->user->identity->id;
        $modelUser = $this->findModel($userId);
        $modelUser->scenario = 'updateProfile';

        if ($modelUser->load(Yii::$app->request->post()) && $modelUser->validate()) {
            if($modelUser->save()){
                Yii::$app->session->setFlash('success', Yii::t('app', "Profile has been update"));
                return $this->redirect(['user/profile']);

            }
            
        }
      
        if ($modelChangePassword->load(Yii::$app->request->post()) && $modelChangePassword->validate()) {

            if($modelChangePassword->change()){
                Yii::$app->session->setFlash('success',Yii::t('app', "Password has been changed successfully"));
                return $this->redirect(['user/profile']);
            }
        }
       

        return $this->render('profile', [
            'modelUser' => $modelUser,
            'modelChangePassword'=>$modelChangePassword

        ]);
    }

    public function actionUpdateProfileImage()
    {
        $userId = Yii::$app->user->identity->id;
        $model = $this->findModel($userId);

        $model->scenario = 'updateProfileImage';

        if ($model->load(Yii::$app->request->post())) {

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->validate()) {

                if ($model->imageFile) {

                    $microtime = (microtime(true) * 10000);
                    $uniqueimage = $microtime . '_' . date("Ymd_His") . '_' . substr(md5($microtime), 0, 10);
                    $imageName = $uniqueimage;
                    $model->image = $imageName . '.' . $model->imageFile->extension;
                    $imagePath = Yii::$app->params['pathUploadUser'] . "/" . $model->image;
                    $imagePathThumb = Yii::$app->params['pathUploadUserThumb'] . "/" . $model->image;
                    $imagePathMedium = Yii::$app->params['pathUploadUserMedium'] . "/" . $model->image;
                    $model->imageFile->saveAs($imagePath, false);

                    Image::thumbnail($imagePath, 500, 500)
                        ->save($imagePathMedium, ['quality' => 100]);

                    Image::thumbnail($imagePath, 120, 120)
                        ->save($imagePathThumb, ['quality' => 100]);

                }

                if ($model->save(false)) {

                    Yii::$app->session->setFlash('success', Yii::t('app', "Profile image updated"));
                    return $this->redirect(['user/profile']);
                }

            }
        }

        return $this->render('update-profile-image', [
            'modelUser' => $model,

        ]);
    }


    public function actionUpdateMobile($resend=false)
    {
        
        $session  = Yii::$app->session;
        $userId = Yii::$app->user->identity->id;
        $modelUser = $this->findModel($userId);
        $modelUser->scenario = 'updateMobile';
        $otpSent =  false;
        if ($modelUser->load(Yii::$app->request->post()) && $modelUser->validate()) {
            
            if($modelUser->save(false)){
                Yii::$app->session->setFlash('success', Yii::t('app', "Mobile has been updated successfully"));
                return $this->redirect(['user/profile']);
            
            }else{
                    Yii::$app->session->setFlash('error', Yii::t('app', "Process failed, plz try again"));
            }

            
        }

        return $this->render('update-mobile', [
            'modelUser' => $modelUser,
            'otpSent' =>$otpSent

        ]);
    }


    /***
     * update mobile number   with otp verification
     * 
     * 
     */

    public function actionUpdateMobile_old($resend=false)
    {
        
        $session  = Yii::$app->session;
        $userId = Yii::$app->user->identity->id;
        $modelUser = $this->findModel($userId);
        $modelUser->scenario = 'updateMobile';
        $otpSent =  false;
        if ($modelUser->load(Yii::$app->request->post()) && $modelUser->validate()) {
            $otpSent =  true;
            
            if(Yii::$app->request->post('submit1')==2){ //
                
                if($session['otpMobile']['otp']==$modelUser->otp){
                    print_r($session['otpMobile']);
                    $modelUser->phone = $session['otpMobile']['phone'];
                    $modelUser->country_code = $session['otpMobile']['country_code'];
                    if($modelUser->save(false)){

                        Yii::$app->session->setFlash('success', Yii::t('app', "Mobile has been updated successfully"));
                         return $this->redirect(['user/profile']);
                    }
                }else{
                    Yii::$app->session->setFlash('error', Yii::t('app', "Wrong OTP"));

                }


            }else{
               
                $otp  = rand(1000,9999);
                $session['otpMobile'] = [
                    'phone' =>  $modelUser->phone,
                    'country_code' =>  $modelUser->country_code,
                    'otp' => $otp
                ];
               Yii::$app->session->setFlash('success', Yii::t('app', "Otp has been send. ".$otp));
              
            }

        }

        return $this->render('update-mobile', [
            'modelUser' => $modelUser,
            'otpSent' =>$otpSent

        ]);
    }



    ///popup mobile update
    public function actionUpdateAddMobile()
    {
      //  $this->layout = 'ss';
      $session  = Yii::$app->session;
      $userId = Yii::$app->user->identity->id;
      $modelUser = $this->findModel($userId);
      $modelUser->scenario = 'updateMobile';
      $otpSent =  false;
      //if ($modelUser->load(Yii::$app->request->post()) && $modelUser->validate()) {
      if (Yii::$app->request->isAjax && $modelUser->load(Yii::$app->request->post())) {
        
          
            Yii::$app->response->format = Response::FORMAT_JSON;
            
            if($modelUser->save(false)){
                $response['status']='success';
                $response['message']=Yii::t('app','Phone has been updated successfully');
                $response['phone']=$modelUser->phone;
                echo json_encode($response);
                die;
            }else{
                $response['status']='error';
                $response['message']=Yii::t('app','Wrong OTP');
                echo json_encode($response);
                die;
            }
        }

      
        return $this->renderAjax('update-add-mobile', [
            'modelUser' => $modelUser,
            'otpSent' =>$otpSent
        ]);
    }


    public function actionVerifyOtp()
    {
      //  $this->layout = 'ss';
      $session  = Yii::$app->session;
      $userId = Yii::$app->user->identity->id;
      $modelUser = $this->findModel($userId);
     /// $modelUser->scenario = 'updateMobile';
      //$otpSent =  false;
      //if ($modelUser->load(Yii::$app->request->post()) && $modelUser->validate()) {
        if (Yii::$app->request->isAjax && $modelUser->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
              if($session['otpMobile']['otp']==$modelUser->otp){
                 // print_r($session['otpMobile']);
                  $modelUser->phone = $session['otpMobile']['phone'];
                  $modelUser->country_code = $session['otpMobile']['country_code'];
                  
                  if($modelUser->save(false)){
                    $response['status']='success';
                    $response['message']=Yii::t('app','Phone has been updated successfully');
                    $response['phone']=$session['otpMobile']['phone'];
                    echo json_encode($response);
                 
                    die;
                  }
              }else{
                 $response['status']='error';
                $response['message']=Yii::t('app','Wrong OTP');
                    echo json_encode($response);
                  //
                  die;


              }

      }

      
        return $this->renderAjax('verify-otp', [
            'modelUser' => $modelUser,
            'otp'=>$session['otpMobile']['otp']
           
        ]);
    }

    ///popup mobile update firebase
    public function actionUpdateAddMobileFirebase()
    {
      //  $this->layout = 'ss';
      $session  = Yii::$app->session;
      $userId = Yii::$app->user->identity->id;
      $modelUser = $this->findModel($userId);
      $modelUser->scenario = 'updateMobile';
      $otpSent =  false;
      //if ($modelUser->load(Yii::$app->request->post()) && $modelUser->validate()) {
      if (Yii::$app->request->isAjax && $modelUser->load(Yii::$app->request->post())) {
        
          
            Yii::$app->response->format = Response::FORMAT_JSON;
            
            if($modelUser->save(false)){
                $response['status']='success';
                $response['message']=Yii::t('app','Phone has been updated successfully');
                $response['phone']=$modelUser->phone;
                echo json_encode($response);
                die;
            }else{
                $response['status']='error';
                $response['message']=Yii::t('app','Wrong OTP');
                echo json_encode($response);
                die;
            }
        }

      
        return $this->renderAjax('update-add-mobile-firebase', [
            'modelUser' => $modelUser,
            'otpSent' =>$otpSent
        ]);
    }


    public function actionDetail($id)
    {
        $modelUser  =  $this->findModel($id);
        $modelAd    = new Ad();
        $query = $modelAd->find()
            ->where(['<>','status',Ad::STATUS_DELETED])
            ->andWhere(['user_id'=>$id])
            ->orderBy(['created_at'=>SORT_DESC]);

         $query->andwhere(['status'=>Ad::STATUS_ACTIVE]);
       
    
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' =>false
        ]);

        

            
        return $this->render('user-detail',[
            'modelAd' => $modelAd,
            'dataProvider' => $dataProvider,
            'modelUser' =>$modelUser
            
        ]);
    }

    public function actionMyFollower()
    {
        $id = Yii::$app->user->identity->id;
        $modelUser  =  $this->findModel($id);
        $modelAd    = new Ad();
        return $this->render('my-follower',[
       
            'modelUser' =>$modelUser
            
        ]);

    }


    
    public function actionMyFollowing()
    {
        $id = Yii::$app->user->identity->id;
        $modelUser  =  $this->findModel($id);
        $modelAd    = new Ad();
        return $this->render('my-following',[
       
            'modelUser' =>$modelUser
            
        ]);
    }




    public function actionUserFollower($id)
    {
        $modelUser  =  $this->findModel($id);
        $modelAd    = new Ad();
        return $this->render('user-follower',[
       
            'modelUser' =>$modelUser
            
        ]);

    }

   
    public function actionUserFollowing($id)
    {
        $modelUser  =  $this->findModel($id);
        $modelAd    = new Ad();
        return $this->render('user-following',[
       
            'modelUser' =>$modelUser
            
        ]);
    }


     
    public function actionFollowUnfollowUser($id,$type='user')
    {
        $userId = Yii::$app->user->identity->id;
        $modelFollower = new Follower();
        $followreResult = $modelFollower->find()->where(['follower_id'=>$userId,'user_id'=>$id])->one();
        
        if($userId==$id){
            $msg=Yii::t('app','You can not follow yourself');
            Yii::$app->session->setFlash('error', $msg);

        }else{

        
            if(isset($followreResult->id)){
                $followreResult->delete();
                $msg=Yii::t('app','Unfollow user successfully');

            }else{

                $modelFollower->user_id = $id;
                if($modelFollower->save(false)){
                    $msg=Yii::t('app','Follow user successfully');

                }else{
                    $msg=Yii::t('app','Something was wrong');

                }

            }

            Yii::$app->session->setFlash('success', $msg);

        }
        
        
        
        if($type=='my'){
            return $this->redirect(['user/my-following']);
        }else{
            return $this->redirect(['user/detail','id'=>$id]);
        }
        
        die;

        
    }



    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}