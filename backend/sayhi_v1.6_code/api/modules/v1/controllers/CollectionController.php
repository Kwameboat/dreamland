<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use yii\imagine\Image;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\User;
use api\modules\v1\models\Collection;
use api\modules\v1\models\CollectionUser;

class CollectionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\post';   
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
            //'except'=>['ad-search'],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }


    public function actionIndex()
    {
        $userId = Yii::$app->user->identity->id;
        $model =   new Collection();
        $results = $model->find()
        ->where(['user_id'=>$userId,'status'=>$model::STATUS_ACTIVE])->orderBy(['name'=> SORT_ASC])->all();
       
       
       $response['message']='ok';
        $response['collection']=$results;
        return $response;




    }


    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model =   new Collection();
        $model->scenario ='create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            if($model->save()){
                $response['message']=Yii::$app->params['apiMessage']['collection']['created'];
                $response['collection_id']=$model->id;
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }
    }

    public function actionUpdate($id)
    {
        $userId = Yii::$app->user->identity->id;
        $model =   Collection::find()->where(['id'=>$id,'user_id'=>$userId])->one();
       // $model->scenario ='create';
       

        $model =  $this->findModel($id);
        $model->scenario ='update';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        if($model->save()){
            $response['message']=Yii::$app->params['apiMessage']['collection']['updated'];
            $response['collection_id']=$model->id;
            return $response; 
        }else{
            $response['statusCode']=422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors']=$errors;
            return $response;
        }
      
    }


    public function actionDelete($id)
    {
        $userId = Yii::$app->user->identity->id;
        
        $model =   Collection::find()->where(['id'=>$id,'user_id'=>$userId])->one();

        if( $model){
      
            if($model->delete()){
                $modelCollectionUser = new CollectionUser();
                $modelCollectionUser->deleteAll(['collection_id'=>$id]); 

                $response['message']=Yii::$app->params['apiMessage']['collection']['deleted'];
             
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }
      
    }


    public function actionAddPost()
    {

        $userId = Yii::$app->user->identity->id;
        $model =   new CollectionUser();
        $model->scenario ='create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $resultCollection =   Collection::findOne($model->collection_id);

            if(!$resultCollection){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;

            }



            if($resultCollection->user_id !=$userId){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;

            }

            $isAlreadyAdded = $model->find()->where(['collection_id'=>$model->collection_id,'post_id'=>$model->post_id])->count(); 

            if($isAlreadyAdded>0){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['collection']['alreadyAddedInList'];
                $response['errors']=$errors;
                return $response;

            }


            if($model->save()){
                $response['message']=Yii::$app->params['apiMessage']['collection']['addedCollection'];
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }

    }


    public function actionRemovePost()
    {

        $userId = Yii::$app->user->identity->id;
        $model =   new CollectionUser();
        $model->scenario ='delete';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $resultCollectionUser =   CollectionUser::findOne($model->id);

            if(!$resultCollectionUser){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;

            }


            $resultCollection =   Collection::findOne($resultCollectionUser->collection_id);


            if($resultCollection->user_id !=$userId){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;

            }

           
            if($resultCollectionUser->delete()){
                $response['message']=Yii::$app->params['apiMessage']['collection']['removedCollection'];
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }

    }




    protected function findModel($id)
    {
        if (($model = Collection::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


