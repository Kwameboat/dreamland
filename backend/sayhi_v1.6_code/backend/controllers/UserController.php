<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use backend\models\UserSearch;
use backend\models\ChangePassword;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Country;
use common\models\ReportedUser;
use yii\web\UploadedFile;
use yii\imagine\Image;
use common\models\Follower;
use yii\data\ActiveDataProvider;
use common\models\BlockedUser;
use common\models\Payment;

use common\models\Setting;
use common\models\FeatureList;
use common\models\FeatureEnabled;
use common\models\Post;
use yii\filters\AccessControl;

/**
 * 
 */
class UserController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::USER),
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
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
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
        $model = User::find()
            ->joinWith('country')
            ->where(['user.id' => $id])
            ->one();

        if($model->role ==1 || $model->role ==2 ){
            Yii::$app->session->setFlash('error', "This user is admin");
            return $this->redirect(['administrator/index']);
            
        }    

        return $this->render('view', [
            'model' => $model
        ]);
    }


    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionReportedUser()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->searchReportedUser(Yii::$app->request->queryParams);

        return $this->render('reported-user', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionViewReportedUser($id)
    {
        $model = User::find()
            ->joinWith('country')
            ->where(['user.id' => $id])
            ->one();

        return $this->render('view-reported-user', [
            'model' => $model
        ]);
    }



    public function actionReportedUserAction($id, $type)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $modelReportedUser = new ReportedUser();
        $model = $this->findModel($id);
        if ($type == 'cancel') {

            $currentTime = time();
            $modelReportedUser->updateAll(['status' => ReportedUser::STATUS_REJECTED, 'resolved_at' => $currentTime], ['report_to_user_id' => $id, 'status' => ReportedUser::STATUS_PENDING]);
            Yii::$app->session->setFlash('success', "Reported request cancelled successfully");
            return $this->redirect(['reported-user']);
        } else if ($type == 'block') {

            $currentTime = time();
            $modelReportedUser->updateAll(['status' => ReportedUser::STATUS_ACEPTED, 'resolved_at' => $currentTime], ['report_to_user_id' => $id, 'status' => ReportedUser::STATUS_PENDING]);

            $model->status = $model::STATUS_INACTIVE;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', "User Inactive successfully");
                return $this->redirect(['reported-user']);
            }
        }



    }


    /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();
        $modelCountry = new Country();
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            // $model->image  = $model->upload();
            if ($model->imageFile) {

                $microtime = (microtime(true) * 10000);
                $uniqueimage = $microtime . '_' . date("Ymd_His") . '_' . substr(md5($microtime), 0, 10);
                $imageName = $uniqueimage;
                $model->image = $imageName . '.' . $model->imageFile->extension;
                $imagePath = Yii::$app->params['pathUploadUser'] . "/" . $model->image;
                $imagePathThumb = Yii::$app->params['pathUploadUserThumb'] . "/" . $model->image;
                $imagePathMedium = Yii::$app->params['pathUploadUserMedium'] . "/" . $model->image;
                $model->imageFile->saveAs($imagePath, false);


                Image::thumbnail($imagePath, 500, 500)
                    ->save($imagePathMedium, ['quality' => 100]);

                Image::thumbnail($imagePath, 120, 120)
                    ->save($imagePathThumb, ['quality' => 100]);


            }

            if ($model->save()) {

                Yii::$app->session->setFlash('success', "USer created successfully");
                return $this->redirect(['index']);
            }
        }

        /*   if ($model->load(Yii::$app->request->post()) && $model->save()) {
               return $this->redirect(['index']);
           }*/
        $countryData = $modelCountry->getCountryDropdown();


        return $this->render('create', [
            'model' => $model,
            'countryData' => $countryData
        ]);
    }

    /**
     * Updates an existing Countryy model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $preImage = $model->image;
        $modelCountry = new Country();

        $countryData = $modelCountry->getCountryDropdown();
        if($model->role ==1 || $model->role ==2 ){
            Yii::$app->session->setFlash('error', "You can update admin user");
            return $this->redirect(['view', 'id' => $model->id]);
            
        }



        /* $res = Yii::$app->fs->read();

         print_r($res);*/


        $preStatus = $model->status;
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {


            $modelUser = new User();
            $modelUser->checkPageAccess();


            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            // $model->image  = $model->upload();
            if ($model->imageFile) {
                
                $type = Yii::$app->fileUpload::TYPE_USER;
                $files = Yii::$app->fileUpload->uploadFile($model->imageFile, $type, false);

                $model->image = $files[0]['file'];

                /*$microtime 			= 	(microtime(true)*10000);
                $uniqueimage		=	$microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10); 
                $imageName 			=	$uniqueimage.'.'.$model->imageFile->extension;
                $model->image 		= 	$imageName; 
                $s3 = Yii::$app->get('s3');
                $imagePath = $model->imageFile->tempName;
                $result = $s3->upload('./'.Yii::$app->params['pathUploadUserFolder'].'/'.$imageName, $imagePath);

                $s3->commands()->delete('./'.Yii::$app->params['pathUploadUserFolder'].'/'.$preImage)->execute(); /// delete previous
                */

            }
            if ($preStatus != $model->status) {
                $model->auth_key = null;
                $model->is_chat_user_online = 0;

            }

            if ($model->save()) {

                Yii::$app->session->setFlash('success', "User updated successfully");
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        /* if ($model->load(Yii::$app->request->post()) && $model->save()) {
             return $this->redirect(['view', 'id' => $model->id]);
         }*/
        $statusDropDownData = $model->getStatusDropDownData();

        $model->email = $model->getEmail();

        return $this->render('update', [
            'model' => $model,
            'countryData' => $countryData
        ]);
    }
    /**
     * Update user coin.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdateCoin($id)
    {

        $model = User::find()
            ->where(['user.id' => $id])
            ->one();
        $paymentModel = new Payment();
        $model->scenario = 'updateCoin';

        if ($model->load(Yii::$app->request->post())) {

            $updateCoin = $model->update_coin;

            $userId = $model->id;
            $transactionType = Payment::TRANSACTION_TYPE_CREDIT;
            $historyCoin = $updateCoin;
            if ($updateCoin <= 0) {
                $transactionType = Payment::TRANSACTION_TYPE_DEBIT;
                $historyCoin = str_replace('-', '', $updateCoin);

            }
            if ($historyCoin == 0) {
                Yii::$app->session->setFlash('error', "Amount can not be zero.");
                return $this->redirect(['user/update-coin', 'id' => $id]);

            }
            // payment tranctio entery
            $paymentModel->type = Payment::TYPE_COIN;
            $paymentModel->user_id = $id;
            $paymentModel->transaction_type = $transactionType;
            $paymentModel->payment_type = Payment::PAYMENT_TYPE_ADMIN_UPDATE;
            $paymentModel->coin = $historyCoin;
            $paymentModel->payment_mode = Payment::PAYMENT_MODE_WALLET;
            $paymentModel->created_at = time();

            if ($paymentModel->save()) {
                $userDetails = User::find()->where(['id' => $userId])->one();
                if ($userDetails) {

                    $user_available_coin = @$userDetails->available_coin;
                    $totalCoin = $updateCoin + $user_available_coin;
                    $userDetails->available_coin = $totalCoin;
                    $userDetails->updated_at = time();
                    if ($userDetails->save()) {
                        Yii::$app->session->setFlash('success', "Congratulations !! you have successfully update user coin and its reflected into user account.");
                        return $this->redirect(['user/view', 'id' => $id]);

                    }
                }
            }
        }
        return $this->render('update-coin', [
            'model' => $model
        ]);
    }



    /**
     * Deletes an existing Countryy model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $userModel = $this->findModel($id);
        if($userModel->role ==1 || $userModel->role ==2 ){
            Yii::$app->session->setFlash('error', "You can delete admin user");
            return $this->redirect(['index']);
        }
        $userModel->status = USER::STATUS_DELETED;
        $userModel->auth_key = null;
        $userModel->is_chat_user_online = 0;
        $userModel->save(false);
        return $this->redirect(['index']);
    }

    /*
     *
     *Total following Users
     *
     */
    public function actionFollowing($user_id)
    {
        // echo "hello";
        // exit;
        $searchModel = new Follower();
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($user_id);

        $query = $searchModel->find()->
            joinWith('followingUserDetail')->
            where(['follower_id' => $user_id])
            ->andWhere(['NOT', ['user_id' => $userIdsBlockedMe]])
            ->andWhere(['NOT', ['type' => Follower::FOLLOW_REQUEST]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        return $this->render('following', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /*
     *
     *Total followers Users
     *
     */
    public function actionFollower($user_id)
    {

        $searchModel = new Follower();
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($user_id);

        $query = $searchModel->find()->
            joinWith('followerUserDetail')->
            where(['user_id' => $user_id])
            ->andWhere(['NOT', ['follower_id' => $userIdsBlockedMe]])
            ->andWhere(['NOT', ['type' => Follower::FOLLOW_REQUEST]]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        return $this->render('follower', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 
     * Blocked user list
     * 
     */
    public function actionBlockedUserList($user_id)
    {
        $searchModel = new BlockedUser();
        // $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $query = $searchModel->find()
            ->joinWith('blockedUserDetail')
            ->where(['user_id' => $user_id]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        return $this->render('blocked-user-list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists all  agent.
     * @return mixed
     */
    public function actionAgent()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->searchAgent(Yii::$app->request->queryParams);

        return $this->render('agent', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionApprove($id)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();

        $model = $this->findModel($id);
        $model->status = $model::STATUS_ACTIVE;
        if ($model->save(false)) {


            if ($model->email) {
                $userName = $model->username;
                $fromMail = Yii::$app->params['senderEmail'];
                $fromName = Yii::$app->params['senderName'];
                $from = array($fromMail => $fromName);
                $sentMail = Yii::$app->mailer->compose()
                    ->setSubject('Account approved')
                    ->setFrom($from)
                    ->setTo($model->email)
                    ->setHtmlBody('Hi ' . $userName . '<br>Congratulations, Your account has been approved.<br>')
                    ->send();

            }
            Yii::$app->session->setFlash('success', "User Approved successfully");
            return $this->redirect(['view', 'id' => $id]);
        }

    }

    public function actionReject($id)
    {

        $modelUser = new User();
        $modelUser->checkPageAccess();
        $model = $this->findModel($id);
        $model->status = $model::STATUS_REJECTED;
        if ($model->save(false)) {

            if ($model->email) {
                $userName = $model->username;
                $fromMail = Yii::$app->params['senderEmail'];
                $fromName = Yii::$app->params['senderName'];
                $from = array($fromMail => $fromName);
                $sentMail = Yii::$app->mailer->compose()
                    ->setSubject('Account Rejected')
                    ->setFrom($from)
                    ->setTo($model->email)
                    ->setHtmlBody('Hi ' . $userName . '<br>Your account has been rejected, Please contact to admin.<br>')
                    ->send();

            }

            Yii::$app->session->setFlash('success', "User rejected successfully");
            return $this->redirect(['view', 'id' => $id]);
        }

    }
    public function actionFeatureList($id)
    {

       
        $model = new Setting();

        $modelFeatureList = new FeatureList();
        $modelFeatureEnabled = new FeatureEnabled();
        $featureListRecord = $modelFeatureList->find()->where(['status' => $modelFeatureList::STATUS_ACTIVE])->asArray()->all();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $modelUser = new User();
            $modelUser->checkPageAccess();
            if ($model->feature) {

                $values = [];
                foreach ($featureListRecord as $key => $item) {
                    $isEnabled = 0;
                    if(in_array($item['id'],$model->feature)){
                        $isEnabled =1;
                    }

                    $dataInner['feature_id']    = $item['id'];
                    $dataInner['type']          = 2;
                    $dataInner['is_enabled']    = $isEnabled;
                    $dataInner['user_id']       = $id;
                    $values[] = $dataInner;
                }
                
                $modelFeatureEnabled->deleteAll(['type' => 2,'user_id'=>$id]);
                
                if (count($values) > 0) {
                    Yii::$app->db
                        ->createCommand()
                        ->batchInsert('feature_enabled', ['feature_id','type','is_enabled','user_id'], $values)
                        ->execute();
                }
               

            }
            Yii::$app->session->setFlash('success', "Setting updated successfully");
            return $this->redirect(['feature-list','id'=>$id]);
        }
        $featureEnabledRecord = $modelFeatureEnabled->find()->where(['type' => 2,'user_id'=>$id])->asArray()->all();
         $featureEnabledMainRecord = $modelFeatureEnabled->find()->where(['type' => 1])->asArray()->all();
        $featureList = array();

      
        foreach ($featureListRecord as $key => $item) {
            $item['is_active'] =0;
            $item['is_disable'] = 0;
           $found_key = array_search($item['id'], array_column($featureEnabledRecord, 'feature_id'));
           $found_main_key = array_search($item['id'], array_column($featureEnabledMainRecord, 'feature_id'));
            if(is_int($found_key)){
                $enabledRecords = $featureEnabledRecord[$found_key];
                if($enabledRecords){
                    if($enabledRecords['is_enabled']){
                        $item['is_active'] =1;        
                    }
                }
            }

            if (is_int($found_main_key)) {
                $enabledMainRecords = $featureEnabledMainRecord[$found_main_key];
                if($enabledMainRecords){
                    if($enabledMainRecords['is_enabled'] ){
                        if(!is_int($found_key)){
                            $item['is_active'] =1; 
                        }       
                    }else{
                        $item['is_disable'] = 1; 
                    }
                }
            }



            $featureList[$item['section']][$key] = $item;
        }
        ksort($featureList, SORT_NUMERIC);
        $sections = ['1' => 'Feature List', '2' => 'Gift Section'];
        return $this->render('feature-list', [
            'model' => $model,
            'featureList' => $featureList,
            'sections' => $sections
        ]);

    }
    //temp set unique id
    public function actionSetUniqueId()
    {
        $model = new User();
        $userListRecord = $model->find()->where(['unique_id' => null])->all();
        foreach ($userListRecord as $item) {
            echo $item->id;
            echo '<br>';
            $userModel =  User::find()->select(['id','max(unique_id) as last_unique_id'])->asArray()->one();
            $lastUniqueId =  (int)$userModel['last_unique_id'];
            if($lastUniqueId==0){
               
               $nextUniqueId = 10000;
            }else{
                $nextUniqueId = $lastUniqueId+1;
            }
            echo $nextUniqueId;
            $item->unique_id = $nextUniqueId;
            $item->save(false);
          echo '<br>';
        }
        $model = new Post();
        $userListRecord = $model->find()->where(['unique_id' => null])->all();
        foreach ($userListRecord as $item) {

            echo $item->id;
            
           
            $uniqueId = time().$item->id.rand(1,99999).rand(1,99999);
            $item->unique_id = md5($uniqueId);
            $item->save(false);
          echo '<br>';
          
        }
        die;
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
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
