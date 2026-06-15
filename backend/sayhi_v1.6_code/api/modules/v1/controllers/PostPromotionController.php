<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\PostPromotion;
use api\modules\v1\models\User;
use api\modules\v1\models\Payment;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
//use api\modules\v1\models\AudienceKeyword;
use yii\rest\ActiveController;
use api\modules\v1\models\PostPromotionSearch;
use api\modules\v1\models\Audience;
use api\modules\v1\models\Post;
use api\modules\v1\models\Setting;

class PostPromotionController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\postPromotion';

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
            'except' => [],
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new PostPromotionSearch();

        $result = $model->searchMyPromotion(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['promotion'] = $result;
        return $response;

    }

    public function actionCreate()
    {

        $model = new PostPromotion();
        $modelSetting = new Setting();
        $modelAudience = new Audience();
        $modelPost    = new Post();
        $modelUser                          =   new User();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }
        if(!empty(@$model->post_id)){
            $post = $modelPost->findOne(['id' => $model->post_id , 'user_id'=>$userId, 'status'=>Post::STATUS_ACTIVE ]);          
             if(!$post){
                 $response['statusCode'] = 422;
                 $errors['message'][] = 'Post is not found or your are not allow to use this post';
                 $response['errors'] = $errors;
                 return $response;
             }
         }
        if(!empty(@$model->audience_id)){
           $audience = $modelAudience->findOne(['id' => $model->audience_id , 'status'=>Audience::STATUS_ACTIVE]);          
            if(!$audience){
                $response['statusCode'] = 422;
                $errors['message'][] = 'Audience is not found';
                $response['errors'] = $errors;
                return $response;
            }
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

        



        $model->expiry = strtotime("+$model->duration days", time());

        $settingData = $modelSetting->find()->one();
        // print_r($settingData);
        
        $model->daily_promotion_limit = @$model->amount;// * @$settingData->each_view_price_promotion;
        // exit;
        //  * @$promotionCard->views_per_price;

        if ($model->save(false)) {

            foreach($model->payments as $payment){
                // print_r($payment['amount']);
                     $modelPayment                       =  new Payment();
                     $modelPayment->user_id              =  $userId;
                     $modelPayment->post_promotion_id      =  $model->id;
                     $modelPayment->amount               =  $payment['amount'];
                     $modelPayment->transaction_id       =  $payment['transaction_id'];
                     $modelPayment->transaction_type     =  Payment::TRANSACTION_TYPE_DEBIT;
                     $modelPayment->payment_type         =  Payment::PAYMENT_TYPE_PROMOTION;
                     $modelPayment->payment_mode         =   $payment['payment_mode'];
                    //  $modelPayment->processed_status     =  Payment::PROCESSED_STATUS_COMPLETED;
                     $modelPayment->save(false);


                     if($payment['payment_mode']==Payment::PAYMENT_MODE_WALLET){

                         $resultUser = $modelUser->findOne($userId);
                         $resultUser->available_balance   = $resultUser->available_balance - $payment['amount'];
                         $resultUser->save(false);

                     }

            }

            $response['message'] = Yii::$app->params['apiMessage']['postPromotion']['createdSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

    }

    // public function actionUpdate($id)
    // {

    //     $userId = Yii::$app->user->identity->id;
    //     $modelAudience = new Audience();
    //     $modelAudienceKeyword = new AudienceKeyword();

    //     $modelAudienceDelete = $modelAudience->find()->where(['id' => $id, 'user_id' => $userId])->one();
    //     $model = new Audience();
    //     $model->scenario = 'create';
    //     $model->load(Yii::$app->getRequest()->getBodyParams(), '');
    //     if (!$model->validate()) {
    //         $response['statusCode'] = 422;
    //         $response['errors'] = $model->errors;
    //         return $response;
    //     }
    //     if ($model->save(false)) {
    //         if ($modelAudienceDelete) {
    //             $modelAudienceDelete->status = Audience::STATUS_DELETED;
    //             $modelAudienceDelete->save();
    //         }

    //         $id = $model->id;
    //         $modelAudienceKeyword->updateKeywords($id, $model['keywords']);
    //         $response['message'] = Yii::$app->params['apiMessage']['postPromotion']['updatedSuccess'];
    //         return $response;
    //     } else {

    //         $response['statusCode'] = 422;
    //         $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
    //         $response['errors'] = $errors;
    //         return $response;
    //     }

    // }

    public function actionDelete($id)
    {

        $userId = Yii::$app->user->identity->id;
        $modelAudience = new Audience();

        $model = $modelAudience->find()->where(['id' => $id, 'user_id' => $userId])->one();
        if ($model) {
            $model->status = Audience::STATUS_DELETED;
            if ($model->save(false)) {

                $response['message'] = Yii::$app->params['apiMessage']['postPromotion']['deletedSuccess'];
                return $response;
            }

        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

    }

    public function actionUpdateStatus()
    {

        $userId = Yii::$app->user->identity->id;
        $model = new PostPromotion();

        $model->scenario = 'updateStatus';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');

        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $modelAdPromotion = $model->find()->where(['id' => $model->id])->one();
       
        if (!$modelAdPromotion || @$modelAdPromotion->post->user_id != $userId) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

        $newStatus = $model->status;
        if ($newStatus == PostPromotion::STATUS_PAUSED) {
            $allowedStatus = [PostPromotion::STATUS_ACTIVE];
        } elseif ($newStatus == PostPromotion::STATUS_ACTIVE) {
            $allowedStatus = [PostPromotion::STATUS_PAUSED];
        }

        $isStatusAllowed = in_array($modelAdPromotion->status, $allowedStatus);
        if (!$isStatusAllowed || $modelAdPromotion->expiry < time() ) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['postPromotion']['alreadyProcessed'];
            $response['errors'] = $errors;
            return $response;
        }

        $modelAdPromotion->status = $newStatus;

        

        if ($modelAdPromotion->save(false)) {
            $msg = '';
            if($modelAdPromotion->status==PostPromotion::STATUS_PAUSED){
                $msg =Yii::$app->params['apiMessage']['postPromotion']['pausedSuccess'];
            }else if($modelAdPromotion->status==PostPromotion::STATUS_ACTIVE){
                $msg =Yii::$app->params['apiMessage']['postPromotion']['resumedSuccess'];

            }

            $response['message'] = $msg;
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

    }

    public function actionCancel()
    {

        $userId = Yii::$app->user->identity->id;
        $modelPostPromotion = new PostPromotion();
        $modelUser = new User();
        $currentTime = time();
        $modelPostPromotion->scenario = 'cancel';
        $modelPostPromotion->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$modelPostPromotion->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $modelPostPromotion->errors;

            return $response;
        }
//         echo $modelPostPromotion->id;
// echo "exit";
// exit("1234");
        $result = $modelPostPromotion->find()->where(['id' => @$modelPostPromotion->id, 'created_by' => $userId])
        ->andwhere(['>', 'expiry', $currentTime])->andWhere(['status'=>PostPromotion::STATUS_ACTIVE])->one();
        // print_r($result );
        // exit("hello");
        if(!$result){
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

        $totalSpend = $result->totalSpend;
        $refundAmount = $result->total_amount - $totalSpend;

            $result->total_spend = $totalSpend;
            $result->status = PostPromotion::STATUS_CANCEL;
            if ($result->save(false)) {
                if ($refundAmount > 0) {
                    $userId = $result->post->user_id;
                    $resultUser = $modelUser->findOne($userId);
                    $resultUser->available_balance = $resultUser->available_balance + $refundAmount;
                    if ($resultUser->save(false)) {
                        $modelPayment = new Payment();
                        $modelPayment->user_id = $userId;
                        $modelPayment->post_promotion_id = $result->id;
                        $modelPayment->amount = $refundAmount;
                        $modelPayment->transaction_type = Payment::TRANSACTION_TYPE_CREDIT;
                        $modelPayment->payment_type = Payment::PAYMENT_TYPE_PROMOTION_REFUND;
                        $modelPayment->payment_mode = Payment::PAYMENT_MODE_WALLET;
                        // $modelPayment->status               =  Payment::PROCESSED_STATUS_COMPLETED;
                        $modelPayment->save(false);
                        $response['message'] = Yii::$app->params['apiMessage']['postPromotion']['cancel'];
                        return $response;
                    }
                }

            } else {

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
