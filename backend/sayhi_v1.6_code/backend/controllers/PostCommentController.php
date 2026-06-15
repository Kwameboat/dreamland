<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\PostSearch;
use yii\web\UploadedFile;
//use common\models\Category;
use yii\helpers\ArrayHelper;
use app\models\User;
use backend\models\PostCommentSearch;
use common\models\PostComment;
use common\models\ReportedPostComment;

/**
 * 
 */
class PostCommentController extends Controller
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
        
        $searchModel = new PostSearch();
        $modelUser = new User();
        

        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all();
       
        $userData = ArrayHelper::map($resultUser,'id','username');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData'=>$userData
         
        ]);
        
      
    }

     /**
     * Displays 
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReportedComment()
    {

        $searchModel = new PostCommentSearch();
        $modelUser = new User();
        

        $resultUser = $modelUser->find()->select(['id','username'])->where(['role' => User::ROLE_CUSTOMER])->andWhere(['<>', 'status', User::STATUS_DELETED])->all();
       
        $userData = ArrayHelper::map($resultUser,'id','username');
        // $searchModel = new PostSearch();
        $dataProvider = $searchModel->searchReportedPostComment(Yii::$app->request->queryParams);

        return $this->render('reported-comment', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userData'=>$userData

        ]);
    }

    public function actionViewReportedPostComment($id)
    {
        $model = $this->findModel($id);

        return $this->render('view-reported-post-comment', [
            'model' => $model,

        ]);
    }

    

    
    public function actionReportedPostCommentAction($id, $type)
    {
        $modelUser = new User();
        $modelUser->checkPageAccess();
        $modelReportedPost = new ReportedPostComment();
        $model = $this->findModel($id);
        if($type=='cancel'){
           
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedPostComment::STATUS_REJECTED,'resolved_at'=>$currentTime], [ 'post_comment_id' => $id,'status'=> ReportedPostComment::STATUS_PENDING]);
            Yii::$app->session->setFlash('success', "Reported request cancelled successfully");
                return $this->redirect(['reported-comment']);
        }else if($type=='block'){
            
            $currentTime = time();
            $modelReportedPost->updateAll(['status' => ReportedPostComment::STATUS_ACEPTED,'resolved_at'=>$currentTime], [ 'post_comment_id' => $id,'status'=> ReportedPostComment::STATUS_PENDING]);
            
            $model->status = $model::STATUS_BLOCKED;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', "Post Comment blocked successfully");
                return $this->redirect(['reported-comment']);
            }
        }
       
        
        
    }

     
    /**
     * Updates an existing Countryy model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */

   

    /**
     * Deletes an existing Countryy model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    // public function actionDelete($id)
    // {
    //     $modelUser = new User();
    //     $modelUser->checkPageAccess();
    //     $model= $this->findModel($id);
    //     $model->status =  $model::STATUS_DELETED;
    //     if($model->save(false)){

    //         Yii::$app->session->setFlash('success', "Post deleted successfully");

    //         return $this->redirect(['index']);
    //     }
        
    // }


    /**
     * Finds the Countryy model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Countryy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PostComment::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
