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
use Twilio\Rest\Client;

/**
 * User Controller API
 *

 */
class UserController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\user';   
    
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
            'only'=>['profile-update','update-payment-detail','update-profile-image','nearest-user','update-mobile','verify-otp','search-user','report-user'],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }
    /**
     * Login user
     */
    public function actionLogin(){
        $model =  new User();
        $model->scenario ='login';
        
        $request = Yii::$app->request;
        
        $params = $request->bodyParams;
        $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
       $user =  $model->checkLogin();
       if($user){

            $authKey = Yii::$app->security->generateRandomString();
            $user->auth_key = $authKey;
            $user->last_active = time();
            $user->device_token = $params['device_token'];
            $user->device_type = $params['device_type'];

          
            if($user->save(false)){
                
                $userProfile = $model->getProfile($user->id);
                
                $response['message']='Looged in successfully';
                $response['user']= $userProfile;
                $response['auth_key']= $userProfile->auth_key;

                return $response; 

            }


       } else {
            $response['statusCode']=401;
            $errors['message'][] = "Email/password incorrect";
            $response['errors']=$errors;
            //$response['message']='Email/password incorrect';
            return $response;

       }

        
    }

/**
     * Login user
     */
    public function actionLoginSocial(){
        
        $model =  new User();
        $model->scenario ='loginSocial';
        
        $request = Yii::$app->request;
        
        $params = $request->bodyParams;
        $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
       $user =  $model->checkLoginSocail($params);

       if($user){

            $authKey = Yii::$app->security->generateRandomString();
            $user->auth_key = $authKey;
            $user->last_active = time();
            $user->device_token = $params['device_token'];
            $user->device_type = $params['device_type'];

          
            if($user->save(false)){
                
                $userProfile = $model->getProfile($user->id);
                
                $response['message']='Looged in successfully';
                $response['user']= $userProfile;
                $response['auth_key']= $userProfile->auth_key;
                return $response; 

            }



       } else {
            $response['statusCode']=401;
            $errors['message'][] = "Something is wrong to login";
            $response['errors']=$errors;
            return $response;

       }

        
    }


    /**
     * Forgot password
     */
    public function actionForgotPassword(){
        
        $model =  new User();
        $model->scenario ='forgotPassword';
        
        $request = Yii::$app->request;
        
        $params = $request->bodyParams;
        
        $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $user = $model->find()->where(['email' => $params['email'], 'status' => User::STATUS_ACTIVE])->one();
        if($user){

            
           $password = Yii::$app->security->generateRandomString(8);
          
           $user->password_hash = Yii::$app->security->generatePasswordHash($password);


           $fromMail = Yii::$app->params['senderEmail'];
           $fromName = Yii::$app->params['senderName'];
           $from = array($fromMail =>$fromName);

           if($user->save(false)){
                     Yii::$app->mailer->compose()
                     ->setSubject('Passowrd Reset')
                     ->setFrom($from)
                    ->setTo($model->email)
                    ->setHtmlBody('Hi '.$user->name.'<br>Your password has been successfully upated.<br> New password is : <b>'.$password.'</b>')
                    ->send();              
                $response['message']='New Password has sent on your email';
               // $response['pass']=$password;
                return $response; 
        

           }else{
                $response['statusCode']=422;
                $errors['message'][] = "Action failed, Please try again";
                $response['errors']=$errors;
                return $response; 
           }
        }else{
            $response['statusCode']=422;
            $errors['message'][] = "Email not registered with us";
            $response['errors']=$errors;

            return $response; 
        }
    }
    /**
     * Register user
     */
    public function actionRegister(){
        $model =  new User();
        $modelPackage =  new Package();

        
        $model->scenario ='register';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $model->role =  $model::ROLE_CUSTOMER;
        $model->status = $model::STATUS_ACTIVE;
     //   $model->name =  'Guest';
        $defaultPackage = $modelPackage->getDefaultPackage();
        if($defaultPackage){
            $model->available_coin =  $defaultPackage->coin;
        }
    
       
        if($model->save()){
            
            
            $userProfile = $model->getProfile($model->id);
            
            $response['message']='User Register successfully';
            $response['user']   = $userProfile;
            $response['auth_key']= $userProfile->auth_key;
            return $response; 

        }
    }


    /**
     * my Profile
     *      */
    public function actionProfile(){

        $headers = Yii::$app->request->headers;
        print_r($headers);
        die;
        $model =  new User();
        $modelPackage =  new Package();
        $id = Yii::$app->user->identity->id;
        $userProfile = $model->getFullProfileMy($id);
        //$response['user']= $userProfile;
        $response['user']=   $userProfile; 
        return $response; 

        
    }


    /**
     * user Profile 
     */
    public function actionView($id){
        $model =  new User();
        $modelPackage =  new Package();
        $userProfile = $model->getFullProfile($id);
        
        $response['user']=   $userProfile; 
        return $response; 

        
    }


    /**
     * Profile user
     */
    public function actionProfileUpdate(){
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        
        $model->scenario ='profileUpdate';
        
        /*$request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes=$params;
        */
        
            
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        
        
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        
        if($model->save(false)){
            
          //  $modelUserLocation =  new UserLocation();
            //$modelUserLocation->updateUserLocation($id,$params['locations']);
            
            $response['message']='Profile Updated successfully';
            return $response; 

        }
        
    }

    

    /**
     * Profile user
     */
    public function actionUpdatePaymentDetail(){
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        
        $model->scenario ='profilePaymentDetail';
            
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        
        if($model->save(false)){
            
            $response['message']='Payment detail Updated successfully';
            return $response; 

        }
        
    }


    

    /**
     * update mobile
     */
    public function actionUpdateMobile(){
        $id = Yii::$app->user->identity->id;
       $model= new User();
        $model->scenario ='updateMobile';
       $request = Yii::$app->request;
       $params = $request->bodyParams;
       $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $string=$params['phone'].'#'.$params['country_code'];

       $token   =  base64_encode($string);
       $otp = mt_rand(100000, 999999);
       
       $modelRes = $this->findModel($id);
       $modelRes->verification_token  = $otp;
       if($modelRes->save(false)){
         //$token1=  base64_decode($token);
            $otpString ="OTP:".$otp;
            $response['message']="OTP has been sent on your mobile for confiration. ".$otpString;
            $response['verify_token']=$token;
            return $response; 

       }else{
        
            $response['statusCode']=422;
            $errors['message'][] = "Sending otp is failed, Please try again";
            $response['errors']=$errors;
            return $response; 
       }
        
    }



    
    /**
     * update mobile without verifcation
     */
    public function actionChangeMobile(){
        $id = Yii::$app->user->identity->id;
       $model= new User();
        $model->scenario ='updateMobile';
       $request = Yii::$app->request;
       $params = $request->bodyParams;
       $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $string=$params['phone'].'#'.$params['country_code'];

       $token   =  base64_encode($string);
       $otp = mt_rand(100000, 999999);
       
       $modelRes = $this->findModel($id);
       $modelRes->phone  = $params['phone'];
       $modelRes->country_code =$params['country_code'];
       if($modelRes->save(false)){
            $response['message']="Mobile number has been updated successfully";
            return $response; 

       }else{
        
            $response['statusCode']=422;
            $errors['message'][] = "Process failed, Please try again";
            $response['errors']=$errors;
            return $response; 
       }
        
    }

     /**
     * update mobile and verify
     */
    public function actionVerifyOtp(){
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        $model->scenario ='verifyMobileOtp';
        $request = Yii::$app->request;
        $params = $request->bodyParams;
        $model->attributes=$params;
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        $verify_token =$params['verify_token'];
        $token_arr =  explode('#',base64_decode($verify_token));
       $model->verification_token  = null;
       $model->phone  = $token_arr[0];
       $model->country_code  = $token_arr[1];
       
       if($model->save(false)){
            $response['message']="Phone has been updated successfully";
            return $response; 
       }else{
            $response['statusCode']=422;
            $errors['message'][] = "Phone update process failed";
            $response['errors']=$errors;
            return $response; 
       }
        
    }

    /**
     * update  Profile image user
     */
    public function actionUpdateProfileImage(){
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);

        $preImage = $model->image;
        $model->scenario ='updateProfileImage';

        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->imageFile = UploadedFile::getInstanceByName('imageFile'); 
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }

            if($model->imageFile){

                $microtime 			= 	(microtime(true)*10000);
                $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                $imageName 			=	$uniqueimage.'.'.$model->imageFile->extension;
                $model->image 		= 	$imageName; 

                $s3 = Yii::$app->get('s3');
                $imagePath = $model->imageFile->tempName;
                $result = $s3->upload('./'.Yii::$app->params['pathUploadUserFolder'].'/'.$imageName, $imagePath);

                /*
                $s3 = Yii::$app->get('s3');
                $imagePath = $model->imageFile->tempName;
                $result = $s3->upload('./'.Yii::$app->params['pathUploadUserFolder'].'/'.$imageName, $imagePath);
                */
               //  $s3->commands()->delete('./'.Yii::$app->params['pathUploadUserFolder'].'/'.$preImage)->execute(); /// delete previous
                //$promise = $s3->commands()->upload('./video-thumb/'.$imageName, $imagePath)->async()->execute();
            }

                    
                /*$microtime 			= 	(microtime(true)*10000);
                $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                $imageName 			=	$uniqueimage;
                $model->image 		= 	$imageName.'.'.$model->imageFile->extension; 
                $imagePath 			=	Yii::$app->params['pathUploadUser'] ."/".$model->image;
                $imagePathThumb 	=	Yii::$app->params['pathUploadUserThumb'] ."/".$model->image;
                $imagePathMedium 	=	Yii::$app->params['pathUploadUserMedium'] ."/".$model->image;
                $model->imageFile->saveAs($imagePath,false);
                
                
                Image::thumbnail($imagePath, 500, 500)
                        ->save($imagePathMedium, ['quality' => 100]);

                Image::thumbnail($imagePath, 120, 120)
                        ->save($imagePathThumb, ['quality' => 100]);

                       

            
            } */

            if($model->save()){
            
            $response['message']='Profile image updated successfully';
            return $response; 

            }

      
      }

        
    }

    /**
     * nearest user
     */
    public function actionNearestUser(){
        $userId= Yii::$app->user->identity->id;
        $model = new User();
        $model = $this->findModel($userId);
        $location = $model->userLocation;
        $cityIds=[];
        foreach($model->userLocation as $location)
        {
            $cityIds[] = $location->city_id;
        }
        $cityIds   = array_unique($cityIds);

        $user = $model->find()
            ->innerJoinWith('userLocation') 
            ->select(['user.id','user.name','user.username','user.email','user.description','user.phone','user.image'])
             ->where(['user_location.city_id' => $cityIds,'user.status'=>User::STATUS_ACTIVE,'user.role'=>User::ROLE_CUSTOMER ])
             ->andWhere(['<>','user.id',$userId])
             ->all();
    
        

            
        $response['message']='User list found successfully';
        $response['user']=$user;
        return $response; 


        
    }


    /**
     * nearest user
     */
    public function actionSearchUser(){
        $userId= Yii::$app->user->identity->id;
        $model = new User();
        //$model->scenario ='searchUser';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }


        $is_following_user =@(int)Yii::$app->getRequest()->getBodyParams()['is_following_user'];
        $is_follower_user =@(int)Yii::$app->getRequest()->getBodyParams()['is_follower_user'];
        $is_popular_user =@(int)Yii::$app->getRequest()->getBodyParams()['is_popular_user'];
        
        
        $query = $model->find()
            //->select(['user.id','user.name','user.username','user.email','user.description','user.phone','user.image'])
            ->select(['user.id','user.name','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','user.dob'])
             ->where(['user.role'=>User::ROLE_CUSTOMER])
             ->andwhere(['user.status'=>User::STATUS_ACTIVE]);

            if($model->name){
             $query->andWhere(['like','user.name',$model->name]);
            }elseif($is_following_user){
                
                $query->innerJoinWith('follower'); 
                $query->andWhere(['follower.follower_id'=>$userId]);
            }elseif($is_follower_user){
                
                $query->innerJoinWith('following'); 
                $query->andWhere(['follower.user_id'=>$userId]);
            }

        $user = $query->all();
    
        
            
        $response['message']='User list found successfully';
        $response['user']=$user;
        return $response; 


        
    }


    /**
     * Report user
     */
    public function actionReportUser()
    {
        
        
        $model = new ReportedUser();
        $userId = Yii::$app->user->identity->id;
        
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }

       $reportToUserId =  @(int) $model->report_to_user_id;
       
       $totalCount = $model->find()->where(['report_to_user_id'=>$reportToUserId, 'user_id'=>$userId,'status'=>ReportedUser::STATUS_PENDING])->count();
       if($totalCount>0){
        $response['statusCode']=422;
        $errors['message'][] = Yii::$app->params['apiMessage']['user']['alreadyReported'];
        $response['errors']=$errors;
         return $response; 

       }

       
        $model->status = ReportedUser::STATUS_PENDING;
        if($model->save(false)){
            $response['message']=Yii::$app->params['apiMessage']['user']['reportedSuccess'];
            return $response; 
        }else{

            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
        }
    }




    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


