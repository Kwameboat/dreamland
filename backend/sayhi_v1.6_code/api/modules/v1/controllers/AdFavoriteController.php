<?php
namespace api\modules\v1\controllers;
use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\FavoriteAd;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\Ad;


class AdFavoriteController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\favoriteAd';   
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
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
            'except'=>[],
            'authMethods' => [
                HttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $userId = Yii::$app->user->identity->id;
       $model = new Ad();
        $result  = $model->find()
            ->joinWith('locations')
            ->joinWith('favorite')
            ->select(['ad.id','ad.user_id','ad.category_id','ad.title','ad.sub_category_id','ad.status','ad.phone','ad.price','ad.view','ad.description','featured','featured_exp_date','currency','is_banner_ad','package_banner_id','ad.created_at','ad.deal_start_date','ad.deal_end_date','ad.deal_price'])
            ->where(['favorite_ad.user_id'=>$userId]);
            // ->all();
            $dataProvider = new ActiveDataProvider([
                'query' => $result,
                'pagination' => [
                    'pageSize' => 20,
                ]
            ]);
        
        $response['message']='Favorite list found successfully';
        $response['ad']=$dataProvider;
        return $response;
       
    }


    public function actionCreate()
    {
       
        $model = new FavoriteAd();
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }


        $adId =  @(int) $model->ad_id;
       
        $totalCount = $model->find()->where(['ad_id'=>$adId, 'user_id'=>$userId])->count();
        if($totalCount>0){
           $response['statusCode']=422;
          $response['message']='You have already added in favorite list this ad';
          return $response; 
 
        }
 
         if($model->save(false)){
             $response['message']='Ad added in favorite list successfully';
             return $response; 
         }else{
             $response['statusCode']=422;
             $response['message']='Ad not added in favorite list successfully';
             return $response; 
 
 
         }
        
    }

    public function actionDeleteList()
    {
       
        $model = new FavoriteAd();
        $userId = Yii::$app->user->identity->id;
        $model->scenario ='create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
        
            return $response;
        }


        $adId =  @(int) $model->ad_id;
       $result =  $model->find()->where(['ad_id'=>$adId, 'user_id'=>$userId])->one();
        if(isset($result->id)){
            if($result->delete()){
       
                $response['message']='Favorite removed from list successfully';
                return $response; 
            }else{
                $response['statusCode']=422;
                $response['message']='Favorite not removed successfully';
                return $response; 


            }

        }else{
            $response['statusCode']=422;
            $response['message']='Action not performed';
            return $response; 


        }
       
       
        

        
        
    }





}


