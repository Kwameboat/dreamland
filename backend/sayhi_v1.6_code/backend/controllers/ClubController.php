<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
//use common\models\Category;
//use backend\models\CategorySearch;
use common\models\Club;
use backend\models\ClubSearch;
use common\models\ChatRoom;
use common\models\ClubCategory;

use common\models\Post;
//use common\models\User;
use app\models\User;
use yii\data\ActiveDataProvider;
use yii\imagine\Image;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use common\models\FileUpload;
use yii\filters\AccessControl;

/**
 * 
 */
class ClubController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::CLUB),
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
        
        $searchModel = new ClubSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modelClubCategory = new ClubCategory();
        

        $resultCategory = $modelClubCategory->find()->select(['id','name'])->andWhere(['<>', 'status', ClubCategory::STATUS_DELETED])->all();
       
        $categoryData = ArrayHelper::map($resultCategory,'id','name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$categoryData
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

   

  

    public function actionDelete($id)
    {
        
        $modelUser = new User();
        $modelUser->checkPageAccess();
        
        $model= $this->findModel($id);
        $model->status =  $model::STATUS_DELETED;
        if($model->save(false)){
            if($model->is_chat_room){
                $modelChatRoom      =   new ChatRoom();
                $resultChatRoom =   $modelChatRoom->find()->where(['club_id'=>$id])->one();
                if($resultChatRoom){
                    $resultChatRoom->status = ChatRoom::STATUS_DELETED;
                    $resultChatRoom->save(false);
                }

            }


            Yii::$app->session->setFlash('success', "Club deleted successfully");

            return $this->redirect(['index']);
        }
        
    }

    protected function findModel($id)
    {
        if (($model = Club::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
