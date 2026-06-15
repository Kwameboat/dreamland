<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use yii\imagine\Image;


use api\modules\v1\models\Ad;
use api\modules\v1\models\AdSearch;
use api\modules\v1\models\AdImage;
use api\modules\v1\models\User;
use api\modules\v1\models\Package;
use api\modules\v1\models\UserLocation;
use api\modules\v1\models\Setting;
use api\modules\v1\models\ReportedAd;
use api\modules\v1\models\AdSubscription;
use api\modules\v1\models\Post;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class AdController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\ad';   
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


    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $packageId  =Yii::$app->user->identity->package_id;
        $model =   new Ad();
        $modelSetting =   new Setting();

        $model->scenario ='create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
            
                return $response;
            }

        }

        
     //   $totalJob = $model->getUserActiveJob($userId);
        // $modelSubscription = new AdSubscription();
      
        // $subcriptionResult =  $modelSubscription->getCurrentSubscription($userId);
      
        // if(!$subcriptionResult ){
            
        //     $response['statusCode']=422;
        //     $response['message']='You have not active package subsciption, Please subscribe the package';
        //     return $response;

        // }

        
        
        // if($subcriptionResult->ad_remaining <= 0) {
        //     $response['statusCode']=422;
        //     $response['message']='You have already reach the limit to post maximum ads';
        //     return $response;
        // }

       
        $setting = $modelSetting->getSettingData();
        $model->start_date=  time();
        if($setting->ads_auto_approve){
            $model->status=  Ad::STATUS_ACTIVE;
            // $model->expire_date=  $model->getAdExpiry();

        }

        //$model->package_type            = $subcriptionResult->package->type;
        //$model->package_id              = $subcriptionResult->package->id;
        //$model->package_banner_id       = $subcriptionResult->package->promotional_banner_id;

        if($model->save(false)){
           
            ///// updte ad remaining limit
            // $subcriptionResult->ad_remaining  = $subcriptionResult->ad_remaining-1;
            // $subcriptionResult->save(false);


            $adId = $model->id;
            $modelAdImage =  new AdImage();
            $params = Yii::$app->getRequest()->getBodyParams();
            
            $modelAdImage->updateAdImages($adId,$params['images']);
            
            $modelUserLocation =  new UserLocation();

            $userId = Yii::$app->user->identity->id;
            $modelUserLocation->updateUserLocation($userId,$params['locations'],UserLocation::TYPE_AD,$adId);

            
            $response['message']='Ad successfully Added';
            $response['ad_id']=$adId;
            
            return $response; 

        }
    }



    public function actionUpdate($id)
    {
        $modelSetting =   new Setting();
        $userId = Yii::$app->user->identity->id;
        $packageId  =Yii::$app->user->identity->ad_package_id;
        $model = $this->findModel($id);
        $model->scenario ='update';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        
        if($model->status != Ad::STATUS_PENDING && $model->status != Ad::STATUS_ACTIVE  ) {
            $response['statusCode']=422;
            $response['message']='This ad alreary process you can not edit this Ads';
            return $response;
        }
        $setting = $modelSetting->getSettingData();
      
        if($setting->ads_auto_approve==0){
          
            $model->status =  Ad::STATUS_PENDING;
        }
        
        if($model->save()){
            
            $adId = $model->id;
            $modelAdImage =  new AdImage();
            $params = Yii::$app->getRequest()->getBodyParams();
            
            $modelAdImage->updateAdImages($adId,$params['images']);
            
            $modelUserLocation =  new UserLocation();

            $userId = Yii::$app->user->identity->id;
            $modelUserLocation->updateUserLocation($userId,$params['locations'],UserLocation::TYPE_AD,$adId);
            
            $response['message']='Ad updated successfully';
            
            return $response; 

        }else{
            $response['errors']=$model->errors;
            
            return $response; 

        }
    
    }

    public function actionView($id){

        //$model = $this->findModel($id);
        $model = new Ad();
        $result  = $model->find()->where(['ad.id'=>$id])
        
        ->joinWith('locations')
         ->joinWith(['user' => function($query){
               $query->select(['id','image','name']);
           }
        ])
        ->select(['ad.id','ad.user_id','ad.category_id','ad.title','ad.sub_category_id','ad.status','ad.phone','ad.price','ad.view','ad.description','featured','featured_exp_date','currency','is_banner_ad','package_banner_id','ad.created_at','ad.deal_start_date','ad.deal_end_date','ad.deal_price'])
        ///->addSelect(['user.id','user.name'])
        
        /*->joinWith([
           'locations as u' => function($query){
               //$query->select(['u.name','u.id']);
           }
       ])*/
      //  ->asArray()
        ->one();

        $response['message']='ok';
        $response['ad']=$result;
        return $response;
    }
   

    // public function actionUploadImage()
    // {
        
    //     $model =  new AdImage();
        
    //     if (Yii::$app->request->isPost) {
    //         $model->load(Yii::$app->getRequest()->getBodyParams(), '');
    //         $model->imageFile = UploadedFile::getInstanceByName('imageFile'); 
    //         if(!$model->validate()) {
    //             $response['statusCode']=422;
    //             $response['errors']=$model->errors;
    //             return $response;
    //         }

    //         if($model->imageFile){
                    
    //             $microtime 			= 	(microtime(true)*10000);
    //             $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
    //             $imageName 			=	$uniqueimage;
    //             $model->image 		= 	$imageName.'.'.$model->imageFile->extension; 
    //             $imagePath 			=	Yii::$app->params['pathUploadAd'] ."/".$model->image;
    //             $imagePathThumb 	=	Yii::$app->params['pathUploadAdThumb'] ."/".$model->image;
    //             $imagePathMedium 	=	Yii::$app->params['pathUploadAdMedium'] ."/".$model->image;
    //             $model->imageFile->saveAs($imagePath,false);
    //             Image::thumbnail($imagePath, 500, 425)
    //                     ->save($imagePathMedium, ['quality' => 100]);

    //             Image::thumbnail($imagePath, 270, 200)
    //                     ->save($imagePathThumb, ['quality' => 100]);

    //         }
    //         $response['message']='Image updated successfully';
    //         $response['image']=Yii::$app->params['siteUrl'].Yii::$app->urlManagerFrontend->baseUrl.'/uploads/ad/medium/'.$model->image;
    //         return $response; 
    //     }
    // }

    /**
     * My ads
     * 
     */
    public function actionMyAd(){

        $model = new AdSearch();

        $result = $model->myAdSearch(Yii::$app->request->queryParams);
        
        $response['message']='Ads found successfully';
        $response['ad']=$result;
        
        return $response; 
    }


    public function actionUpdateStatus()
    {
        
        $model = new Ad();

        $model->scenario ='updateStatus';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }

        $id =  @(int) $model->adId;
        
       
       
        $modelAd = $this->findModel($id);

        $userId = Yii::$app->user->identity->id;

        if($modelAd->user_id!=$userId || $model->status == Ad::STATUS_ACTIVE ){
            $response['statusCode']=422;
            $response['message']='Ad already processed or wrong request';
            return $response; 
        }

      
        $modelAd->load(Yii::$app->getRequest()->getBodyParams(), '');
        if($modelAd->save(false)){
            $response['message']='Ad status updated successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $response['message']='Ad status not updated successfully';
            return $response; 


        }
    }

    public function actionReportAd()
    {
        
        $model = new Ad();
        $userId = Yii::$app->user->identity->id;
        
        $model->scenario ='reportAd';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }

       $adId =  @(int) $model->adId;
       
       $modelReportedAd = new ReportedAd();

       $totalCount = $modelReportedAd->find()->where(['ad_id'=>$adId, 'user_id'=>$userId,'status'=>ReportedAd::STATUS_PENDING])->count();
       if($totalCount>0){
          $response['statusCode']=422;
         $response['message']='You have already reported this Ad';
         return $response; 

       }

        $modelReportedAd->ad_id = $adId;
        $modelReportedAd->status = ReportedAd::STATUS_PENDING;

        



        if($modelReportedAd->save(false)){
            $response['message']='Ad reported successfully';
            return $response; 
        }else{
            $response['statusCode']=422;
            $response['message']='Ad not  reported successfully';
            return $response; 


        }
    }


    

    public function actionAdSearch()
    {
        
        $model = new AdSearch();

        $result = $model->search(Yii::$app->getRequest()->getBodyParams());
        
        $response['message']='Ads found successfully';
        $response['ad']=$result;
        
        return $response; 
        
    }
   




    protected function findModel($id)
    {
        if (($model = Ad::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


