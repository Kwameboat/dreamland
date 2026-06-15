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
use common\models\EventOrganisor;

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

use common\models\OrganizationType;
use yii\filters\AccessControl;



/**
 * 
 */
class OrginazitionController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::ORGANIZATION),
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
       
        $model = new Organization();
        $query = $model->find()
        ->where(['<>','status',Organization::STATUS_DELETED]);

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
       
        $model = new Organization();
      
        //  $model->scenarios = 'create';
        $modelCategory = new OrganizationType();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', Organization::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        if ($model->load(Yii::$app->request->post()) ) {
            $model->image = UploadedFile::getInstance($model, 'image');
            
            if($model->validate()){
               
                if($model->image){
                    
                    $type = Yii::$app->fileUpload::TYPE_ORGNIZATION;
                    $files = Yii::$app->fileUpload->uploadFile($model->image,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Record Created successfully");
                    return $this->redirect(['index']);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'categoryData'=>$categoryData
            
        ]);
    }


    // Update new 
    public function actionUpdate($id)
    {
        $modelCategory = new OrganizationType();
        $resultCategory = $modelCategory->find()->select(['id','name'])->andWhere(['<>', 'status', OrganizationType::STATUS_DELETED])->all();
        $categoryData = ArrayHelper::map($resultCategory,'id','name');


        $model = $this->findModel($id);

        // $model->scenario = 'update';
       
        if($model->load(Yii::$app->request->post())){
            $modelUser = new User();
            $modelUser->checkPageAccess();
          
            $model->image = UploadedFile::getInstance($model, 'image');

            if($model->image){
               
                
                $type =  Yii::$app->fileUpload::TYPE_ORGNIZATION;
                $files = Yii::$app->fileUpload->uploadFile($model->image,$type,false);
                $model->image 		= 	  $files[0]['file']; 
            }
            
            if($model->save(false)){
                Yii::$app->session->setFlash('success', " Updated successfully");
                return $this->redirect(['index']);
            }
                
        }
       
        return $this->render('update', [
            'model' => $model,
            'categoryData'=>$categoryData
       
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
        if (($model = Organization::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
