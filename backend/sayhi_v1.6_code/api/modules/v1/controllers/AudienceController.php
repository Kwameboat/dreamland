<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\Audience;
use api\modules\v1\models\AudienceKeyword;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use api\modules\v1\models\PromotionInterest;
use api\modules\v1\models\PromotionLocation;

class AudienceController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\audience';   
    
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

    public function actionIndex()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new Audience();
        $result  = $model->find()
            //->select(['id','name','description','created_at'])
            ->where(['user_id'=>$userId,'status'=>Audience::STATUS_ACTIVE])
            ->orderBy(['name'=>SORT_ASC])
            ->all();
       
        $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
        $response['audience']=$result;
        return $response;
       
    }


    public function actionCreate()
    {
       
        $model = new Audience();
        $modelAudienceInterest = new PromotionInterest();
        $modelPromotionLocation = new PromotionLocation();
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }
        if($model->save(false)){

            $audienceId = $model->id;
            // ad keyworkd  
            if($model['interest']){
                $modelAudienceInterest->updatePromotionInterest($audienceId,$model['interest']);
            }
             
            $locationType = @$model['location_type'];
            if($locationType ==Audience::LOCATION_TYPE_REGIONAL){
                if($model['country_id'] ){
                    $modelPromotionLocation->updatePromotionLocation($audienceId,$model['country_id'],'country'); 
                }
                if($model['state_id'] ){
                    $modelPromotionLocation->updatePromotionLocation($audienceId,$model['state_id'],'state');
                }
                if($model['city_id'] ){
                    $modelPromotionLocation->updatePromotionLocation($audienceId,$model['city_id'],'city');
                }
                     
            }
            $response['message']=Yii::$app->params['apiMessage']['audience']['createdSuccess'];
            return $response; 
        }else{

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    
    }

    public function actionUpdate($id)
    {
        
        $userId = Yii::$app->user->identity->id;
        $modelAudience = new Audience();
        $modelAudienceInterest = new PromotionInterest();
        $modelPromotionLocation = new PromotionLocation();

       // $modelAudienceDelete= $modelAudience->find()->where(['id'=>$id,'user_id'=>$userId])->one();
        //$model= new Audience();
        $model= $modelAudience->find()->where(['id'=>$id,'user_id'=>$userId])->one();
        if(!empty($model)){

        $model->scenario ='update';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        if($model->save(false)){
            /*if($modelAudienceDelete){
                $modelAudienceDelete->status = Audience::STATUS_DELETED;
                $modelAudienceDelete->save();
            }*/

            $id = $model->id;
            if($model['interest']){
                $modelAudienceInterest->updatePromotionInterest($id,$model['interest']); 
            }

            $locationType = @$model['location_type'];
            if($locationType ==Audience::LOCATION_TYPE_REGIONAL){
                if($model['country_id'] ){
                    $modelPromotionLocation->updatePromotionLocation($id,$model['country_id'],'country'); 
                }
                if($model['state_id'] ){
                    $modelPromotionLocation->updatePromotionLocation($id,$model['state_id'],'state');
                }
                if($model['city_id'] ){
                    $modelPromotionLocation->updatePromotionLocation($id,$model['city_id'],'city');
                }
                     
            }
            $response['message']=Yii::$app->params['apiMessage']['audience']['updatedSuccess'];
            return $response; 
        }else{

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


    public function actionDelete($id){
        
        $userId = Yii::$app->user->identity->id;
        $modelAudience = new Audience();

        $model= $modelAudience->find()->where(['id'=>$id,'user_id'=>$userId])->one();
        if($model){
            $model->status = Audience::STATUS_DELETED;
            if($model->save(false)){
                
                $response['message']=Yii::$app->params['apiMessage']['audience']['deletedSuccess'];
                return $response; 
            }

            
        }else{

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
        
    }


    

    protected function findModel($id)
    {
        if (($model = Audience::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }




}


