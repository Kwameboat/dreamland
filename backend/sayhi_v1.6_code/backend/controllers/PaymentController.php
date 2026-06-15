<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Payment;
use backend\models\PaymentSearch;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use common\models\Package;
use common\models\User;
use backend\models\StreamerAwardHistorySearch;
use common\models\Setting;
use yii\filters\AccessControl;
/**
 * 
 */
class PaymentController extends Controller
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
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::PAYMENT),
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
    public function actionIndex($type='payment_received')
    {
        
        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$type);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'type'=> $type
        ]);
    }

     /**
     * Lists all  models.
     * @return mixed
     */
    public function actionAdminWallet()
    {
        $modelSetting = new Setting();
        $resultSetting = $modelSetting->getSettingData();

        $availableCoin= $resultSetting->available_coin;
        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->searchAdminWallet(Yii::$app->request->queryParams);

        return $this->render('admin-wallet', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'availableCoin'=>$availableCoin
            
        ]);
    }

    

    public function actionUpdate($id)
    {
       
        $model = $this->findModel($id);
      //  $model->scenario = 'update';
       
       
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Payment  updated successfully");
            return $this->redirect(['index']);
            
                
        }
       
        return $this->render('update', [
            'model' => $model
            
       
        ]);
    }

    
        /**
     * Displays a single payment details.
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
     * Deletes payment
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $model= $this->findModel($id);
    
        if($model->delete(false)){

            Yii::$app->session->setFlash('success', "Payment deleted successfully");

            return $this->redirect(['index']);
        }
        
    }
    
    // Refund Payment
    public function actionRefund($id)
    {
       
        $model = $this->findModel($id);
      //  $model->scenario = 'update';
      $modelUser = new User();
        $modelUser->checkPageAccess();
        
        $model->status = Payment::STATUS_REFUND;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $packageId= $model->package_id;
            $userId = $model->user_id;
            $paymentModel = new Payment();
            $packageModel = new Package();
            $userModel = new User();
            if($packageId){
                $packageDetails = $packageModel->getPackageDetails($packageId);
                $package_coin = @$packageDetails['coin'];
                $userDetails = $userModel->find()->where(['id'=>$userId])->one(); 
                if($userDetails){
                    $userAvalaibleCoin = $userDetails['available_coin'];
                    // now deduct package coin from user available coin
                    // echo $userAvalaibleCoin-$package_coin;
                    // exit;
                     $totalCoin = $userAvalaibleCoin-$package_coin;
                     $userDetails->available_coin = $totalCoin;
                    //  echo  $userModel->available_coin;
                    //  exit;
                     if($userDetails->save(false)){
                        $paymentModel->type = Payment::TYPE_COIN;
                        $paymentModel->transaction_type = Payment::TRANSACTION_TYPE_DEBIT;
                        $paymentModel->payment_type = Payment::PAYMENT_TYPE_REFUND;
                        $paymentModel->payment_mode = Payment::PAYMENT_MODE_WALLET;                       
                        $paymentModel->remarks  = "Coin deduct from user account.";
                        $paymentModel->coin =  $package_coin;
                        $paymentModel->user_id =   $userId;
                        $paymentModel->reference_id =  $id;
                        $paymentModel->save();
                     }
                }
                
                
            }
            
            
            Yii::$app->session->setFlash('success', "Payment  updated successfully");
            return $this->redirect(['index']);
            
                
        }
       
        return $this->render('refund', [
            'model' => $model
            
       
        ]);
    }

     /**
     * Lists all  models.
     * @return mixed
     */
    public function actionStreamerAward()
    {
        
        $searchModel = new StreamerAwardHistorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('streamer-award', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
            
        ]);
    }


    /**
     * 
     * Post Promtion payment
     * 
     */
    public function actionPromotionPayment($promotion_id)
    {
        
        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->promotionsearch(Yii::$app->request->queryParams,$promotion_id);

        return $this->render('promotion-payment', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
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
        if (($model = Payment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
