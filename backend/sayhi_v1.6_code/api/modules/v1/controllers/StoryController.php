<?php
namespace api\modules\v1\controllers;
use api\modules\v1\models\ReportedStory;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use yii\imagine\Image;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\User;
use api\modules\v1\models\Story;
use api\modules\v1\models\StorySearch;
use api\modules\v1\models\StoryView;
use yii\web\NotFoundHttpException;

class StoryController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\story';   
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

        $model = new StorySearch();
        $results = $model->searchStory(Yii::$app->request->queryParams);

        $userArr=[];
        foreach($results as $result){
         

          $key = array_search($result->user->id, array_column($userArr, 'id'));
          
          if(is_int($key)){
            $prUserPost =  $userArr[$key]['userStory'];
            $prUserPost[]=$result;
            $userArr[$key]['userStory']=$prUserPost;
          }else{
            
            $user= $result->user;
            $resultArray=[];
            $resultArray[] = $result;
            $user['userStory'] = $resultArray;
            $userArr[]=$user;

          }
        }

        $response['message'] = Yii::$app->params['apiMessage']['story']['listFound'];
        $response['story'] = $userArr;
       // $response['post'] = $results;
        return $response;

    }

    // public function actionCreate()
    // {
    //     $userId = Yii::$app->user->identity->id;
    //     $model =   new Story();
    //     $model->scenario ='createMain';
        
    //     if (Yii::$app->request->isPost) {
            
    //         $model->load(Yii::$app->getRequest()->getBodyParams(), '');
         
    //         $stroyIds=[];
    //         $isProcess=false;

    //         foreach($model->stories as $story){
    //             $modelStory =   new Story();
    //             $modelStory->type = @$story['type'];
    //             $modelStory->image = @$story['image'];
    //             $modelStory->video = @$story['video'];
    //             $modelStory->description = @$story['description'];
    //             $modelStory->background_color = @$story['background_color'];
    //             $modelStory->video_time = (int)@$story['video_time'];
                
    //             if($modelStory->save(false)){
    //                 $stroyIds[]=$modelStory->id;
    //                 $isProcess=true;
    //             }
    //         }

    //         if($isProcess){
    //             $response['message']=Yii::$app->params['apiMessage']['story']['created'];
    //             $response['story_ids']=$stroyIds;
    //             return $response; 
    //         }else{
    //             $response['statusCode']=422;
    //             $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
    //             $response['errors']=$errors;
    //             return $response;
    //         }
            
    //     }
    // }


    
   

    public function actionView($id)
    {
        $result =   Story::find()->where(['id'=>$id])->one();
 
        $response['message'] = Yii::$app->params['apiMessage']['story']['listFound'];
        $response['story'] = $result;
        return $response;
      
    }

   

    public function actionMyStory()
    {

        
        $model = new StorySearch();
        
        
        $result = $model->searchMyStory(Yii::$app->request->queryParams);
        

        
        $response['message'] = Yii::$app->params['apiMessage']['story']['listFound'];
        $response['story'] = $result;
        return $response;

    }

    public function actionMyActiveStory()
    {

        
        $model = new StorySearch();
        
        
        $result = $model->searchMyActiveStory(Yii::$app->request->queryParams);
        

        
        $response['message'] = Yii::$app->params['apiMessage']['story']['listFound'];
        $response['story'] = $result;
        return $response;

    }




    public function actionDelete($id)
    {
        $userId = Yii::$app->user->identity->id;
        
        $model =   Story::find()->where(['id'=>$id,'user_id'=>$userId])->one();

        if( $model){
            $model->status = Story::STATUS_DELETED;
            if($model->save(false)){
                
                $response['message']=Yii::$app->params['apiMessage']['story']['deleted'];
             
                return $response; 
            }else{
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            }
        }
      
    }

    
    public function actionViewCounter()
    {
        $model = new StoryView();
        $userId = @Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $storyId = @(int) $model->story_id;
        $storyData = Story::find()->where(['id' => $storyId])->one();
        if (empty($storyData)) {
            $response['statusCode'] = 422;
            $response['errors'] = 'Story not found';
            return $response;
        }
       
        $result = $model->find()->where(['story_id' => $storyId, 'user_id' => $userId])->one();
        if (empty($result)) {
            $model->save(false);
        }
        $response['message'] = 'ok';
        return $response;
    }


    
    public function actionStoryViewUser($id)
    {

        $model = new StoryView();
        $query = $model->find()->where(['story_id'=>$id])
        ->joinWith(['user' => function ($query) {
            $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
        }]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>  [
                'pageSize' => 20
            ]
        ]);
        
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['story-view'] = $dataProvider;
       // $response['post'] = $results;
        return $response;

    }

    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model =   new Story();
        $model->scenario ='createMain';
        
        if (Yii::$app->request->isPost) {
            
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
         
            $stroyIds=[];
            $isProcess=false;
            
           $fileLocation= Yii::$app->fileUpload->getUploadedLocation(Yii::$app->fileUpload::TYPE_STORY);
           $folderLocationPath = $fileLocation['folderLocation'];
            foreach($model->stories as $story){
                $modelStory =   new Story();
                $modelStory->type = @$story['type'];
                $modelStory->image = @$story['image'];
                $modelStory->video = @$story['video'];              
                $modelStory->description = @$story['description'];
                $modelStory->background_color = @$story['background_color'];
                if(!empty(@$story['video_time'])){
                    $modelStory->video_time = (int)@$story['video_time'];
                }else{
                    $url = $folderLocationPath.'/'.@$story['video'];
                    $videoDuration = $modelStory->getVideoDuration($url);
                    if (!empty($videoDuration)) {
                    $modelStory->video_time = (int)@$videoDuration['duration'];
                }
                }
                
                
                if($modelStory->save(false)){
                    $stroyIds[]=$modelStory->id;
                    $isProcess=true;
                }
            }

            if($isProcess){
                $response['message']=Yii::$app->params['apiMessage']['story']['created'];
                $response['story_ids']=$stroyIds;
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
     * Report Story
     */
    public function actionReportStory()
    {

        $model = new ReportedStory();
        $userId = @Yii::$app->user->identity->id;

        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $story_id = @(int) $model->story_id;

        $totalCount = $model->find()->where(['story_id' => $story_id, 'user_id' => $userId, 'status' => ReportedStory::STATUS_PENDING])->count();
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['story']['alreadyReported'];
            $response['errors'] = $errors;
            return $response;

        }

        $model->status = ReportedStory::STATUS_PENDING;
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['story']['reportedSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }

    protected function findModel($id)
    {
        if (($model = Story::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}


