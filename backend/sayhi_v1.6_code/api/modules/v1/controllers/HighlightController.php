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
use api\modules\v1\models\Highlight;
use api\modules\v1\models\HighlightStory;
use api\modules\v1\models\ReportedHighlight;

class HighlightController extends ActiveController
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
        //$userId = Yii::$app->user->identity->id;

        $userId = Yii::$app->request->queryParams['user_id'];


        $model =   new Highlight();
        $results = $model->find()
        ->joinWith(['highlightStory.story.user' => function($query){
            $query->select(['name','username','email','image','id']);
        }])
        ->where(['highlight.user_id'=>$userId,'highlight.status'=>$model::STATUS_ACTIVE])->orderBy(['highlight.name'=> SORT_ASC])->all();
       
       
       $response['message']='ok';
        $response['highlight']=$results;
        return $response;




    }


    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model =   new Highlight();
        $modelHighlightStory =   new HighlightStory();
        $model->scenario ='create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            
            if($model->save()){

                
                $modelHighlightStory->addStory($model->id,$model->story_ids);
                $response['message']=Yii::$app->params['apiMessage']['highlight']['created'];
                $response['hightlight_ids']=$model->id;
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
        $model =   Highlight::find()->where(['id'=>$id,'user_id'=>$userId])->one();
       
        $model->scenario ='update';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        if($model->save()){
            $response['message']=Yii::$app->params['apiMessage']['highlight']['updated'];
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
        
        $model =   Highlight::find()->where(['id'=>$id,'user_id'=>$userId])->one();

        if( $model){
            
            if($model->delete()){
                $modelHighlightStory = new HighlightStory();
                $modelHighlightStory->deleteAll(['highlight_id'=>$id]); 

                $response['message']=Yii::$app->params['apiMessage']['highlight']['deleted'];
             
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }
      
    }


    public function actionAddStory()
    {

        $userId = Yii::$app->user->identity->id;
        $model =   new HighlightStory();
        $model->scenario ='create';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $resultHighlight =   Highlight::findOne($model->highlight_id);

            if(!$resultHighlight){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;

            }



            if($resultHighlight->user_id !=$userId){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;

            }
            $storyIdsArr = explode(',',$model->story_ids);

        
            $isAlreadyAddedResult = $model->find()->select(['story_id'])->where(['highlight_id'=>$model->highlight_id,'story_id'=>$storyIdsArr])->all(); 

            if($isAlreadyAddedResult){
              
              foreach($isAlreadyAddedResult as $record){
                //echo $record->story_id;
                if (($key = array_search( $record->story_id, $storyIdsArr)) !== false) {
                    unset($storyIdsArr[$key]);
                }
              }
            }
            $story_ids = '';

            if(count($storyIdsArr)>0){
                $story_ids=implode(',',$storyIdsArr);
                $model->addStory($model->highlight_id,$story_ids);
            }

            $response['message']=Yii::$app->params['apiMessage']['highlight']['addedStory'];
            return $response; 
 
        }

    }


    public function actionRemoveStory()
    {

        $userId = Yii::$app->user->identity->id;
        $model =   new HighlightStory();
        $model->scenario ='delete';
        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$model->validate()) {
                $response['statusCode']=422;
                $response['errors']=$model->errors;
                return $response;
            }
            $resultHighlightStory =   HighlightStory::findOne($model->id);

            if(!$resultHighlightStory){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;

            }


            $resultHighlight =   Highlight::findOne($resultHighlightStory->highlight_id);


            if($resultHighlight->user_id !=$userId){

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
                $response['errors']=$errors;
                return $response;

            }

           
            if($resultHighlightStory->delete()){
                $response['message']=Yii::$app->params['apiMessage']['highlight']['removedStory'];
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }

    }

     /**
     * Report Highlight
     */
    public function actionReportHighlight()
    {

        $model = new ReportedHighlight();
        $userId = @Yii::$app->user->identity->id;

        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $highlight_id = @(int) $model->highlight_id;

        $totalCount = $model->find()->where(['highlight_id' => $highlight_id, 'user_id' => $userId, 'status' => ReportedHighlight::STATUS_PENDING])->count();
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['highlight']['alreadyReported'];
            $response['errors'] = $errors;
            return $response;

        }

        $model->status = ReportedHighlight::STATUS_PENDING;
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['highlight']['reportedSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }




    protected function findModel($id)
    {
        if (($model = Hightlight::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


