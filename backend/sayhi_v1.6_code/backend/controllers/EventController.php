<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
//use common\models\Category;
//use backend\models\CategorySearch;
use common\models\Competition;
use common\models\Event;
//use common\models\EventOrganisor;
use common\models\Organization;

use common\models\Post;
use common\models\User;
use common\models\Notification;


use common\models\Payment;
use common\models\EventGallaryImage;
use common\models\CompetitionPosition;

use common\models\Category;

use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;


/**
 * 
 */
class EventController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'winning' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::EVENT),
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex()
    {
        
        $model = new Event();
        $query = $model->find()
        ->where(['<>','status',Event::STATUS_DELETED]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);


        return $this->render('index', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Countryy model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model  = $this->findModel($id);
        
        return $this->render('view', [
            'model' =>   $model
        ]);
    }

    /**
     * Creates
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        
       // $modelCompetitionPosition       = new CompetitionPosition();
        $model = new Event();
        $modelCategory = new Category();
        
        $modelEventOrganisor = new Organization();
        
        $model->scenario= 'create';

        $modelEventGallaryImage = new EventGallaryImage();

        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_EVENT])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        $resultEventOrganisor = $modelEventOrganisor->find()->select(['id','name'])->where(['<>', 'status', Organization::STATUS_DELETED])->all();
        $eventOrganisorData = ArrayHelper::map($resultEventOrganisor,'id','name');

        
        if ($model->load(Yii::$app->request->post())  ) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->gallaryFile = UploadedFile::getInstances($model, 'gallaryFile');
           
            $preImage = $model->image;
          
            if($model->validate()){
                

                if($model->imageFile){

                    
                    $type =     Yii::$app->fileUpload::TYPE_EVENT;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file'];
                    
                }
                $model->start_date              = strtotime($model->start_date);
                $model->end_date                = strtotime($model->end_date);

                if( $model->save(false)){


                    /*$competitionPositionArr = Yii::$app->request->post('competitionPosition');
                    $competitionAwardArr = Yii::$app->request->post('competitionAward');
                    
                    $inputPosition['competitionId']          =  $model->id;
                    $inputPosition['competitionPosition']    =  $competitionPositionArr;
                    $inputPosition['competitionAward']       =  $competitionAwardArr;
        

                    
                    $modelCompetitionPosition->updateCompetitionPosition($inputPosition);

                    
                   */
                    
                    $images =[];
                    foreach ($model->gallaryFile as $file) {
                        
                        $type =     Yii::$app->fileUpload::TYPE_EVENT;
                        $files  = Yii::$app->fileUpload->uploadFile($file,$type,false);
                         $images[]	= 	  $files[0]['file']; 
                      

                    }
                    if(count($images)>0){
                        $modelEventGallaryImage->addPhoto($model->id,$images);
                    }


                    /*
                      
                    // send notification 

                    $modelUser = new User();
                    $userResult = $modelUser->find()->select(['id','device_token'])->where(['role'=>3,'status'=>10])->all();
                    $userIds=[];
                    foreach($userResult as $user){
                        $userIds[] =    $user->id;
                    }
        
                
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData =  Yii::$app->params['pushNotificationMessage']['newCompetition'];
                    $replaceContent=[];   
                    $replaceContent['TITLE'] = $model->title;
                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                
                   // $userIds=[];
                    //$userIds[]   =   $userId;
                
                    $notificationInput['referenceId'] = $model->id;
                    $notificationInput['userIds'] = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    
                    $modelNotification->createNotification($notificationInput);
                    // end send notification     
                    
                    */

                    $modelPost = new Post();
                    $modelPost->type =  Post::TYPE_NORMAL;
                    $modelPost->post_content_type =  Post::CONTENT_TYPE_EVENT;
                    $modelPost->content_type_reference_id =  $model->id;
                    $modelPost->is_add_to_post =  1;
                    $modelPost->latitude =  $model->latitude;
                    $modelPost->longitude =  $model->longitude;
                    $modelPost->address =  $model->address;
                    $modelPost->save();



                    Yii::$app->session->setFlash('success', "Event created successfully");
                    return $this->redirect(['index']);
                }
            }
            
          
        }
    
        
        return $this->render('create', [
            'model' => $model,
            'categoryData' =>$categoryData,
            'eventOrganisorData'=>$eventOrganisorData
        ]);
    }

    public function actionUpdate($id)
    {
        
        
        $model = $this->findModel($id);
        $model->scenario= 'update';

        $modelEventOrganisor = new Organization();
        
        $resultEventOrganisor = $modelEventOrganisor->find()->select(['id','name'])->where(['<>', 'status', Organization::STATUS_DELETED])->all();
        $eventOrganisorData = ArrayHelper::map($resultEventOrganisor,'id','name');

        
        $modelEventGallaryImage   = new EventGallaryImage();
        $modelCategory = new Category();
        $resultCategory = $modelCategory->find()->select(['id','name'])->where(['type'=>Category::TYPE_EVENT])->andWhere(['<>', 'status', Category::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');



      //  $modelCompetitionPosition       = new CompetitionPosition();
        
        
        if ($model->load(Yii::$app->request->post())  ) {

          
            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            $model->gallaryFile = UploadedFile::getInstances($model, 'gallaryFile');
            $preImage = $model->image;
           
            if($model->validate()){
                if($model->imageFile){
                    $type =     Yii::$app->fileUpload::TYPE_EVENT;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    
                    $model->image 		= 	  $files[0]['file']; 



                }
                $model->start_date              = strtotime($model->start_date);
                $model->end_date                = strtotime($model->end_date);


                if( $model->save(false)){
                    
                    /*$competitionPositionArr = Yii::$app->request->post('competitionPosition');
                    $competitionAwardArr = Yii::$app->request->post('competitionAward');
                    
                    $inputPosition['competitionId']          =  $model->id;
                    $inputPosition['competitionPosition']    =  $competitionPositionArr;
                    $inputPosition['competitionAward']       =  $competitionAwardArr;
        

                    if($model->status!=Competition::STATUS_COMPLETED){
                      $modelCompetitionPosition->updateCompetitionPosition($inputPosition);

                    }*/
                   


                  
                    if($model->deletePhoto){
                      $deletePhotoIds=[];
                        foreach($model->deletePhoto as $photoId){
                            if((int)$photoId>0){
                                $resultPhoto = $modelEventGallaryImage->findOne($photoId);
                                $deletePhotoIds[]=$photoId;
                            }
                        }    
                        
                        if(count($deletePhotoIds)){
                            $modelEventGallaryImage->deleteAll(['IN','id',$deletePhotoIds]);
                        }
                        
                    }


                    $images =[];
                    foreach ($model->gallaryFile as $file) {

                        $type       =     Yii::$app->fileUpload::TYPE_EVENT;
                        $files      =     Yii::$app->fileUpload->uploadFile($file,$type,false);
                        $images[]   = 	  $files[0]['file']; 
                    }
                    if(count($images)>0){
                        $modelEventGallaryImage->addPhoto($model->id,$images);
                    }

                    Yii::$app->session->setFlash('success', "Event updated successfully");
                    return $this->redirect(['index']);
                }
            }
           
           
        }else{
            //  print_r($model->errors);
            $model->start_date              = date('Y-m-d h:i A',$model->start_date);
            $model->end_date                = date('Y-m-d h:i A',$model->end_date);
            
        }

        return $this->render('update', [
            'model' => $model,
            'categoryData' =>$categoryData,
            'eventOrganisorData'=>$eventOrganisorData
            
    
        ]);
    
    }

    
    public function actionDeclareResult($id)
    {
        $modelPost = new Post();
        $modelUser = new User();
        $modelPayment = new Payment();
        
        $model = $this->findModel($id);
        $model->scenario= 'update';

      
        $modelCompetitionPosition       = new CompetitionPosition();

        if($model->status !=$model::STATUS_ACTIVE){
            Yii::$app->session->setFlash('error', "Competition already processed");
            return $this->redirect(['view','id'=>$model->id]);
        }
        
        
        if (Yii::$app->request->post()  ) {

            $winnerPostIds=Yii::$app->request->post('winner_post_id');
            $valueArr=[];
            $isValidationProcess=true;

            foreach($winnerPostIds as $key=>$value){
                if(!$value){
                    $isValidationProcess=false;
                    Yii::$app->session->setFlash('error', "Please select all winner positions");
                }else{
                    $res = in_array($value,$valueArr);
                    if($res){
                        $isValidationProcess=false;
                        Yii::$app->session->setFlash('error', "Awarded multiple winner position on same post");
                    }
                    $valueArr[]=$value;
                }
                
            }

           if($isValidationProcess){
                
                $model->status =  $model::STATUS_COMPLETED;
                $model->is_result_declare =  $model::COMMON_YES;
                
                if($model->save(false)){
                    
                    $userIds=[];
                    $currentTime=time();


                    foreach($winnerPostIds as $competitionPositionId=>$postId){
               

                        $resultPost = $modelPost->findOne($postId);
                      

                        $resultPost->is_winning = $modelPost::IS_WINNING_YES;
                        $resultPost->save(false);

                        $userIds[]=$resultPost->user_id;
                               
                
                        $resultCompetitionPosition =  $modelCompetitionPosition->findOne($competitionPositionId);
                        $resultCompetitionPosition->winner_user_id = $resultPost->user_id;
                        $resultCompetitionPosition->winner_post_id =  $postId;
                        $resultCompetitionPosition->awarded_at = $currentTime; 
                        $resultCompetitionPosition->save(false);

                        $resultUser   = $modelUser->findOne($resultPost->user_id);
                        
                        $modelPayment->user_id              =  $resultPost->user_id;
                        if($model->award_type==$model::AWARD_TYPE_PRICE){
                            $modelPayment->type                 =  Payment::TYPE_PRICE;
                            $resultUser->available_balance      =  $resultUser->available_balance+$resultCompetitionPosition->award_value;
                        }else{
                            $modelPayment->type                 =  Payment::TYPE_COIN;
                            $resultUser->available_coin      =  $resultUser->available_coin+$resultCompetitionPosition->award_value;
                        }
                        $modelPayment->amount               =  $resultCompetitionPosition->award_value;
                        $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_AWARD;
                        $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                        $modelPayment->save(false);
        
        
                        $resultUser->save(false);

                        

                    }


                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData =  Yii::$app->params['pushNotificationMessage']['wonCompetition'];
                    $replaceContent=[];   
                    $replaceContent['TITLE'] = $model->title;
                    $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                
                   // $userIds=[];
                    //$userIds[]   =   $userId;
                
                    $notificationInput['referenceId'] = $model->id;
                    $notificationInput['userIds'] = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    
                    $modelNotification->createNotification($notificationInput);
                    // end send notification                          



                    Yii::$app->session->setFlash('success', "Competition awareded successfully");

                    return $this->redirect(['view','id'=>$model->id]);

                }

        

                
               //echo 'aa';
               die;
           }
            
            

           
           
        }else{
            $winnerPostIds=[];
        }

        $resultPost = $modelPost->find()->where(['competition_id'=>$id,'status'=>$modelPost::STATUS_ACTIVE])->all();

        $resultPostData = ArrayHelper::map($resultPost, 'id', function($model) {
            return '#'.$model['id'].' '.$model['title'];
        });
        


        

        return $this->render('declare-result', [
            'model' => $model,
            'resultPostData'=>$resultPostData,
            'winnerPostIds'=>$winnerPostIds
            
    
        ]);
    
    }

    public function actionWinning($id)
    {
        
        $modelPost = new Post();
        $modelPayment = new Payment();
        $modelUser = new User();
        
        $resultPost = $modelPost->findOne($id);
        $resultUser   = $modelUser->findOne($resultPost->user_id);


        $model= $this->findModel($resultPost->competition_id);


        
        if($model->status !=  $model::STATUS_COMPLETED){
            $model->status =  $model::STATUS_COMPLETED;
            $model->winner_id =  $id;
            if($model->save(false)){

                
                $resultPost->is_winning = $modelPost::IS_WINNING_YES;
                $resultPost->save(false);






                $modelPayment->user_id              =  $resultPost->user_id;
                if($model->award_type==$model::AWARD_TYPE_PRICE){
                    $modelPayment->type                 =  Payment::TYPE_PRICE;
                    $modelPayment->amount               =  $model->price;
                    
                    $resultUser->available_balance      =  $resultUser->available_balance+$model->price;

                }else{
                    $modelPayment->type                 =  Payment::TYPE_COIN;
                    $modelPayment->coin                 =  $model->coin;
                    
                    $resultUser->available_coin      =  $resultUser->available_coin+$model->coin;
                }
                
                $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_CREDIT;
                $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_AWARD;
                $modelPayment->payment_mode         =  Payment::PAYMENT_MODE_WALLET;
                $modelPayment->save(false);


                $resultUser->save(false);


                

                Yii::$app->session->setFlash('success', "Competition awareded successfully");

                return $this->redirect(['view','id'=>$model->id]);
            }
        }
      
        
    }

    public function actionTicketView($id)
    {
        $model  = $this->findModel($id);
        
        return $this->render('view', [
            'model' =>   $model
        ]);
    }


    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){

            Yii::$app->session->setFlash('success', "Package deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = Event::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
