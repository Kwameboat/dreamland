<?php

namespace backend\controllers;

use Yii;
use app\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\PickleballMatch;
use common\models\PickleballMatchTeam;
use common\models\PickleballTeamPlayer;
use common\models\PickleballCourt;
use backend\models\PickleballMatchSearch;
use yii\web\UploadedFile;

/**
 * 
 */
class PickleballMatchController extends Controller
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
        $searchModel = new PickleballMatchSearch();
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
        $model  = $this->findModel($id);
        
        return $this->render('view', [
            'model' =>   $model
        ]);
    }

    /**
     * Creates a new Countryy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
       
        $model = new PickleballCourt();
      
        $model->scenario = 'create';

        if ($model->load(Yii::$app->request->post()) ) {
            
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->validate()){
                if($model->imageFile){
                    
                    $type =  Yii::$app->fileUpload::TYPE_PICKLEBALL_COURT;
                    $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                    $model->image 		= 	  $files[0]['file']; 
                    
                }

                if($model->save()){
                    Yii::$app->session->setFlash('success', "Court created successfully");
                    return $this->redirect(['index']);
                }
            }
            
        }

        return $this->render('create', [
            'model' => $model
            
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
        
        //echo Yii::$app->urlManagerFrontend->baseUrl;
        $model = $this->findModel($id);

        $model->scenario = 'update';
        if($model->load(Yii::$app->request->post())){
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if($model->imageFile){
                
                $type =  Yii::$app->fileUpload::TYPE_PICKLEBALL_COURT;
                $files = Yii::$app->fileUpload->uploadFile($model->imageFile,$type,false);
                $model->image 		= 	  $files[0]['file']; 
                
            }
           
          
            if($model->save(false)){
                Yii::$app->session->setFlash('success', "Court updated successfully");
                return $this->redirect(['index']);
            };
                
        }
       
        return $this->render('update', [
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
     
        $userModel= $this->findModel($id);
        $userModel->status =  PickleballMatch::STATUS_DELETED;
        if($userModel->save(false)){

            Yii::$app->session->setFlash('success', "Match deleted successfully");

            return $this->redirect(['index']);
        }
        
    }
    
    public function actionUpdateScore($teamId){
        $model = new PickleballMatchTeam();
        $resultTeam = $model->find()
        ->where(['id'=>$teamId])->one();
        
        if(Yii::$app->request->post()){
            $players = Yii::$app->request->post()['player_id'];;

            //print_r($players);
            //echo 'aa';
            
            foreach($players as $paylerId => $playerScore){
                //print_r($paylerId );

                $modelPickleballTeamPlayer = new PickleballTeamPlayer();
                $resultPickleballTeamPlayer = $modelPickleballTeamPlayer->findOne($paylerId);
                $resultPickleballTeamPlayer->point_gain = $playerScore;
                $resultPickleballTeamPlayer->save();

            }
            $model->updateTeamScore($teamId);
            Yii::$app->session->setFlash('success', "Score updated successfully");
            return $this->redirect(['view','id'=>$resultTeam->match_id]);

            
        }
        
        /*return $this->renderPartial('user-live-gift-details', [
            'dataProvider' => $dataProvider,
        ]);*/
        return $this->renderAjax('update-score', ['model' => $resultTeam]);
        
    }
    
    public function actionDeclareResult($teamId){
        
        $modelPickleballMatchTeam    = new PickleballMatchTeam();
        $modelPickleballMatch       = new PickleballMatch();
        $resultTeam = $modelPickleballMatchTeam->find()
        ->where(['id'=>$teamId])->one();
        
        if(Yii::$app->request->post()){
           
            $resultMatch = $modelPickleballMatch->findOne($resultTeam->match_id);
            
            $resultMatch->status = PickleballMatch::STATUS_COMPLETED;
            $resultMatch->winner_team_id = $teamId;
            $resultMatch->result_declared_at = time();
            if($resultMatch->save(false)){
                
                foreach ($resultMatch->matchTeam as $team) {
                    $modelPickleballMatchTeam = new PickleballMatchTeam();
                    $resultPickleballMatchTeam = $modelPickleballMatchTeam->findOne($team->id );
                    $winner_status = 0;
                    if($resultPickleballMatchTeam->id == $teamId){
                        $winner_status =PickleballMatchTeam::WINNER_STATUS_WIN;
                    }else{
                        $winner_status =PickleballMatchTeam::WINNER_STATUS_LOSS;
                    }
                    $resultPickleballMatchTeam->winner_status =  $winner_status;
                    if($resultPickleballMatchTeam->save(false)){


                    }

                }

            }

            Yii::$app->session->setFlash('success', "Result declared successfully");
            return $this->redirect(['view','id'=>$resultTeam->match_id]);
           
            
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
        if (($model = PickleballMatch::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}