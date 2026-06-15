<?php
namespace backend\controllers;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\Setting;
use yii\helpers\ArrayHelper;
use app\models\User;
use common\models\FeatureList;
use common\models\FeatureEnabled;
use yii\filters\AccessControl;

/**
 * 
 */
class SettingController extends Controller
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
                        'allow' => Yii::$app->authPermission->can(Yii::$app->authPermission::SETTING),
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
        $id=1;
        $model = $this->findModel($id);
       
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
 
            $modelUser = new User();
            $modelUser->checkPageAccess();

            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['index']);
                
            }
                
        }
       
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionGeneralInformation(){

        $id=1;
        $model = $this->findModel($id);
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $modelUser = new User();
            $modelUser->checkPageAccess();

            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['general-information']);
                
            }
                
        }
        return $this->render('generalupdate', [
            'model' => $model,
        ]);
    }
    
    public function actionContentModeration(){
        
        $id=1;
        $model = $this->findModel($id);
        
        $model->scenario= 'contentModerationSetting';
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $modelUser = new User();
            $modelUser->checkPageAccess();
            
            if($model->content_moderation_gateway ==  Setting::CONTENT_MODERATION_GATEWAY_AMAZON_REKOGNITION){
                if($model->storage_system != Setting::STORAGE_SYSTEM_S3){
                    Yii::$app->session->setFlash('error', "If you want to use Amazone Rekognition then you must use AWS S3 for storage system");
                    return $this->redirect(['content-moderation']);
                }
            }
            
            /*if(!$model->sightengine_is_content_moderation){
                $model->sightengine_api_user=null;
                $model->sightengine_api_secret=null;
            }*/

            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['content-moderation']);
                
            }
                
        }
        return $this->render('content-moderation-update', [
            'model' => $model,
        ]);
    }

    public function actionPayment(){
        $id=1;
        $model = $this->findModel($id);
       
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    
            $modelUser = new User();
            $modelUser->checkPageAccess();
    
            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['payment']);
                
            }
                
        }
        return $this->render('paymentupdate', [
            'model' => $model,
        ]);
     }
     public function actionSocialLinks(){
      
        $id=1;
        $model = $this->findModel($id);
       
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    
            $modelUser = new User();
            $modelUser->checkPageAccess();
    
            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['social-links']);
                
            }
                
        }
        return $this->render('sociallinksupdate', [
            'model' => $model,
        ]);
     }

     public function actionAppSetting(){
        $id=1;
        $model = $this->findModel($id);
       
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    
            $modelUser = new User();
            $modelUser->checkPageAccess();
    
            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['app-setting']);
                
            }
                
        }
        return $this->render('app', [
            'model' => $model,
        ]);
     }

     public function actionFeature(){
        {
            $id=1;
            $model = $this->findModel($id);
           
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
     
                $modelUser = new User();
                $modelUser->checkPageAccess();
    
                if(($model->is_photo_post==0 && $model->is_video_post==0)) {
    
                    Yii::$app->session->setFlash('error', "At least 1 thing should be enabled from Photo Post and Video Post, both can’t be disabled");
                   return $this->goBack(Yii::$app->request->referrer);
                }
    
                if($model->is_stories==1)
                {
                  
                  $model->is_story_highlights=1;
    
                }
               
                if( $model->is_story_highlights==1  &&  $model->is_stories==0)
                {
                    Yii::$app->session->setFlash('error', "Enable only when  Story Highlights is Enable ");
                    return $this->goBack(Yii::$app->request->referrer);
                }
    
                if($model->is_chat==1)
                {
                   /* $model->is_photo_share=1;  $model->is_video_share=1;   $model->is_files_share=1; $model->is_gift_share=1; $model->is_audio_share=1; $model->is_drawing_share=1;
    
                    $model->is_user_profile_share=1;$model->is_club_share=1;  $model->is_photo_share=1; $model->is_reply=1; $model->is_forward=1; $model->is_star_message=1;  $model->is_events_share=1;   $model->is_location_sharing=1; $model->is_contact_sharing=1; 
                    */
                }
                if($model->is_chat==0){
    
                    $model->is_photo_share=0;  $model->is_video_share=0;   $model->is_files_share=0; $model->is_gift_share=0; $model->is_audio_share=0; $model->is_drawing_share=0;
    
                    $model->is_user_profile_share=0;$model->is_club_share=0;  $model->is_photo_share=0; $model->is_reply=0; $model->is_forward=0; $model->is_star_message=0;  $model->is_events_share=0; $model->is_location_sharing=0; $model->is_contact_sharing=0;
               
    
                }
    
    
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Setting updated successfully");
                    return $this->redirect(['feature']);
                    
                }
                    
            }
           
            return $this->render('feature', [
                'model' => $model,
            ]);
        }
     }
     public function actionFeatureList(){
        {
            $id=1;
            $model = $this->findModel($id);

            $modelFeatureList =  new FeatureList();
            $modelFeatureEnabled =  new FeatureEnabled();
            $featureListRecord = $modelFeatureList->find()->where(['status'=>$modelFeatureList::STATUS_ACTIVE])->asArray()->all();
           
            if ($model->load(Yii::$app->request->post()) && $model->validate()) {
     
                $modelUser = new User();
                $modelUser->checkPageAccess();
                if($model->feature){

                    $values = [];
                    foreach ($featureListRecord as $key => $item) {
                        $isEnabled = 0;
                        if(in_array($item['id'],$model->feature)){
                            $isEnabled =1;
                        }

                        $dataInner['feature_id']    = $item['id'];
                        $dataInner['type']          = 1;
                        $dataInner['is_enabled']    = $isEnabled;
                        $values[] = $dataInner;
                    }
                    
                    $modelFeatureEnabled->deleteAll( ['type' => 1]);
                    /*foreach ($model->feature as $featureId) {
                       
                        if($featureId>0){
                            $dataInner['feature_id'] = $featureId;
                            $dataInner['type'] = 1;
                            $values[] = $dataInner;
                        }
                       // $isFirst = false;
                    }*/
                    if (count($values) > 0) {
                        Yii::$app->db
                            ->createCommand()
                            ->batchInsert('feature_enabled', ['feature_id','type','is_enabled'], $values)
                            ->execute();
                    }
  
                }
                if($model->save()){
                    Yii::$app->session->setFlash('success', "Setting updated successfully");
                    return $this->redirect(['feature-list']);
                }
            }
            $featureEnabledRecord = $modelFeatureEnabled->find()->where(['type'=>1])->asArray()->all();
            $featureList = array();
            foreach ($featureListRecord as $key => $item) {
                $item['is_active'] =0;
                $found_key = array_search($item['id'], array_column($featureEnabledRecord, 'feature_id'));
                if(is_int($found_key)){
                    $enabledRecords = $featureEnabledRecord[$found_key];
                    if($enabledRecords){
                        if($enabledRecords['is_enabled']){
                            $item['is_active'] =1;        
                        }
                    }
                }
                $featureList[$item['section']][$key] = $item;
            }
            ksort($featureList, SORT_NUMERIC);
            $sections=['1'=>'Feature List','2'=>'Gift Section'];
            return $this->render('feature-list', [
                'model' => $model,
                'featureList'=>$featureList,
                'sections'=>$sections
            ]);
        }
     }

     public function actionAppThemeSetting(){

        $id=1;
        $model = $this->findModel($id);
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $modelUser = new User();
            $modelUser->checkPageAccess();

            if($model->save()){
                Yii::$app->session->setFlash('success', "App Theme Setting updated successfully");
                return $this->redirect(['app-theme-setting']);
                
            }
                
        }
        return $this->render('appthemeupdate', [
            'model' => $model,
        ]);
    }

     
    public function actionSms(){
        
        $id=1;
        $model = $this->findModel($id);
        $model->scenario= 'smsSetting';
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $modelUser = new User();
            $modelUser->checkPageAccess();
            if($model->sms_gateway== $model::SMS_GATEWAY_TWILIO){
                $model->msg91_authKey=null;
                $model->msg91_sender_id=null;
            }else if($model->sms_gateway== $model::SMS_GATEWAY_SMS91){
                $model->twilio_sid=null;
                $model->twilio_token=null;
                $model->twilio_number=null;
            }else{
                
                $model->twilio_sid=null;
                $model->twilio_token=null;
                $model->twilio_number=null;
                $model->msg91_authKey=null;
                $model->msg91_sender_id=null;
            }

            if($model->save()){
                Yii::$app->session->setFlash('success', "SMS Setting updated successfully");
                return $this->redirect(['sms']);
                
            }
                
        }
        return $this->render('sms-update', [
            'model' => $model,
        ]);
    }

    
    public function actionStorage(){
        
        $id=Setting::SETTING_DB_ID;
        $model = $this->findModel($id);
        $model->scenario= 'storageSetting';
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            
            
            $modelUser = new User();
            $modelUser->checkPageAccess();
            if($model->storage_system== $model::STORAGE_SYSTEM_S3){
                $model->azure_account_name=null;
                $model->azure_account_key=null;
                $model->azure_container=null;
            }else if($model->storage_system== $model::STORAGE_SYSTEM_AZURE){
                $model->aws_access_key_id=null;
                $model->aws_secret_key=null;
                $model->aws_region=null;
                $model->aws_bucket=null;
                $model->aws_access_url=null;
            }else{

                $model->aws_access_key_id=null;
                $model->aws_secret_key=null;
                $model->aws_region=null;
                $model->aws_bucket=null;
                $model->aws_access_url=null;

                $model->azure_account_name=null;
                $model->azure_account_key=null;
                $model->azure_container=null;
                
            }

            if($model->save()){
                Yii::$app->session->setFlash('success', "Srorage Setting updated successfully");
                return $this->redirect(['storage']);
                
            }
                
        }
        if(Yii::$app->params['siteMode'] == 3){
            $model->aws_access_key_id='###';
            $model->aws_secret_key='###';
            $model->aws_region='####';
            $model->aws_bucket='###';
            $model->aws_access_url='###';

        }

        return $this->render('storage-update', [
            'model' => $model,
        ]);
    }

    public function actionUserSubscription(){
      
        $id=Setting::SETTING_DB_ID;
        $model = $this->findModel($id);
       
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
    
            $modelUser = new User();
            $modelUser->checkPageAccess();
    
            if($model->save()){
                Yii::$app->session->setFlash('success', "Setting updated successfully");
                return $this->redirect(['user-subscription']);
                
            }
                
        }
        return $this->render('user-subscription', [
            'model' => $model,
        ]);
     }


    protected function findModel($id)
    {
        if (($model = Setting::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
