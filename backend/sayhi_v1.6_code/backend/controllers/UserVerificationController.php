<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\WithdrawalPayment;
use backend\models\WithdrawalPaymentSearch;
use common\models\Payment;
use common\models\User;
use common\models\Notification;


use common\models\UserVerification;
use backend\models\UserVerificationSearch;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * 
 */
class UserVerificationController extends Controller
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
                ],
            ],
        ];
    }

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionIndex($type=null)
    {
        
        $searchModel = new UserVerificationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$type);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'type'=>$type
        ]);
    }


    public function actionUpdate($id,$status)
    {
      
        $statusText = "";
        if($status==UserVerification::STATUS_ACCEPTED){
            $statusText="Complete";
        }else if($status==UserVerification::STATUS_REJECTED){
            $statusText="Reject";
        }
        $model = $this->findModel($id);

       $userModel = new User();
       $userDetails =  $userModel->find()
            ->select(['id','name','username','email','status'])
             ->where(['id'=>$model->user_id])->one();
       $userEmail = $userDetails->email;
       $userName = $userDetails->name;
        /*if($model->status!=UserVerification::STATUS_PENDING){
            return $this->redirect(['index']);

        }*/
       
        if(Yii::$app->request->post()) {

            if($status==UserVerification::STATUS_ACCEPTED){
            $fromMail = Yii::$app->params['senderEmail'];
            $fromName = Yii::$app->params['senderName'];
            $from = array($fromMail =>$fromName);

            $sentMail =  Yii::$app->mailer->compose()
                ->setSubject('Account Verification ')
                ->setFrom($from)
                ->setTo($userEmail)
                ->setHtmlBody('Hi '.$userName . '<br>Congratulations, Your account verification request has been approved.<br>')
                ->send();
            } 
            
            $model->status=$status;
            if($model->save(false)){
                
                if($status==UserVerification::STATUS_REJECTED){

            
                    $modelNotification = new Notification();
                    $notificationInput = [];
                    $notificationData =  Yii::$app->params['pushNotificationMessage']['verificationRejected'];
                    //$replaceContent=[];   
                   // $replaceContent['TITLE'] = $model->title;
                    //$notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                
                    $userIds=[];
                    $userIds[]   =   $model->user_id;
                
                    $notificationInput['referenceId'] = $model->id;
                    $notificationInput['userIds']       = $userIds;
                    $notificationInput['notificationData'] = $notificationData;
                    
                    $modelNotification->createNotification($notificationInput);
                    

                    Yii::$app->session->setFlash('success', "Verification request rejected successfully");
                }else if($status==UserVerification::STATUS_ACCEPTED){
                    $modelUser = new User();
                    $resultUer = $modelUser->findOne($model->user_id);
                    $resultUer->is_verified = User::COMMON_YES;
                    $resultUer->user_verification_id = $id;
                    if($resultUer->save(false)){



                        $modelNotification = new Notification();
                        $notificationInput = [];
                        $notificationData =  Yii::$app->params['pushNotificationMessage']['verificationApproved'];
                        //$replaceContent=[];   
                       // $replaceContent['TITLE'] = $model->title;
                        //$notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
                    
                        $userIds=[];
                        $userIds[]   =   $model->user_id;
                    
                        $notificationInput['referenceId'] = $model->id;
                        $notificationInput['userIds']       = $userIds;
                        $notificationInput['notificationData'] = $notificationData;
                        
                        $modelNotification->createNotification($notificationInput);
                        Yii::$app->session->setFlash('success', "Verification user successfully");



                    }
                    
                }else{
                    Yii::$app->session->setFlash('error', "Action not performed");
                } 
               
               // die;
            }
            return $this->redirect(['index']);
        }
        /*return $this->render('update', [
            'model' => $model,
            'statusText'=>$statusText
        ]);*/
    }


    public function actionReject($id)
    {
      
        $statusText = "";
        
        $model = $this->findModel($id);

        /*if($model->status!=UserVerification::STATUS_PENDING){
            return $this->redirect(['index']);

        }*/

        $userModel = new User();
        $userDetails =  $userModel->find()
            ->select(['id','name','username','email','status'])
             ->where(['id'=>$model->user_id])->one();
        $userEmail = $userDetails->email;
        $userName = $userDetails->name;
        //if(Yii::$app->request->post()) {
        if ($model->load(Yii::$app->request->post())) {
            
            $model->status=UserVerification::STATUS_REJECTED;
            if($model->save(false)){
                
                // sent email to user for reject account
                $fromMail = Yii::$app->params['senderEmail'];
                $fromName = Yii::$app->params['senderName'];
                $from = array($fromMail =>$fromName);

                $sentMail =  Yii::$app->mailer->compose()
                    ->setSubject('Account Verification')
                    ->setFrom($from)
                    ->setTo($userEmail)
                    ->setHtmlBody('Hi '.$userName . '<br>, Your account verification request has been reject.<br>')
                    ->send();
               
                $modelNotification = new Notification();
                $notificationInput = [];
                $notificationData =  Yii::$app->params['pushNotificationMessage']['verificationRejected'];
                //$replaceContent=[];   
                // $replaceContent['TITLE'] = $model->title;
                //$notificationData['body'] = $modelNotification->replaceContent($notificationData['body'],$replaceContent);   
            
                $userIds=[];
                $userIds[]   =   $model->user_id;
            
                $notificationInput['referenceId'] = $model->id;
                $notificationInput['userIds']       = $userIds;
                $notificationInput['notificationData'] = $notificationData;
                
                $modelNotification->createNotification($notificationInput);
                

                Yii::$app->session->setFlash('success', "Verification request rejected successfully");
                return $this->redirect(['index']);
            
                    
            }else{
                Yii::$app->session->setFlash('error', "Action not performed");
            } 
               
              
           
        }
        return $this->render('reject', [
            'model' => $model
           
        ]);
    }

    

    
    
    /**
     * Deletes payment
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model= $this->findModel($id);
    
        if($model->delete(false)){

            Yii::$app->session->setFlash('success', "Payment deleted successfully");

            return $this->redirect(['index']);
        }
        
    }


    public function actionView($id)
    {
        $model  = $this->findModel($id);
        
        return $this->render('view', [
            'model' =>   $model
        ]);
    }
    
    
    
    /**
     * Finds the Countryy model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Countryy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserVerification::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
