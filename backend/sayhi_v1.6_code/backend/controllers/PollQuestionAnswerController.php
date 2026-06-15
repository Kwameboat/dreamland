<?php

namespace backend\controllers;

use Yii;
//use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\LiveTv;
use backend\models\LiveTvSearch;
use yii\web\UploadedFile;
use common\models\FileUpload;
use common\models\LiveTvCategory;
use yii\helpers\ArrayHelper;
use common\models\Poll;
use common\models\Category;
use backend\models\PollSearch;
use common\models\Organization;
use backend\models\PollQuestionSearch;
use common\models\PollQuestion;
use backend\models\PollQuestionAnswerSearch;
use common\models\User;
use common\models\PollQuestionAnswer;
/**
 * 
 */
class PollQuestionAnswerController extends Controller
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
    public function actionIndex()
    {
        $searchModel = new PollQuestionAnswerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $modeldata = new Poll();
        $resultData = $modeldata->find()->select(['id','title'])->andWhere(['and', 'status', Poll::STATUS_ACTIVE])->all(); 
       
        $data = ArrayHelper::map($resultData,'id','title');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categoryData' =>$data,
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
        $userModel->status =  PollQuestionAnswer::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Poll Answer deleted successfully");

            return $this->redirect(['index']);
        }
        
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
        if (($model = PollQuestionAnswer::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}