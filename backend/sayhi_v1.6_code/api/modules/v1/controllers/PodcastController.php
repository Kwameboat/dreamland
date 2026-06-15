<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\Podcast;
use api\modules\v1\models\PodcastSearch;
use api\modules\v1\models\PodcastSubscriber;
use api\modules\v1\models\PodcastViewer;
use api\modules\v1\models\PodcastFavorite;
use api\modules\v1\models\TvShowSearch;
use api\modules\v1\models\Payment;
use api\modules\v1\models\PodcastEpisodeSearch;

/**
 * live tv Controller API
 *
 
 */
class PodcastController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Podcast';   
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


        $model = new PodcastSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['podcast']=$result;
        return $response;

        
    }


    
    public function actionSubscribe()
    {
        $userId                 = Yii::$app->user->identity->id;
     
        $model                  =   new Podcast();
        $modelLiveTvSubscriber   =   new PodcastSubscriber();
        $modelUser   =   new User();
        $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='subscribe';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $currentTime=time();
           $liveTvId =  @(int) $model->id;
           $resultLiveTv     = $model->findOne($liveTvId);
           
            if(!$resultLiveTv){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


            if(!$resultLiveTv->is_paid){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['podcast']['noNeedSubscribe'];
                $response['errors']=$errors;
                return $response;
            
            }
    
          
            $resultCount =$modelLiveTvSubscriber->find()->where(['user_id'=>$userId,'podcast_id'=>$liveTvId])->count();

            if($resultCount>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['podcast']['alreadySubscribed'];
                $response['errors']=$errors;
                return $response;
            
            }


            if($resultLiveTv->paid_coin > $resultUser->available_coin){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['podcast']['subscribeFeeNotAvailable'];
                $response['errors']=$errors;
                return $response;
            
            }

           
            //resultUser
            $modelLiveTvSubscriber->user_id       =   $userId;
            $modelLiveTvSubscriber->podcast_id    =   $liveTvId;
            $modelLiveTvSubscriber->paid_coin     =   $resultLiveTv->paid_coin;
            

            if($modelLiveTvSubscriber->save()){
                $resultUser->available_coin  =  $resultUser->available_coin-$resultLiveTv->paid_coin;
                if($resultUser->save(false)){
                    $modelPayment          = new Payment();
                    $modelPayment->type                 =  Payment::TYPE_COIN;
                    $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                    $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_LIVE_TV_SUBSCRIBE;
                    $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                    $modelPayment->coin                 =  $resultLiveTv->paid_coin;
                    $modelPayment->podcast_id           =  $liveTvId;
                    $modelPayment->transaction_id        = $model->transaction_id;

                    
                    
                    $modelPayment->save(false);




                }
                
                $response['message']=Yii::$app->params['apiMessage']['podcast']['subscribed'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            

            }

            
        }

       
        
    }

    
    public function actionStopViewing()
    {
        $userId                 = Yii::$app->user->identity->id;
     
        $model                  =   new Podcast();
        $modelLiveTvViewer       =   new PodcastViewer();
        $modelUser   =   new User();
        $resultUser = $modelUser->findOne($userId);
       
        $model->scenario ='stopViewing';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
          
           $liveTvId =  @(int) $model->id;
           $resultLiveTv     = $model->findOne($liveTvId);
           
            if(!$resultLiveTv){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            $modelLiveTvViewer->deleteAll( ['podcast_id' => $liveTvId,'user_id'=>$userId]);
            $response['message']=Yii::$app->params['apiMessage']['common']['actionSuccess'];
            return $response; 
           
            
        }

       
        
    }

    public function actionMySubscribedList(){


        $model = new PodcastSearch();

        $result = $model->podcastMySubscribed(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['podcast']=$result;
        return $response;

        
    }

    
    public function actionAddFavorite()
    {
        $userId                 = Yii::$app->user->identity->id;
     
        $model                  =   new Podcast();
        $modelLiveTvFavorite  =   new PodcastFavorite();
        $modelUser   =   new User();
     
       
        $model->scenario ='addFavorite';
        
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $liveTvId =  @(int) $model->id;
           $resultLiveTv     = $model->findOne($liveTvId);
           
            if(!$resultLiveTv){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


            $resultCount =$modelLiveTvFavorite->find()->where(['user_id'=>$userId,'podcast_id'=>$liveTvId])->count();

            if($resultCount>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['podcast']['alreadyFavorite'];
                $response['errors']=$errors;
                return $response;
            
            }


        
           
            //resultUser
            $modelLiveTvFavorite->user_id       =   $userId;
            $modelLiveTvFavorite->podcast_id    =   $liveTvId;
            
            if($modelLiveTvFavorite->save()){
               
                
                $response['message']=Yii::$app->params['apiMessage']['podcast']['AddFavorite'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            

            }

            
        }

       
        
    }

    
    public function actionRemoveFavorite()
    {
     
        $userId                 = Yii::$app->user->identity->id;
     
        $model                  =   new Podcast();
        $modelLiveTvFavorite  =   new PodcastFavorite();
        $modelUser   =   new User();
        $model->scenario ='removeFavorite';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $liveTvId =  @(int) $model->id;
           $resultLiveTv     = $model->findOne($liveTvId);
           
            if(!$resultLiveTv){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


            $resultFavorite =$modelLiveTvFavorite->find()->where(['user_id'=>$userId,'podcast_id'=>$liveTvId])->one();

            if(!$resultFavorite){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


        
            if($resultFavorite->delete()){
               
                
                $response['message']=Yii::$app->params['apiMessage']['podcast']['removedFavorite'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            

            }

            
        }

       
        
    }

    
    public function actionMyFavoriteList(){


        $model = new PodcastSearch();

        $result = $model->PodcastMyFavorite(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['podcast']=$result;
        return $response;

        
    }

    public function actionPodcastShows(){


        $model = new PodcastSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['podcast_show']=$result;
        return $response;

        
    }

    public function actionPodcastHostDetails($id){

        $model =  new Podcast();     
        $result = $model->find()->where(['podcast.id'=>$id])->one();      
        $response['podcastHostDetails']=   $result; 
        return $response; 
        
    }


}


