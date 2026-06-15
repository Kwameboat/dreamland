<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\HighlightSearch;
use yii\data\ActiveDataProvider;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use common\models\FileUpload;
use common\models\Highlight;
use common\models\ReportedHighlight;
use common\models\User;

/**
 * 
 */
class HighlightController extends Controller
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
     * Displays 
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReportedHighlight()
    {

        $searchModel = new HighlightSearch();
        $modelUser = new User();
        

        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all();
       
        $userData = ArrayHelper::map($resultUser,'id','username');
        $searchModel = new HighlightSearch();
        $dataProvider = $searchModel->searchReportedHighlight(Yii::$app->request->queryParams);

        return $this->render('reported-highlight', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData'=>$userData

        ]);
    }

    public function actionViewReportedHighlight($id)
    {
        $model = $this->findModel($id);

        return $this->render('view-reported-highlight', [
            'model' => $model,

        ]);
    }


    public function actionReportedHighlightAction($id, $type)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $modelReportedPost = new ReportedHighlight();
        $model = $this->findModel($id);
        if($type=='cancel'){
           
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedHighlight::STATUS_REJECTED,'resolved_at'=>$currentTime], [ 'highlight_id' => $id,'status'=> ReportedHighlight::STATUS_PENDING]);
            Yii::$app->session->setFlash('success', "Reported request cancelled successfully");
                return $this->redirect(['reported-highlight']);
        }else if($type=='block'){
            
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedHighlight::STATUS_ACEPTED,'resolved_at'=>$currentTime], [ 'highlight_id' => $id,'status'=> ReportedHighlight::STATUS_PENDING]);
            
            $model->status = Highlight::STATUS_BLOCKED;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', "Highlight blocked successfully");
                return $this->redirect(['reported-highlight']);
            }
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
        if (($model = Highlight::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
