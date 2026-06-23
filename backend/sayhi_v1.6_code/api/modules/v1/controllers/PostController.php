<?php
namespace api\modules\v1\controllers;

use api\modules\v1\models\Competition;
use api\modules\v1\models\CompetitionUser;
use api\modules\v1\models\Country;
use api\modules\v1\models\HashTag;
use api\modules\v1\models\Notification;
use api\modules\v1\models\Post;
use api\modules\v1\models\PostComment;
use api\modules\v1\models\PostGallary;
use api\modules\v1\models\PostLike;
use api\modules\v1\models\PostSearch;
use api\modules\v1\models\PostView;
use api\modules\v1\models\ReportedPost;
use api\modules\v1\models\Setting;
use api\modules\v1\models\User;
use api\modules\v1\models\Follower;
use api\modules\v1\models\MentionUser;
use api\modules\v1\models\Event;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\imagine\Image;
use yii\db\Expression;
use yii\rest\ActiveController;
use yii\web\UploadedFile;
use api\modules\v1\models\ProfileCategoryType;
use api\modules\v1\models\ProfileView;
use api\modules\v1\models\AgeGroup;
use api\modules\v1\models\PostPromotion;
use api\modules\v1\models\PostShare;
use api\modules\v1\models\Poll;
use api\modules\v1\models\UserFavorite;
use api\modules\v1\models\EventTicketBooking;



