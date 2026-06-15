<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Business;
use Yii;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use api\modules\v1\models\Coupon;
use api\modules\v1\models\CouponSearch;
use api\modules\v1\models\Notification;
use api\modules\v1\models\Comment;
use api\modules\v1\models\CommentSearch;
use api\modules\v1\models\User;
use api\modules\v1\models\UserFavorite;

/**
 * Coupon Controller API
 *
 
 */
class CouponController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Coupon';   
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


    public function actionIndex(){


        $model = new CouponSearch();

        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['coupon']=$result;
        return $response;

        
    }

    public function actionLists(){

        $model =  new Coupon();     
        $result = $model->find()->where(['business.status'=>Coupon::STATUS_ACTIVE])->orderBy(['business.name'=>SORT_DESC])->all();        
        $response['businessLists']=$result;
        return $response;

        
    }

    public function actionShare($id=null){
        $model =  new Coupon();     
        $result = $model->find()->where(['id'=>$id])->andWhere(['status'=>Coupon::STATUS_ACTIVE])->all();        
        $response['couponShare']=$result;
        return $response;    
    }

        /**
     * add comment
     */

     public function actionAddComment()
     {
         $model = new Comment();
        //  $modelFollower = new Follower();
         $userId = Yii::$app->user->identity->id;
         $model->scenario = 'create';
         $model->load(Yii::$app->getRequest()->getBodyParams(), '');
         if (!$model->validate()) {
             $response['statusCode'] = 422;
             $response['errors'] = $model->errors;
             return $response;
         }
         $couponId = @(int) $model->reference_id;
         $model->status = Comment::STATUS_ACTIVE;
         $model->type = Comment::TYPE_COUPON;
         
         $parentId = (int)@$model->parent_id;


         if($parentId>0){
            $model->level = Comment::LEVEL_TWO;
         }
         $model->parent_id = $parentId;
         
         
         if ($model->save(false)) {
            
             $modelCoupon = new Coupon();
             $modelCoupon->updateCommentCounter($couponId);
 
             $response['message'] = Yii::$app->params['apiMessage']['post']['commentSuccess'];
             $response['id'] = $model->id;
             return $response;
         } else {
             $response['statusCode'] = 422;
             $errors['message'][] = Yii::$app->params['apiMessage']['coomon']['actionFailed'];
             $response['errors'] = $errors;
             return $response;
         }
     }

     // Get all coupon comments
     public function actionCommentList(){
        $model =  new CommentSearch();
        $result = $model->search(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['commentLists']=$result;
        return $response;
     }

     // Add  Favrioute
    public function actionAddFavorite()
    {
      
        $userId                 = Yii::$app->user->identity->id; 
        $model                  =   new Coupon();
        $modelBusiness                  =   new Business();
        $modelUserFavorite  =   new UserFavorite();
        $modelUser   =   new User();
        $modelUserFavorite->scenario ='create';
        if (Yii::$app->request->isPost) {
           
            $modelUserFavorite->load(Yii::$app->getRequest()->getBodyParams(), '');
          
            if(!$modelUserFavorite->validate()) {
              
                $response['statusCode']=422;
                $response['errors']=$modelUserFavorite->errors;
                return $response;
            }
            
           $referenceId = @(int) $modelUserFavorite->reference_id;
           $type =  @(int) $modelUserFavorite->type;
           if($type ==UserFavorite::TYPE_COUPON){
            $result    = $model->findOne($referenceId);

           }elseif($type ==UserFavorite::TYPE_BUSINESS){
              $result     = $modelBusiness->findOne($referenceId);
           }    
          
           
            if(!$result){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }

            $resultCount =$modelUserFavorite->find()->where(['user_id'=>$userId,'reference_id'=>$referenceId, 'type'=>$type])->count();

            if($resultCount>0){
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['podcast']['alreadyFavorite'];
                $response['errors']=$errors;
                return $response;
            
            }

            //resultUser
            $modelUserFavorite->user_id       =   $userId;
            $modelUserFavorite->reference_id    =   $referenceId;
            
            if($modelUserFavorite->save()){
               
                
                $response['message']=Yii::$app->params['apiMessage']['podcast']['AddFavorite'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            }

            
        }
 
    }

    public function actionRemoveFavorite()
    {
        $userId                 = Yii::$app->user->identity->id;
        $model                  =   new Coupon();
        $modelBusiness                  =   new Business();
        $modelUserFavorite  =   new UserFavorite();
        $modelUser   =   new User();
        $modelUserFavorite->scenario ='removeFavorite';
        if (Yii::$app->request->isPost) {
            $modelUserFavorite->load(Yii::$app->getRequest()->getBodyParams(), '');
            if(!$modelUserFavorite->validate()) {
                $response['statusCode']=422;
                $response['errors']=$modelUserFavorite->errors;
                return $response;
            }
            $referenceId = @(int) $modelUserFavorite->reference_id;
            $type =  @(int) $modelUserFavorite->type;

            if($type ==UserFavorite::TYPE_COUPON){
                $result    = $model->findOne($referenceId);
            }elseif($type ==UserFavorite::TYPE_BUSINESS){
                $result     = $modelBusiness->findOne($referenceId);
            }
    

            if(!$result){
                
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


            $resultFavorite =$modelUserFavorite->find()->where(['user_id'=>$userId,'reference_id'=>$referenceId,'type'=>$type])->one();

            if(!$resultFavorite){
                
                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                $response['errors']=$errors;
                return $response;
            
            }


        
            if($resultFavorite->delete()){
               
                
                $response['message']=Yii::$app->params['apiMessage']['podcast']['removedFavorite'];
                return $response; 
            }else{

                $response['statusCode']=422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors']=$errors;
                return $response;
            
            }

            
        }
        
    }

    public function actionMyFavoriteList()
    {

        $model = new CouponSearch();

        $result = $model->CouponMyFavorite(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        
        $response['couponFavoriteList']=$result;
        return $response;

        
    }

    public function actionDeleteComment()
    {
        $model = new Comment();
        $userId = @Yii::$app->user->identity->id;
        $model->scenario = 'couponDelete';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $coupon_comment_id = @(int) $model->id;

        $model = Comment::find()->where(['id' => $coupon_comment_id, 'user_id' => $userId , 'type'=>Comment::TYPE_COUPON])->andWhere(['status'=>Comment::STATUS_ACTIVE])->one();

        if ($model) {
            $commentStatus = Comment::deleteAll(['id'=>$coupon_comment_id, 'user_id' => $userId,'type'=>Comment::TYPE_COUPON]);
            if ($commentStatus) {

                $getData = Coupon::find()->where(['id' => @$model->reference_id])->one();
                if($getData){
                    $oldTotalcomment = @$getData->total_comment;
                    $newTotalcomment = $oldTotalcomment-1;
                    $getData->total_comment = $newTotalcomment;
                    $getData->save();
                }
                $response['message'] = Yii::$app->params['apiMessage']['coupon']['deleted'];

                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }else{
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }

    }


}


