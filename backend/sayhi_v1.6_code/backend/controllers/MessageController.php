<?php
namespace backend\controllers;
use common\models\Setting;
use common\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use common\models\MessageGroup;
use common\models\Message;
use backend\models\Ad;
use yii\filters\VerbFilter;



class MessageController extends Controller
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
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex($group_id = null,$ad_id=null)
    {
        $userId = Yii::$app->user->identity->id;
        
       
        $modelGroup = new MessageGroup();
        $modelAd = new Ad();
        $modelMessage = new Message();


        $modelMessage->scenario  = 'create';
        $resultGroup  = $modelGroup->getActiveGroup($userId);


        
        if($modelMessage->load(Yii::$app->request->post()) && $modelMessage->validate()) {

            $ad_id_form = $modelMessage->ad_id;
            $group_id_form =  $modelMessage->group_id;
            if($group_id_form==0){
                $resultAd = $modelAd->findOne($ad_id_form);
                $modelGroup->ad_id        = $ad_id_form;
                $modelGroup->receiver_id = $resultAd->user_id;
                if($modelGroup->save(false)){
                     $group_id_form = $modelGroup->id;
                    
                }


            }

            if($group_id_form>0){
                $currentGroup = $modelGroup->findOne($group_id_form);
                if($currentGroup->sender_id==$userId){
                    $modelMessage->receiver_id = $currentGroup->receiver_id;

                }else{
                    $modelMessage->receiver_id = $currentGroup->sender_id;

                }
                
                $modelMessage->group_id = $group_id_form;
               if($modelMessage->save()){

                    return $this->redirect(['index','group_id'=>$modelMessage->group_id]);
                    //return $this->redirect(['index', 'type' => $type]);
               }
              



            }else{


            }
          
        }


        if($group_id ==null && $ad_id==null){
            $group_id = @$resultGroup[0]['id'];
        }


        $resultMessage = [];
        if($group_id>0){
            
            $currentGroup = $modelGroup->findOne($group_id);
            $ad_id=$currentGroup->ad_id;

            $resultMessage = $modelMessage->find()->where(['group_id'=>$group_id])->all();



        }
        $resultAd=[];
        if($ad_id>0){
            $resultAd = $modelAd->findOne($ad_id);
            
        }

        return $this->render('index', [
            'resultGroup' => $resultGroup,
            'userId' => $userId,
            'groupId'=>$group_id,
            'adId'=>$ad_id,
            'resultAd' =>$resultAd,
            'resultMessage'=>$resultMessage,
            'modelMessage' => $modelMessage
        ]);
      
    }



}