class PostController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\post';
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
            'except' => ['ad-search', 'search-post', 'view-by-unique-id'],
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];
        return $behaviors;
    }
    public function actionCreate()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new Post();

        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            //$model->imageFile = UploadedFile::getInstanceByName('imageFile');
            // $model->videoFile = UploadedFile::getInstanceByName('videoFile');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            if($model->type == Post::TYPE_RESHARE_POST){
                $model->is_share_post = Post::IS_SHARE_POST_YES;
               
                $origin = [];
                if(empty($model->origin_post_id)){
                    $response['statusCode'] = 422;
                    $origin['origin_post_id'] = "Origin post id cannot be blank.";
                    $response['errors'] = $origin;
                    return $response;
                }
                $origin_post_id = @(int) $model->origin_post_id;
                // echo $model->share_comment;
                // exit("hello");
                $result = $model->findOne($origin_post_id);
                if (!$result) {
                    $response['statusCode'] = 422;
                    $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
                    $response['errors'] = $errors;
                    return $response;
        
                }
                $model->share_level = $result->share_level + 1;
            }

            /*if($model->post_content_type ==Post::CONTENT_TYPE_POLL){
                if(empty($model->poll_id)){
                    $response['statusCode'] = 422;
                    $errors['poll_id'] = ["poll_id cannot be blank."];
                    $response['errors'] = $errors;
                    return $response;
                }
            }
            if(!empty($model->poll_id)){
                $modelPoll = new Poll();
                $pollDetails =$modelPoll->find()->where(['id'=>$model->poll_id , 'status'=>Poll::STATUS_ACTIVE])->one();
                if(!$pollDetails){
                    $response['statusCode'] = 422;
                    $errors['message'][] = "poll_id doesnot exit!";
                    $response['errors'] = $errors;
                    return $response;
                }
            }*/
            if($model->type ==Post::CONTENT_TYPE_EVENT){
                if(empty($model->event_id)){
                    $response['statusCode'] = 422;
                    $errors['event_id'] = ["event_id cannot be blank."];
                    $response['errors'] = $errors;
                    return $response;
                }
                $modelEvent = new Event();
                $resultEvent  =$modelEvent->findOne($model->event_id);

                $modelEventTicketBooking = new EventTicketBooking();
                
                $purchasedTicketCount = $modelEventTicketBooking->find()
                    ->where(['event_id'=>$model->event_id])
                    ->andWhere(
                    [
                        'or',
                            ['event_ticket_booking.user_id'=>$userId],
                            ['event_ticket_booking.gifted_to'=>$userId]
                    ]
                    )
                    ->count();
                    if($purchasedTicketCount==0 && $resultEvent->is_paid==1){
                        $response['statusCode'] = 422;
                        $errors['message'][] = Yii::$app->params['apiMessage']['post']['onlyTicketBuyerAllower'];
                        $response['errors'] = $errors;
                        return $response;

                    }
            }
            $displayWhose = (int)$model->display_whose;
            if(!$displayWhose){
                $displayWhose=1;
            }

            // Dreamland: paid reels — admin sets price; user cannot input price
            $isPaid = (int) Yii::$app->request->getBodyParam('is_paid', 0);
            if ($isPaid === 1) {
                $model->is_paid = 1;
                $model->price_credits = null;
                $model->appraisal_status = 'pending_safety';
                $model->status = Post::STATUS_BLOCKED;
            } else {
                $model->is_paid = 0;
                $model->price_credits = null;
                $model->appraisal_status = 'pending_safety';
            }
            // Strip any client-supplied price — admin assigns during appraisal
            unset($model->price_credits);
            if ((int) $model->is_paid === 1) {
                $model->price_credits = null;
            }

            if ($model->save()) {

                $postId = $model->id;

                if ($model->hashtag) {
                    $modelHashTag = new HashTag();
                    $modelHashTag->updateHashTag($model->id, $model->hashtag);
                }

                if ($model->mentionUser) {
                    $modelMentionUser = new MentionUser();
                    $userIds = $modelMentionUser->updateMentionUser($model->id, $model->mentionUser);


                    // send notification 


                    if ($userIds) {


                        $modelNotification = new Notification();
                        $notificationInput = [];
                        $notificationData = Yii::$app->params['pushNotificationMessage']['mentionUserPost'];
                        $replaceContent = [];
                        $replaceContent['TITLE'] = $model->title;
                        $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);



                        $notificationInput['referenceId'] = $postId;
                        $notificationInput['userIds'] = $userIds;
                        $notificationInput['notificationData'] = $notificationData;
                        $modelNotification->createNotification($notificationInput);
                        // end send notification 
                    }



                }



                if ($model->gallary) {
                    $modelPostGallary = new PostGallary();
                    $modelPostGallary->updateGallary($model->id, $model->gallary);
                }

                // Dreamland: enqueue multimodal safety scan
                if ((int) $model->type === Post::TYPE_REEL || (int) $model->post_content_type === Post::CONTENT_TYPE_MEDIA) {
                    Yii::$app->dreamlandSafety->enqueueVideoScan(
                        $model,
                        ['tags' => $model->hashtag ? explode(',', $model->hashtag) : []]
                    );
                    Yii::$app->dreamlandSafety->processPostScan((int) $model->id);
                }

                if($model->type == Post::TYPE_RESHARE_POST){
                    $modelPostShare = new PostShare;
                    $modelPostShare->post_id = $origin_post_id;
                    $modelPostShare->save();
                    $model->updateShareCounter($origin_post_id);
                    if ($result->is_share_post) {
                        $model->updateShareCounter($result->origin_post_id);
                    }
                }
                $response['message'] = Yii::$app->params['apiMessage']['post']['postCreateSuccess'];
                $response['post_id'] = $model->id;
                //$response['image']=Yii::$app->params['pathUploadVideoThumb'] ."/".$model->image;
                //$response['video']=Yii::$app->params['pathUploadVideo'] ."/".$model->video;
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['post']['postCreateFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        }

    }

    
    public function actionUpdate($id){
        $userId = Yii::$app->user->identity->id;
        $model = $this->findModel($id);
        if($model->user_id != $userId){
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['notAllowed'];
            $response['errors'] = $errors;
            return $response;
        }
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if(!$model->validate()) {
            $response['statusCode']=422;
            $response['errors']=$model->errors;
            return $response;
        }
        
        if($model->save(false)){
            
            
            $response['message']=Yii::$app->params['apiMessage']['post']['postUpdateSuccess'];
            return $response; 

        }
        
    }

    public function actionCreate_old()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new Post();

        $model->scenario = 'create';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            //$model->imageFile = UploadedFile::getInstanceByName('imageFile');
            // $model->videoFile = UploadedFile::getInstanceByName('videoFile');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }


            if ($model->save()) {

                $postId = $model->id;

                if ($model->hashtag) {
                    $modelHashTag = new HashTag();
                    $modelHashTag->updateHashTag($model->id, $model->hashtag);
                }

                if ($model->mentionUser) {
                    $modelMentionUser = new MentionUser();
                    $userIds = $modelMentionUser->updateMentionUser($model->id, $model->mentionUser);


                    // send notification 


                    if ($userIds) {


                        $modelNotification = new Notification();
                        $notificationInput = [];
                        $notificationData = Yii::$app->params['pushNotificationMessage']['mentionUserPost'];
                        $replaceContent = [];
                        $replaceContent['TITLE'] = $model->title;
                        $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);



                        $notificationInput['referenceId'] = $postId;
                        $notificationInput['userIds'] = $userIds;
                        $notificationInput['notificationData'] = $notificationData;
                        $modelNotification->createNotification($notificationInput);
                        // end send notification 
                    }



                }



                if ($model->gallary) {
                    $modelPostGallary = new PostGallary();
                    $modelPostGallary->updateGallary($model->id, $model->gallary);
                }

                $response['message'] = Yii::$app->params['apiMessage']['post']['postCreateSuccess'];
                $response['post_id'] = $model->id;
                //$response['image']=Yii::$app->params['pathUploadVideoThumb'] ."/".$model->image;
                //$response['video']=Yii::$app->params['pathUploadVideo'] ."/".$model->video;
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['post']['postCreateFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        }

    }

    public function actionView($id)
    {

        
        $model = new PostSearch();

        $result = $model->find()->where(['post.id' => $id])
            ->joinWith([
                'user' => function ($query) {
                    $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ])
            ->joinWith([
                'clubDetail.createdByUser' => function ($query) {
                    $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ])

            ->one();
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['post'] = $result;
        return $response;

    }
    
    public function actionViewByUniqueId($unique_id)
    {

        
        $model = new PostSearch();

        $result = $model->find()->where(['post.unique_id' => $unique_id])
            ->joinWith([
                'user' => function ($query) {
                    $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ])
            ->joinWith([
                'clubDetail.createdByUser' => function ($query) {
                    $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ])

            ->one();
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['post'] = $result;
        return $response;

    }
    public function actionDelete($id)
    {
        $userId = Yii::$app->user->identity->id;

        $model = Post::find()->where(['id' => $id, 'user_id' => $userId])->one();



        if ($model) {
            $model->status = Post::STATUS_DELETED;
            if ($model->save(false)) {

                Yii::$app->db->createCommand()->delete('mention_user', ['post_id'=>$id])->execute();
                
                $response['message'] = Yii::$app->params['apiMessage']['post']['deleted'];

                return $response;
            } else {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;
            }
        }

    }

    public function actionUploadGallary()
    {

        $model = new PostGallary();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->filenameFile = UploadedFile::getInstanceByName('filenameFile');
            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $files = [];
            if ($model->filenameFile) {

                
                $type = Yii::$app->fileUpload::TYPE_POST;
                $files = Yii::$app->fileUpload->uploadFile($model->filenameFile, $type, false);

                $imageName = $files[0]['file'];
                $fileUrl = $files[0]['fileUrl'];


                /*$microtime = (microtime(true) * 10000);
                $uniqueimage = $microtime . '_' . date("Ymd_His") . '_' . substr(md5($microtime), 0, 10);
                $imageName = $uniqueimage . '.' . $model->filenameFile->extension;
                // $model->filename    =     $imageName;
                $s3 = Yii::$app->get('s3');
                $imagePath = $model->filenameFile->tempName;
                $result = $s3->upload('./' . Yii::$app->params['pathUploadImageFolder'] . '/' . $imageName, $imagePath);
                */

            }

            $response['message'] = 'Gallary updated successfully';
            $response['filename'] = $imageName;
            $response['fileUrl'] = $fileUrl;
            return $response;
        }
    }

    public function actionCompetitionImage()
    {
        $userId = Yii::$app->user->identity->id;
        $model = new Post();
        $modelCompetition = new Competition();
        $modelCompetitionUser = new CompetitionUser();

        $model->scenario = 'competitionImage';

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            //  $model->imageFile = UploadedFile::getInstanceByName('imageFile');

            if (!$model->validate()) {
                $response['statusCode'] = 422;
                $response['errors'] = $model->errors;
                return $response;
            }
            $currentTime = time();
            $competitionId = @(int) $model->competition_id;
            $resultCompetition = $modelCompetition->find()->where(['id' => $competitionId, 'status' => Competition::STATUS_ACTIVE])->one();
            if (!$resultCompetition) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['noRecord'];
                $response['errors'] = $errors;
                return $response;

            }

            if ($resultCompetition->start_date > $currentTime || $resultCompetition->end_date < $currentTime) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['notAvailable'];
                $response['errors'] = $errors;
                return $response;

            }

            $resultCompetitionUser = $modelCompetitionUser->find()->where(['competition_id' => $competitionId, 'user_id' => $userId])->one();

            if (!$resultCompetitionUser) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['joinCompetition'];
                $response['errors'] = $errors;
                return $response;

            }

            $countPost = $model->find()->where(['competition_id' => $competitionId, 'user_id' => $userId])->count();

            if ($countPost) {
                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['competition']['alreadyPosted'];
                $response['errors'] = $errors;
                return $response;

            }

            /*
            if($model->imageFile){
            //print_r($model->imageFile->tempName);
            //die;
            $microtime             =     (microtime(true)*10000);
            $uniqueimage        =    $microtime.'_'.date("Ymd_His").'_'.substr(md5($microtime),0,10);
            $imageName             =    $uniqueimage.'.'.$model->imageFile->extension;
            $model->image         =     $imageName;
            $s3 = Yii::$app->get('s3');
            $imagePath = $model->imageFile->tempName;
            $result = $s3->upload('./'.Yii::$app->params['pathUploadImageFolder'].'/'.$imageName, $imagePath);
            //echo '<pre>';
            //print_r($result);
            //die;
            //$promise = $s3->commands()->upload('./video-thumb/'.$imageName, $imagePath)->async()->execute();
            }
            */

            $model->type = Post::TYPE_COMPETITION;
            if ($model->save()) {

                if ($model->hashtag) {
                    $modelHashTag = new HashTag();
                    $modelHashTag->updateHashTag($model->id, $model->hashtag);
                }

                if ($model->gallary) {
                    $modelPostGallary = new PostGallary();
                    $modelPostGallary->updateGallary($model->id, $model->gallary);
                }

                $response['message'] = Yii::$app->params['apiMessage']['post']['postCreateSuccess'];
                $response['post_id'] = $model->id;
                //$response['image']=Yii::$app->params['pathUploadVideoThumb'] ."/".$model->image;
                //$response['video']=Yii::$app->params['pathUploadVideo'] ."/".$model->video;
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['post']['postCreateFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        }

    }

    public function actionMyPost()
    {

        $model = new PostSearch();

        $result = $model->searchMyPost(Yii::$app->request->queryParams);



        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['post'] = $result;

        return $response;

    }


    public function actionMyPostMentionUser()
    {

        $model = new PostSearch();

        $result = $model->searchMyPostMentionUser(Yii::$app->request->queryParams);

        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['post'] = $result;

        return $response;

    }

    /**
     * search post
     */

    public function actionSearchPost()
    {

        $model = new PostSearch();
        $result = $model->search(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['post'] = $result;
        return $response;

    }


    /**
     * hash Counter list
     */

    public function actionHashCounterList()
    {


        $model = new HashTag();


        $hashtag = Yii::$app->request->queryParams['hashtag'];

        $query = $model->find()
            ->select(['hashtag', 'count(hashtag) as counter']);


        $query->where(
            ['like', 'hashtag', $hashtag . '%', false]
        );
        $query->groupBy('hashtag');


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['results'] = $dataProvider;
        return $response;

    }


    /**
     * Report Post
     */
    public function actionReportPost()
    {

        $model = new ReportedPost();
        $userId = Yii::$app->user->identity->id;

        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $postId = @(int) $model->post_id;

        $totalCount = $model->find()->where(['post_id' => $postId, 'user_id' => $userId, 'status' => ReportedPost::STATUS_PENDING])->count();
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['post']['alreadyReported'];
            $response['errors'] = $errors;
            return $response;

        }

        $model->status = ReportedPost::STATUS_PENDING;
        if ($model->save(false)) {
            $response['message'] = Yii::$app->params['apiMessage']['post']['reportedSuccess'];
            return $response;
        } else {

            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
        }
    }

    /**
     * like post
     */

    public function actionLike()
    {
        $model = new PostLike();
        $modelFollower = new Follower();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $postId = @(int) $model->post_id;
        $totalCount = $model->find()->where(['post_id' => $postId, 'user_id' => $userId])->count();

        
        if ($totalCount > 0) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['post']['postLikeAlready'];
            $response['errors'] = $errors;
            return $response;

        }

        if ($model->save(false)) {
            
            $modelPost = new Post();
            $totalLike = $modelPost->updateLikeCounter($postId);


            $resultPost = $modelPost->findOne($postId);

            // send notification 

            $toUserId = $resultPost->user_id;
            $isFollowing = $modelFollower->find()->where(['user_id' => $userId, 'follower_id' => $toUserId])->count();


            $modelNotification = new Notification();
            $notificationInput = [];
            $notificationData = Yii::$app->params['pushNotificationMessage']['likePost'];
            $replaceContent = [];
            $replaceContent['USER'] = Yii::$app->user->identity->username;
            $notificationData['body'] = $modelNotification->replaceContent($notificationData['body'], $replaceContent);

            $userIds = [];

            if ($userId != $resultPost->user_id) {
                $userIds[] = $resultPost->user_id;
            }


            $notificationInput['referenceId'] = $postId;
            $notificationInput['userIds'] = $userIds;
            $notificationInput['notificationData'] = $notificationData;
            $notificationInput['isFollowing'] = $isFollowing;
            $modelNotification->createNotification($notificationInput);
            // end send notification 



            $response['message'] = Yii::$app->params['apiMessage']['post']['postLikeSuccess'];
            $response['total_like'] = $totalLike;
            return $response;
        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['post']['postLikeFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }

    /**
     * unlike post
     */

    public function actionUnlike()
    {

        $model = new PostLike();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;

            return $response;
        }

        $postId = @(int) $model->post_id;

        $result = $model->find()->where(['post_id' => $postId, 'user_id' => $userId])->one();
        if (isset($result->id)) {
            if ($result->delete()) {

                $modelPost = new Post();
                $totalLike = $modelPost->updateLikeCounter($postId, 'unlike');

                $response['message'] = Yii::$app->params['apiMessage']['post']['postUnlikeSuccess'];
                $response['total_like'] = $totalLike;
                return $response;
            } else {

                $response['statusCode'] = 422;
                $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
                $response['errors'] = $errors;
                return $response;

            }

        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['post']['postUnlikeFailed'];
            $response['errors'] = $errors;
            return $response;

        }

    }

    /**
     * like post
     */

    public function actionViewCounter()
    {
        $model = new PostView();

        $userId = @Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $postId = @(int) $model->post_id;
        $promotionId = @(int) $model->post_promotion_id;
        $postData = Post::find()->where(['id' => $postId])->one();
        if (!empty($postData)) {
            $postUserId = $postData->user_id;
        } else {
            $response['statusCode'] = 422;
            $response['errors'] = 'Post Id not found in Post';
            return $response;
        }
        if (!empty($postUserId)) {
            $followerData = follower::find()->where(['user_id' => $postUserId, 'follower_id' => $userId])->andWhere(['!=', 'type', follower::FOLLOW_REQUEST])->one();
        }
        $isfollower = '';
        if (!empty($followerData)) {
            $isfollower = 1;
        } else {
            $isfollower = 0;
        }

        $userModel = new User();
        $userResult = $userModel->getFullProfileMy($userId);
        $dob = $userResult->dob;
        if (!empty($dob)) {
            $age = (date('Y') - date('Y', strtotime($dob)));
        } else {
            $age = 0;
        }

        $model->age = $age;
        $model->gender = $userResult->sex;
        $model->country_id = $userResult->country_id;
        $model->profile_category_id = $userResult->profile_category_type;
        $model->is_follower = $isfollower;
        $model->impression_count = PostView::IMPRESSION_COUNT;
        $model->ad_post_impression_count = PostView::IMPRESSION_COUNT;
        // $totalCount = $model->find()->where(['post_id' => $postId, 'user_id' => $userId])->count();

        $result = $model->find()->where(['post_id' => $postId, 'user_id' => $userId])->one();
        if (!empty($result)) {
            $post_view_Id = $result->id;
            $modelPostView = PostView::find()->where(['id' => $post_view_Id])->one();

            if (!empty($model->view_source)) {
                $modelPostView->view_source = @$model->view_source;
            }
            if ($model->view_source == PostView::VIEW_SOURCE_TYPE_PROMOTION) {
                $modelPostView->post_promotion_id = @$model->post_promotion_id;
                $old_ad_impression_count = $result->ad_post_impression_count;
                $total_ad_impression_count = $old_ad_impression_count + PostView::IMPRESSION_COUNT;
                $modelPostView->ad_post_impression_count = $total_ad_impression_count;
            } else {
                $old_impression_count = $result->impression_count;
                $total_impression_count = $old_impression_count + PostView::IMPRESSION_COUNT;
                $modelPostView->impression_count = $total_impression_count;
            }
            // $modelPostView->post_promotion_id = @$model->post_promotion_id;

            // echo $promotionId;
            // exit;
            $modelPromotion = new PostPromotion();
            $modelPromotion->updatePromotionReachCounter($promotionId, $postId);
            if ($modelPostView->save(false)) {

            }


        }
        // if ($totalCount == 0) {
        else {
            $model->save(false);
            $modelPost = new Post();
            $modelPost->updateViewCounter($postId);
            $modelPromotion = new PostPromotion();
            $modelPromotion->updatePromotionReachCounter($promotionId, $postId);

        }

        $response['message'] = 'ok';
        return $response;

    }


    /**
     * like post
     */

    public function actionPromotionAdView()
    {

        $modelSetting = new Setting();

        $settingResult = $modelSetting->find()->one();
        $eachViewCoin = (int) $settingResult->each_view_coin;

        $userId = Yii::$app->user->identity->id;
        if ($userId > 0 && $eachViewCoin > 0) { /// each view get coin
            $modelUser = new User();
            $userResult = $modelUser->findOne($userId);
            $userResult->available_coin = $userResult->available_coin + $eachViewCoin;
            $userResult->save(false);
        }

        $response['message'] = 'ok';
        return $response;

    }


    /**
     * add comment
     */

    public function actionAddComment()
    {
        $model = new PostComment();
        $modelFollower = new Follower();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'create';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $postId = @(int) $model->post_id;


        if ($model->filename == '' && $model->comment == '') {
            $response['statusCode'] = 422;
            $errors['message'][] = 'Comment or Filename cannot be blank. At least fill one column.';
            $response['errors'] = $errors;
            return $response;
        }

        if(!empty(@$model->parent_id)){
            $model->level = PostComment::LEVEL_TWO;
         }

        if ($model->save(false)) {
            $modelPost = new Post();
            $totalLike = $modelPost->updateCommentCounter($postId);

            //// push notification
            /*
            $resultPost = Post::findOne($postId);
            $modelUser = new User();
            $userResult = $modelUser->findOne($resultPost->user_id);
            if ($userResult->device_token) {
            $message = $model->comment;
            $title = Yii::$app->user->identity->name . ' write new comment on your post';
            $dataPush['title'] = $title;
            $dataPush['body'] = $message;
            $dataPush['data']['notification_type'] = 'newComment';
            $dataPush['data']['post_id'] = $postId;
            $deviceTokens[] = $userResult->device_token;
            Yii::$app->pushNotification->sendPushNotification($deviceTokens, $dataPush);
            }
            //// end push notification
            /// add notification to list
            $modelNotification = new Notification();
            $modelNotification->user_id = $resultPost->user_id;
            $modelNotification->type = Notification::TYPE_NEW_COMMENT;
            $modelNotification->reference_id = $postId;
            $modelNotification->title = $title;
            $modelNotification->message = $message;
            $modelNotification->save(false);
            /// end add notification to list
            */



            // send notification 

            $resultPost = Post::findOne($postId);
            $toUserId = $resultPost->user_id;
            $isFollowing = $modelFollower->find()->where(['user_id' => $userId, 'follower_id' => $toUserId])->count();

            $modelNotification = new Notification();
            $notificationInput = [];
            $notificationData = Yii::$app->params['pushNotificationMessage']['newComment'];
            $replaceContent = [];
            $replaceContent['USER'] = Yii::$app->user->identity->username;
            $notificationData['title'] = $modelNotification->replaceContent($notificationData['title'], $replaceContent);
            // $notificationData['body'] = $modelNotification->replaceContent($notificationData['title'],$replaceContent);   
            $notificationData['body'] = $model->comment;

            $userIds = [];
            if ($userId != $resultPost->user_id) {
                $userIds[] = $resultPost->user_id;
            }


            $notificationInput['referenceId'] = $postId;
            $notificationInput['userIds'] = $userIds;
            $notificationInput['notificationData'] = $notificationData;
            $notificationInput['isFollowing'] = $isFollowing;
            $notificationInput['createdBy'] = $userId;


            $modelNotification->createNotification($notificationInput);
            // end send notification 

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

    /**
     * list comment
     */

    public function actionCommentList()
    {
        $model = new PostComment();
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'list';

        $model->load(Yii::$app->request->queryParams, '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $postId = @(int) $model->post_id;
        $parent_id = @(int) $model->parent_id;
        $query = $model->find()
            ->joinWith([
                'user' => function ($query) {
                    $query->select(['id', 'name', 'username', 'image', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ])

            ->where(['post_comment.post_id' => $postId])
            ->andWhere(['post_comment.status'=> PostComment::STATUS_ACTIVE])
            ->select(['post_comment.id','post_comment.post_id', 'post_comment.type', 'post_comment.filename', 'post_comment.comment', 'post_comment.user_id', 'post_comment.created_at','post_comment.level', 'post_comment.parent_id'])
            ->orderBy(['post_comment.id' => SORT_ASC]);
            if($parent_id){
                $query->andWhere(['post_comment.level'=> PostComment::LEVEL_TWO]);
                $query->andWhere(['post_comment.parent_id'=> $parent_id]);
            }else{
                $query->andWhere(['post_comment.level'=> PostComment::LEVEL_ONE]);
            }
            $query->leftJoin('pin', 'post_comment.id = pin.reference_id and pin.type=2');
            $query->orderBy([
                new Expression('CASE WHEN pin.reference_id IS NOT NULL THEN 0 ELSE 1 END'), // Pinned posts on top
                'id'=>SORT_ASC
            ]);

        $result = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $response['message'] = 'ok';
        $response['comment'] = $result;

        return $response;

    }

    /**
     * share post
     */

    public function actionShare()
    {
        $model = new Post;
        $userId = Yii::$app->user->identity->id;
        $model->scenario = 'share';
        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if (!$model->validate()) {
            $response['statusCode'] = 422;
            $response['errors'] = $model->errors;
            return $response;
        }
        $postId = @(int) $model->id;
        // echo $model->share_comment;
        // exit("hello");
        $result = $model->findOne($postId);
        if (!$result) {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['noRecord'];
            $response['errors'] = $errors;
            return $response;

        }

        $modelPost = new Post;
        $modelPost->user_id = $userId;
        $modelPost->title = $model->title;
        $modelPost->type = Post::TYPE_RESHARE_POST;
        // $modelPost->video = $result->video;
        // $modelPost->image = $result->image;
        // $modelPost->audio_id = $result->audio_id;
        $modelPost->is_share_post = Post::IS_SHARE_POST_YES;
        $modelPost->share_level = $result->share_level + 1;
        // $modelPost->share_comment = $model->share_comment;
        $origin_post_id = $result->id;
        if ($result->is_share_post) {
            $origin_post_id = $result->origin_post_id;
        }
        // share comment
        $modelPost->origin_post_id = $origin_post_id;

        if ($modelPost->save(false)) {
            $modelPostShare = new PostShare;
            $modelPostShare->post_id = $postId;
            $modelPostShare->save();
            // $tags = [];
            // foreach ($result->hashtags as $tag) {
            //     $tags[] = $tag['hashtag'];
            // }
            // $hashtags = implode(',', $tags);
            // $modelHashTag = new HashTag();
            // $modelHashTag->updateHashTag($modelPost->id, $hashtags);

            $modelPost->updateShareCounter($postId);
            if ($result->is_share_post) {
                $modelPost->updateShareCounter($result->origin_post_id);
            }

            $response['message'] = Yii::$app->params['apiMessage']['post']['postShareSuccess'];
            $response['post_id'] = $modelPost->id;
            return $response;

        } else {
            $response['statusCode'] = 422;
            $errors['message'][] = Yii::$app->params['apiMessage']['common']['actionFailed'];
            $response['errors'] = $errors;
            return $response;
        }
    }

    public function actionMyStats()
    {

        // $model =  new PostView();
        // $postId = @(int) $model->post_id;
        // $currentTime = time();
        // $modelRes= $model->find()->where(['post_id'=> $postId])->all();
        // $response['message']='ok';
        // $response['stats']=$modelRes;
        // return $response;
    }

    public function actionInsight()
    {
        $model = new PostView();
        $postId = (int) Yii::$app->request->queryParams['post_id'];
        $countyDetails = [];
        $modelAgeGroup = new AgeGroup();
        $modelPost = new Post();

        $modelProfileView = new ProfileView();
        $profileViewCount = $modelProfileView->find()->where(['reference_id' => $postId, 'source_type' => ProfileView::SOURCE_TYPE_POST])->all();
        $totalPostShare = 0;
        $postDetails = $modelPost->findOne($postId);
        if($postDetails){
          $totalPostShare =  $postDetails->total_share;
        }
        $modelPostFollow = new Follower();
        $followByPost = (int) $modelPostFollow->find()->where(['reference_id' => $postId, 'source_type' => 1])
            ->count();

        // get country details
        $modelData = $model->find()->where(['post_id' => $postId])->groupBy(['country_id'])->all();
        if (!empty($modelData)) {
            foreach ($modelData as $country) {
                $countryIds[] = $country->country_id;
            }
            if (!empty($countryIds)) {
                foreach ($countryIds as $key => $countyData) {
                    $countyID = $countyData;
                    $countryName = Country::find()->where(['id' => $countyID])->one();
                    if (!empty($countryName)) {
                        $countyDetails[$key]['name'] = @$countryName->name;
                        $countyDetails[$key]['county_view_total'] = $model->find()->where(['post_id' => $postId, 'country_id' => $countyID])
                            ->count();
                    }
                }
                $countyDetails = array_values($countyDetails);
            }
        }
        // get profile category type
        $profileCategoryTypeData = $model->find()->where(['post_id' => $postId])->groupBy(['profile_category_id'])->all();
        // $profileCategoryIds = [];
        $profileCategoryDetails = [];
        if (!empty($profileCategoryTypeData)) {
            foreach ($profileCategoryTypeData as $profile_cate_data) {
                $profileCategoryIds[] = $profile_cate_data->profile_category_id;
            }
            //  $profileCategoryDetails = [];
            if (!empty($profileCategoryIds)) {


                foreach ($profileCategoryIds as $key => $profileCategoryData) {

                    $profileCategorID = $profileCategoryData;
                    $profileCategorName = ProfileCategoryType::find()->where(['id' => $profileCategorID])->one();
                    if (!empty($profileCategorName)) {
                        $profileCategoryDetails[$key]['name'] = @$profileCategorName->name;
                        $profileCategoryDetails[$key]['category_view_total'] = $model->find()->where(['post_id' => $postId, 'profile_category_id' => $profileCategorID])
                            ->count();
                    }
                }
                $profileCategoryDetails = array_values($profileCategoryDetails);
            }
        }

        $modelRes = $model->find()->where(['post_id' => $postId])->all();
        $totalView = count($modelRes);
        $follow = [];
        $genderType = [];
        $impressionCount = 0;
        $adsimpressionCount = 0;
        $male = [];
        $female = [];
        $other = [];
        $followers = [];
        $nonfollowers = [];
        $gender_not_disclose = [];
        $profile_category_type_not_disclose = [];
        $country_not_disclose = [];
        $age_not_disclose = [];
        // print_r($modelRes);
        $agedata = [];
        foreach ($modelRes as $key => $data) {
            $impressionCount += $data->impression_count;
            $adsimpressionCount += $data->ad_post_impression_count;
            $follower = $data->is_follower;
            $gender = $data->gender;
            if ($follower == 1) {
                $followers[] = $follower;
            } else {
                $nonfollowers[] = $follower;
            }
            if ($gender == 1) {
                $male[] = $data->gender;
            } elseif ($gender == 2) {
                $female[] = $data->gender;
            } elseif ($gender == 3) {
                $other[] = $data->gender;
            } elseif ($gender == NULL) {
                $gender_not_disclose[] = $data->gender;
            }
            if ($data->country_id == NULL) {
                $country_not_disclose[] = $data->country_id;
            }
            if ($data->profile_category_id == NULL) {
                $profile_category_type_not_disclose[] = $data->profile_category_id;
            }
            if ($data->age == NULL) {
                $age_not_disclose[] = $data->age;
            }
            if (!empty($data->age)) {
                $ageGroup = $modelAgeGroup->getAge_group_name($data->age);
                $agedata[$key]['name'] = $ageGroup;
            }

            $follower = $data->impression_count;
        }
        if (!empty($agedata)) {
            $ageGroupDetails = array_column($agedata, 'name');
            $ageDetails = @array_count_values($ageGroupDetails);
        } else {
            $ageDetails = [];
        }



        $data = array(
            "total_view" => $totalView,
            "total_impression" => $impressionCount,
            "follower" => count($followers),
            "nonfollower" => count($nonfollowers),
            "male" => count($male),
            "female" => count($female),
            "other" => count($other),
            "gender_not_disclose" => count($gender_not_disclose),
            "country" => $countyDetails,
            "country_not_disclose" => count($country_not_disclose),
            "profile_category_type" => $profileCategoryDetails,
            "profile_category_type_not_disclose" => count($profile_category_type_not_disclose),
            "age" => $ageDetails,
            "age_not_disclose" => count($age_not_disclose),
            "profile_view" => count($profileViewCount),
            "follow_by_post" => $followByPost,
            "post_promotion_total_impression" => $adsimpressionCount,
            "total_share" => $totalPostShare,
        );
        $response['message'] = 'ok';
        $response['insight'] = $data;
        return $response;
    }

    public function actionPromotionAdSearch()
    {

        $model = new PostSearch();
        $modelAdPromotion = new PostPromotion();

        //$modelSearchLog = new SearchLog();


        $results = $model->promotionAdSearch(Yii::$app->request->queryParams);
        $protionIds = [];
        foreach ($results as $result) {
            $protionIds[] = @$result->adPromotion[0]->id;
        }


        $modelAdPromotion->updateReachedCounter($protionIds);


        $response['message'] = 'Posts found successfully';
        $response['post'] = $results;

        return $response;

    }

    /**
     * search post with promotion
     */

    public function actionPostPromotionAd()
    {

        $model = new PostSearch();
        $result = $model->postPromotionAd(Yii::$app->request->queryParams);
        //  print_r( $result);
        //  exit;
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['postPromotionList'] = $result;
        return $response;

    }

    public function actionTrendingHashtag()
    {
        $model = new HashTag();
        $startDate = strtotime('-1 month'); // One month ago from the current date     
        $endDate = time();
        $query = $model->find()
            ->select(['hash_tag.hashtag', 'COUNT(DISTINCT CONCAT(hash_tag.post_id, hash_tag.hashtag)) AS count'])
            // ->select(['hash_tag.hashtag', 'COUNT(*) AS count'])
            ->innerJoinWith('post')
            ->where(['between', 'post.created_at', $startDate, $endDate])
            ->andWhere(['<>', 'hash_tag.hashtag', ''])
            ->groupBy('hash_tag.hashtag')
            ->orderBy(['count' => SORT_DESC])
            ->limit(20)
            ->asArray();
        $result = $query->all();
        // if need to remove  "post": [] then we can use this code
        // $results = [];
        // foreach ($result as $hashtag) {
        //     $results[] = [
        //         'hashtag' => $hashtag['hashtag'],
        //         'count' => $hashtag['count'],
        //     ];
        // }

        // Output the results
        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['trending_hashtag'] = $result;
        return $response;
    }

    public function actionPostVideoList()
    {
        $model = new PostGallary();
        $query = $model->find()
            ->where(['status'=>PostGallary::STATUS_ACTIVE,'media_type'=>PostGallary::MEDIA_TYPE_VIDEO,'type'=>1]);
           
        $query->orderBy(new Expression('rand()'));
        //$query->groupBy('hashtag');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['results'] = $dataProvider;
        return $response;

    }


    public function actionPostLikeUserList()
    {
        
        
        $postId = (int) Yii::$app->request->queryParams['post_id'];
        $model = new PostLike();

        $query = $model->find()
        ->joinWith(['user' => function ($query) {
            $query->select(['name', 'username', 'email', 'image', 'id', 'is_verified','is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
        }])
        ->where(['post_id'=>$postId]);
           
        $query->orderBy(['id'=>SORT_DESC]);
        //$query->groupBy('hashtag');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        $response['message'] = Yii::$app->params['apiMessage']['common']['listFound'];
        $response['results'] = $dataProvider;
        return $response;

    }

    public function actionMyPostPromotionAd()
    {

        $model = new PostSearch();
        $result = $model->MyPostPromotionAd(Yii::$app->request->queryParams);
        $response['message'] = Yii::$app->params['apiMessage']['post']['listFound'];
        $response['myPostPromotionList'] = $result;
        return $response;

    }

 


    protected function findModel($id)
    {
        if (($model = Post::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}