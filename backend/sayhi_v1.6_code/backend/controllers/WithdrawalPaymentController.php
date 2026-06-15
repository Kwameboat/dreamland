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
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;

/**
 * 
 */
class WithdrawalPaymentController extends Controller
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
        
        $searchModel = new WithdrawalPaymentSearch();
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
        if($status==WithdrawalPayment::STATUS_ACCEPTED){
            $statusText="Complete";
        }else if($status==WithdrawalPayment::STATUS_REJECTED){
            $statusText="Reject";
        }
        $model = $this->findModel($id);

        if($model->status!=WithdrawalPayment::STATUS_PENDING){
            return $this->redirect(['index']);

        }
       
        if($model->load(Yii::$app->request->post()) ) {
            $model->status=$status;
            if($model->save()){
                if($status==WithdrawalPayment::STATUS_REJECTED){
                    $modelUser = new User();
                    $resultUer = $modelUser->findOne($model->user_id);
                    
                    $resultUer->available_balance =  $resultUer->available_balance + $model->amount;
                    if($resultUer->save(false)){
                        
                        $modelPayment                   = new Payment();
                        $modelPayment->user_id          =  $model->user_id;
                        $modelPayment->type             =  Payment::TYPE_PRICE;
                        $modelPayment->amount           =  $model->amount;
                        
                        $modelPayment->transaction_type =  Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type     =  Payment::PAYMENT_TYPE_WITHDRAWAL_REFUND;
                        $modelPayment->payment_mode     =  Payment::PAYMENT_MODE_WALLET;
                        $modelPayment->transaction_id   =  $model->transaction_id;
                        
                        $modelPayment->save(false);
                    }
                    Yii::$app->session->setFlash('success', "Payment request rejected successfully");
                }else{
                    Yii::$app->session->setFlash('success', "Payment request updated successfully");
                } 
                return $this->redirect(['index']);
            }
        }
        return $this->render('update', [
            'model' => $model,
            'statusText'=>$statusText
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
        if (($model = WithdrawalPayment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
