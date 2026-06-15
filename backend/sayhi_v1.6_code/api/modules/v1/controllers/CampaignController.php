<?php

namespace api\modules\v1\controllers;
use yii\rest\ActiveController;
use yii;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\Campaign;
use api\modules\v1\models\CampaignSearch;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\User;
use api\modules\v1\models\CampaignComment;
use api\modules\v1\models\CampaignFavorite;
use api\modules\v1\models\Payment;
use api\modules\v1\models\PaymentSearch;
use api\modules\v1\models\Post;


class CampaignController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\campaign';   
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
    
    public function actions()
	{
		$actions = parent::actions();
		unset($actions['create'], $actions['update'], $actions['index'], $actions['delete']);                    

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


        $model = new CampaignSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['campaign']=$result;
        return $response;

        
    }

// add comment

    public function actionAddComment()
    {
        $model = new CampaignComment();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
         $parentId = (int)@$model->parent_id;
         $model->status=CampaignComment::STATUS_ACTIVE;
         if($parentId>0){
            $model->level = CampaignComment::LEVEL_TWO;
         }
         $model->parent_id = $parentId;
        if ($model->save(false)) {
          
            $response['message'] = Yii::$app->params['apiMessage']['post']['commentSuccess'];
            $response['id'] = $model->id;
            return $response;
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['coomon']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }


    // comment list 
    public function actionCommentList()
    {   
        
        $model = new CampaignComment();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'list';
    
        $model->load(Yii::$app->request->queryParams, '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $campaignId = @(int) $model->campaign_id;
        $parent_id = @(int) $model->parent_id;
        $query = $model->find()
            ->where(['campaign_id'=>$campaignId,'campaign_comment.status'=>CampaignComment::STATUS_ACTIVE])
            ->joinWith(['user' => function ($query) {
                $query->select(['id', 'name','username', 'image','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
            }]);
            if($parent_id){
                $query->andWhere(['campaign_comment.level'=> CampaignComment::LEVEL_TWO]);
                $query->andWhere(['campaign_comment.parent_id'=> $parent_id]);
            }else{
                $query->andWhere(['campaign_comment.level'=> CampaignComment::LEVEL_ONE]);
            }
        $result = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $response['message'] = 'ok';
        $response['comment'] = $result;

        return $response;

    }
    
    // Add  Favrioute
    public function actionAddFavorite()
    {
      
        $userId                 = Yii::$app->user->identity->id; 
        $model                  =   new Campaign();
        $modelCampaignFavorite  =   new CampaignFavorite();
        $modelUser   =   new User();
        //   $model->scenario ='addFavorite';
        if (Yii::$app->request->isPost) {
           
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
          
            if(!$model->validate()) {
              
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
           $campaignId = @(int) $model->id;
           
           $resulCampaign     = $model->findOne($campaignId);
           
            if(!$resulCampaign){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            $resultCount =$modelCampaignFavorite->find()->where(['user_id'=>$userId,'campaign_id'=>$campaignId])->count();

            if($resultCount>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['podcast']['alreadyFavorite'];
                $response['errors']=$errors;
                return $response;
            
            }

            //resultUser
            $modelCampaignFavorite->user_id       =   $userId;
            $modelCampaignFavorite->campaign_id    =   $campaignId;
            
            if($modelCampaignFavorite->save()){
               
                
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
        $model                  =   new Campaign();
        $modelLiveTvFavorite  =   new CampaignFavorite();
        $modelUser   =   new User();
        // $model->scenario ='removeFavorite';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
             $campaigneId = @(int) $model->id;
       
           $resultampaigner     = $model->findOne($campaigneId);

            if(!$resultampaigner){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


            $resultFavorite =$modelLiveTvFavorite->find()->where(['user_id'=>$userId,'campaign_id'=>$campaigneId])->one();

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

   
    // public function actionMyFavoriteList(){
    //     $model =  new Campaign();
    //     $modelRes= $model->find()->one();
        
       
    //    $response['message']='ok';
    //     $response['campaign']=$modelRes;
    //     return $response;
    // }

    public function actionMyFavoriteList()
    {

        $model = new CampaignSearch();

        $result = $model->CampaignMyFavorite(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['campaignFavoriteList']=$result;
        return $response;

        
    }

    public function actionPayment(){
        $userId = @Yii::$app->user->identity->id; 
        $model = new Campaign();
        $modelUser = new User();
        $modelPayment = new Payment();
        $model->scenario = 'campaignPayment';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }
        $campaigneId = @(int) $model->id;
        $amount = @$model->amount;
        $resultcampaign     = $model->findOne($campaigneId);

        if(!$resultcampaign ){
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
            $response['errors']=$errors;
            return $response;
        
        }
         #region check availabe balance
         $resultUser = $modelUser->findOne($userId);
         foreach($model->payments as $payment){
             if($payment['payment_mode']==Payment::PAYMENT_MODE_WALLET && $payment['amount'] > $resultUser->available_balance ){
                  $response['statusCode']=422;
                  $errors['message'][] = Yii::$app->params['apiMessage']['order']['amountNotAvailable'];
                  $response['errors']=$errors;
                  return $response;
  
 
              }
 
          }
          #endregion
         $oldRaised_amount = $resultcampaign->raised_value;
         $resultcampaign->raised_value =  $oldRaised_amount+$amount;
     
          if ($resultcampaign->save(false)) {

            foreach($model->payments as $payment){
                // print_r($payment['amount']);
                     $modelPayment                       =  new Payment();
                     $modelPayment->user_id              =  $userId;
                     $modelPayment->campaign_id      =  $model->id;
                     $modelPayment->amount               =  $payment['amount'];
                     $modelPayment->transaction_id       =  $payment['transaction_id'];
                     $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                     $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_CAMPAIGN;
                     $modelPayment->payment_mode         =   $payment['payment_mode'];
                     $modelPayment->status               =  Payment::PROCESSED_STATUS_COMPLETED;
                     $modelPayment->save(false);


                     if($payment['payment_mode']==Payment::PAYMENT_MODE_WALLET){

                         $resultUser = $modelUser->findOne($userId);
                         $resultUser->available_balance   = $resultUser->available_balance - $payment['amount'];
                         $resultUser->save(false);

                     }

            }
            
            
            $response['message'] = Yii::$app->params['apiMessage']['campaignPayment']['createdSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }
    

    public function actionDonorsList(){


        $model = new PaymentSearch();

        $result = $model->searchCampaignPayment(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['donorsList']=$result;
        return $response;

        
    }
       
    
    public function actionDeleteComment()
    {
        $model = new CampaignComment();
        $userId = @Yii::$app->user->identity->id;
        $model->scenario = 'commentdelete';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $campaginer_comment_id = @(int) $model->id;

        $model = CampaignComment::find()->where(['id' => $campaginer_comment_id, 'user_id' => $userId])->andWhere(['status'=>CampaignComment::STATUS_ACTIVE])->one();

        if ($model) {
            $commentStatus = CampaignComment::deleteAll(['id'=>$campaginer_comment_id, 'user_id' => $userId]);
            if ($commentStatus) {

                $response['message'] = Yii::$app->params['apiMessage']['campaignPayment']['deleted'];

                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }else{
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

    }
   





}


