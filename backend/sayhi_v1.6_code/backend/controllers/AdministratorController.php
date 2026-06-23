<?php

namespace backend\controllers;

use Yii;
use backend\models\Administrator;
use backend\models\AdministratorSearch;
use app\models\User;
use backend\models\ChangePassword;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;

use yii\filters\AccessControl;
use backend\models\ModuleAuth;
use backend\models\ModuleAuthUser;


/**
 * 
 */
class AdministratorController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public $moduleName ='administator';

    
    
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
                'except'=>['profile','update-profile','change-password'],
                'rules' => [
                    [
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::ADMINISTRATOR),
                        'roles' => ['@'],
                       // 'ips' => ['::1s', '192.18.1.01'], // Allowed IP addresses
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
       
        
       
        
        $searchModel = new AdministratorSearch();
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
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        
            
        $modelUser = new User();
        $modelUser->checkPageAccess();
    
        
        $model = new Administrator();
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Admin user created. Choose which modules they can access.');
            return $this->redirect(['auth-permission', 'uid' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
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
        if ($model->load(Yii::$app->request->post())) {
            $modelUser = new User();
            $modelUser->checkPageAccess();


            if($model->role == Administrator::ROLE_ADMIN &&  $model->id != Yii::$app->user->identity->id){
                Yii::$app->session->setFlash('error', "You cannot update super Admin");
                return $this->redirect(['index']);
            }


            if ($model->save()) {
                Yii::$app->session->setFlash('success', "Admin has been updated successfully");
                return $this->redirect(['index']);
            }
        }
        $statusDropDownData = $model->getStatusDropDownData();
       
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionUpdateProfile()
    {
        $id = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post())) {
            $modelUser = new User();
            $modelUser->checkPageAccess();


            if ($model->save()) {
                Yii::$app->session->setFlash('success', "Admin has been updated successfully");
                return $this->redirect(['index']);
            }
        }
        
        return $this->render('update-profile', [
            'model' => $model
            

        ]);
    }

    public function actionChangePassword()
    {
        
        $modelUser = new User();
        $modelUser->checkPageAccess();
    
        $model = new ChangePassword();
      
        if ($model->load(Yii::$app->request->post()) && $model->change()) {
            Yii::$app->session->setFlash('success', "Password has been changed successfully");
            return $this->goHome();
        }
       
        return $this->render('change-password', [
            'model' => $model,
        ]);
    }

    public function actionProfile()
    {
        $id = Yii::$app->user->identity->id;
        return $this->render('profile', [
            'model' => $this->findModel($id),
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
        
        $userModel= $this->findModel($id);
        if($userModel->role == Administrator::ROLE_ADMIN){
            Yii::$app->session->setFlash('error', "Super Admin cannot delete");
            return $this->redirect(['index']);
        }
        $userModel->status =  Administrator::STATUS_DELETED;
        
        if($userModel->save(false)){
            Yii::$app->session->setFlash('success', "Admin Deleted  successfully");
            return $this->redirect(['index']);
        }
        
    }
    public function actionAuthPermission($uid){
        $uid = (int) $uid;
        $admin = Administrator::findOne($uid);
        if (!$admin) {
            throw new NotFoundHttpException('Admin user not found.');
        }
        if ((int) $admin->role === Administrator::ROLE_ADMIN) {
            Yii::$app->session->setFlash('warning', 'Super admin always has full access.');
            return $this->redirect(['index']);
        }

        $modelModuleAuthList = new ModuleAuth();
        $moduleAuthUser = new ModuleAuthUser();
        $moduleAuthListRecord = $modelModuleAuthList->find()->where(['level' => 1])->orderBy(['name' => SORT_ASC])->asArray()->all();

        if ($moduleAuthUser->load(Yii::$app->request->post())) {
            $moduleAuthListRecord = $modelModuleAuthList->find()->orderBy(['name' => SORT_ASC])->asArray()->all();
            $modelUser = new User();
            $modelUser->checkPageAccess();

            $selected = array_map('intval', (array) ($moduleAuthUser->module_ids ?? []));
            $values = [];
            foreach ($moduleAuthListRecord as $item) {
                $values[] = [
                    'user_id' => $uid,
                    'module_auth_id' => (int) $item['id'],
                    'is_enabled' => in_array((int) $item['id'], $selected, true) ? 1 : 0,
                ];
            }

            $moduleAuthUser->deleteAll(['user_id' => $uid]);
            if ($values !== []) {
                Yii::$app->db
                    ->createCommand()
                    ->batchInsert('module_auth_user', ['user_id', 'module_auth_id', 'is_enabled'], $values)
                    ->execute();
            }

            Yii::$app->session->setFlash('success', 'Module access updated for ' . $admin->username);
            return $this->redirect(['index']);
        }

        $moduleAuthUserRecord = $moduleAuthUser->find()->where(['user_id' => $uid])->asArray()->all();
        $moduleList = [];
        foreach ($moduleAuthListRecord as $key => $item) {
            $item['is_active'] = 0;
            $found_key = array_search($item['id'], array_column($moduleAuthUserRecord, 'module_auth_id'));
            if (is_int($found_key)) {
                $enabledRecords = $moduleAuthUserRecord[$found_key];
                if ($enabledRecords && $enabledRecords['is_enabled']) {
                    $item['is_active'] = 1;
                }
            }

            $itemChildArray = [];
            $moduleAuthChildListRecord = $modelModuleAuthList->find()->where(['level' => 2, 'parent_id' => $item['id']])->asArray()->all();
            foreach ($moduleAuthChildListRecord as $itemChild) {
                $itemChild['is_active'] = 0;
                $found_key = array_search($itemChild['id'], array_column($moduleAuthUserRecord, 'module_auth_id'));
                if (is_int($found_key)) {
                    $enabledRecords = $moduleAuthUserRecord[$found_key];
                    if ($enabledRecords && $enabledRecords['is_enabled']) {
                        $itemChild['is_active'] = 1;
                    }
                }
                $itemChildArray[] = $itemChild;
            }
            $item['child_action_list'] = $itemChildArray;
            $moduleList[$key] = $item;
        }

        return $this->render('auth-permission', [
            'model' => $moduleAuthUser,
            'moduleList' => $moduleList,
            'admin' => $admin,
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
        if (($model = Administrator::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
