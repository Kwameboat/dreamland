<?php

namespace api\modules\v1\controllers;

use yii\rest\ActiveController;
use api\modules\v1\models\Category;
use api\modules\v1\models\CategorySearch;
use api\modules\v1\models\LiveTvCategory;
use api\modules\v1\models\GiftCategory;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;

use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\PodcastCategory;
use api\modules\v1\models\Poll;
use Yii;
/**
 * Category Controller API
 *
 
 */
class CategoryController extends ActiveController
{
  public $modelClass = 'api\modules\v1\models\category';
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
      'only' => ['live-tv', 'gift', 'event','campaign'],
      'authMethods' => [
        HttpBearerAuth::className()
      ],
    ];
    return $behaviors;
  }


  public function actionIndex()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image'])
      // ->joinWith('subCategory')
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN])->orderBy(['name' => SORT_ASC])->all();
    // $modelRes= $model->find()->where(['status'=>1])->orderBy(['id'=> SORT_DESC])->all();


    //$modelRes1 = ArrayHelper::toArray($modelRes);


    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }


  public function actionLiveTv()
  {
    $model = new LiveTvCategory();
    $modelRes = $model->find()
      ->select(['live_tv_category.id', 'live_tv_category.name', 'live_tv_category.image'])
      // ->joinWith('subCategory')
      ->where(['live_tv_category.status' => $model::STATUS_ACTIVE, 'live_tv_category.level' => LiveTvCategory::LEVEL_MAIN])->orderBy(['name' => SORT_ASC])->all();
    // $modelRes= $model->find()->where(['status'=>1])->orderBy(['id'=> SORT_DESC])->all();
    //$modelRes1 = ArrayHelper::toArray($modelRes);


    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionPodcast()
  {
    $model = new PodcastCategory();
    $modelRes = $model->find()
      ->select(['podcast_category.id', 'podcast_category.name', 'podcast_category.image'])
      // ->joinWith('subCategory')
      ->where(['podcast_category.status' => $model::STATUS_ACTIVE, 'podcast_category.level' => PodcastCategory::LEVEL_MAIN])->orderBy(['name' => SORT_ASC])->all();


    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionPodcastShow()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_PODCAST_SHOW])->orderBy(['name' => SORT_ASC])->all();

    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionGift()
  {
    $model = new GiftCategory();
    $modelRes = $model->find()
      ->select(['gift_category.id', 'gift_category.name', 'gift_category.image'])
      // ->joinWith('subCategory')
      ->where(['gift_category.status' => $model::STATUS_ACTIVE, 'gift_category.level' => GiftCategory::LEVEL_MAIN])->orderBy(['name' => SORT_ASC])->all();

    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionEvent()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      // ->joinWith('subCategory')
      ->joinWith([
        'event' => function ($query) {
          $currentTime = time();
        //  $query->andwhere(['>', 'end_date', $currentTime]);
          $query->andOnCondition(['>', 'end_date', $currentTime]);
        }
      ])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_EVENT])->orderBy(['category.name' => SORT_ASC])->all();


    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }
  public function actionReelAudio()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_REEL_AUDIO])->orderBy(['name' => SORT_ASC])->all();


    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionCampaign()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_FUNDRASING])->orderBy(['name' => SORT_ASC])->all();
    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionPoll()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_POLL])->orderBy(['name' => SORT_ASC])->all();

    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionBusinessCategory()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_BUSINESS_CATEGORY])->orderBy(['name' => SORT_ASC])->all();

    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

  public function actionAll()
  {

      $modelSearch = new CategorySearch();
      $result = $modelSearch->searchCategory(Yii::$app->request->queryParams);
      
      $response['message']=Yii::$app->params['apiMessage']['common']['listFound'];
      $response['category']=$result;
      return $response; 


  }

  public function actionJob()
  {
    $model = new Category();
    $modelRes = $model->find()
      ->select(['category.id', 'category.name', 'category.image', 'type'])
      ->where(['category.status' => $model::STATUS_ACTIVE, 'category.level' => Category::LEVEL_MAIN, 'category.type' => Category::TYPE_JOB_CATEGORY])->orderBy(['name' => SORT_ASC])->all();

    $response['message'] = 'ok';
    $response['category'] = $modelRes;
    return $response;
  }

}