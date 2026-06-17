<?php

namespace api\modules\v1\models;

use Yii;
use api\modules\v1\models\Post;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use api\modules\v1\models\BlockedUser;
use common\helpers\SqlDialect;
use yii\widgets\ListView;
use api\modules\v1\models\UserInterest;
use api\modules\v1\models\PostPromotion;


class PostSearch extends Post
{
    private static function randomOrderExpression()
    {
        if (Yii::$app->db->driverName === 'pgsql') {
            // PostgreSQL rejects ORDER BY RANDOM() with SELECT DISTINCT.
            return ['post.id' => SORT_DESC];
        }
        return new Expression('rand()');
    }

    public $is_popular_post;
    public $is_following_user_post;
    public $is_my_post;
    public $is_winning_post;
    public $is_recent;
    public $is_reel;
    public $is_ai_feed;
    public $category_id;
    public $minimum_follower_user;
    public $is_favorite;
    public $is_video_post;
    public $promotion_status;
    public $is_near_by;
    public $is_collaborate;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['hashtag', 'title','latitude','longitude'], 'string'],
            [['type','user_id', 'club_id', 'audio_id', 'is_popular_post', 'is_following_user_post', 'is_my_post', 'is_winning_post', 'is_recent', 'is_reel', 'is_ai_feed','minimum_follower_user','is_favorite','is_video_post','promotion_status','is_near_by','event_id','campaign_id','is_collaborate','category_id'], 'integer'],
            //  [['title'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchMyPost($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params, '');
        $query = Post::find()

            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title','post.description', 'post.competition_id', 'post.club_id', 'post.event_id',  'post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address','post.poll_id','post.is_comment_enable','post.display_whose'])
            ->where(['post.user_id' => $userId])
            //->andWhere(['<>', 'post.status', Post::STATUS_DELETED])
            ->andWhere(['post.status'=> Post::STATUS_ACTIVE])
            // ->andWhere(['post.type'=>[Post::TYPE_NORMAL,Post::TYPE_COMPETITION,Post::TYPE_CLUB]])
            ->orderBy(['post.id' => SORT_DESC]);

        /*if ($this->is_reel) {

            $query->andWhere(['post.type' => Post::TYPE_REEL]);
        } else {

            $postArr = [];
            $postArr[] = Post::TYPE_NORMAL;
            $postArr[] = Post::TYPE_COMPETITION;
            $postArr[] = Post::TYPE_CLUB;
            $postArr[] = Post::TYPE_RESHARE_POST;
            $postArr[] = Post::TYPE_EVENT;
            $postArr[] = Post::TYPE_CAMPAIGN;
            

            $query->andWhere(['post.type' => $postArr]);
        }*/

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        //  $this->load($params);

        $this->setAttributes($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'post.type' => $this->type
            //'ad.user_id' => $this->user_id,
            //  'hash_tag.hashtag' => $this->hashtag,


        ]);


        return $dataProvider;
    }

    public function searchMyPostMentionUser($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        // $countryId   =  Yii::$app->user->identity->country_id;
        $this->load($params, '');
        $mentionUserId = $this->user_id;
        $isNearBy =  (int)$this->is_near_by;
        //echo $this->latitude;
        //echo $this->longitude;
        //die;

        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title', 'post.description','post.competition_id', 'post.club_id','post.event_id',  'post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address','post.poll_id','post.is_comment_enable','post.display_whose'])
            //->select(['post.id','post.type','post.user_id','post.title','post.competition_id','post.is_winning','post.image','post.total_view','post.total_like','post.total_comment','post.total_share','post.popular_point','post.status','post.created_at'])
            ->joinWith(['user' => function ($query) {
                $query->select(['name', 'username', 'email', 'image', 'id','role', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
            }])
            ->joinWith('mentionUsers')
            //->where(['<>', 'post.status', Post::STATUS_DELETED])
            ->where(['post.status'=> Post::STATUS_ACTIVE])
            // ->andWhere(['post.type'=>[Post::TYPE_NORMAL,Post::TYPE_COMPETITION,,Post::TYPE_CLUB]])
            ->andWhere(['mention_user.user_id' => $mentionUserId]);

            $query->andWhere(['user.status' => User::STATUS_ACTIVE]);

            


        if($isNearBy){
            $adDisplayRadius=50;
            if($this->latitude && $this->longitude){
                $query->addSelect('(
                        3959 * acos (
                        cos ( radians('.$this->latitude.') )
                        * cos( radians( post.latitude ) )
                        * cos( radians( post.longitude ) - radians('.$this->longitude.') )
                        + sin ( radians('.$this->latitude.') )
                        * sin( radians( post.latitude ) )
                    )
                ) AS distance');
                $query->having(['<', 'distance', $adDisplayRadius ]);
                $query->orderBy(['distance'=>SORT_ASC]);
                
                $query->andwhere(['not', ['post.latitude' => null]]);
                $query->andwhere(['not', ['post.longitude' => null]]);
            }
        }else{
            $query->orderBy('id desc');
        }    
        $query->distinct();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }


        return $dataProvider;
    }



    /**
     * search post
     */
    public function search($params)
    {
        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->identity->id;
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $userId
            ? $modleBlockedUser->getUserIdsWhomeBlockMe($userId)
            : [];
       
        $subQueryCollabFollower = (new Query())
        ->select('user_id')
        ->from('follower')
        ->where(['follower_id' => $userId ?? 0])
        ->andWhere(['NOT',['follower.type'=> Follower::FOLLOW_REQUEST]]);

        //  $conditionTime =time();

        $isFilter = false;
        $this->load($params, '');
        if ($this->user_id || $this->hashtag || $this->is_following_user_post) { /// for whether within country or overall
            $isFilter = true; /// overall
        }
        

        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title','post.description', 'post.competition_id', 'post.club_id','post.event_id','post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.is_paid', 'post.price_credits', 'post.appraisal_status', 'post.category_id', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address', 'post.audio_id', 'post.audio_start_time', 'post.audio_end_time', 'post.is_add_to_post','post.is_share_post','origin_post_id','share_comment','post.poll_id','post.is_comment_enable','post.display_whose'])
            ->joinWith(['user' => function ($query) use ($isFilter) {

                $query->select(['id','role', 'username', 'name', 'email', 'bio', 'description', 'image', 'is_verified', 'country_code', 'phone', 'country', 'city', 'sex', 'dob', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
            }])
            ->joinWith('hashtags')
            ->where(['post.status'=> Post::STATUS_ACTIVE])
            ->andWhere(['post.appraisal_status' => 'active'])
            
            ->andWhere(['NOT', ['post.user_id' => $userIdsBlockedMe]]);
            $query->andWhere(['user.status' => User::STATUS_ACTIVE]);
            
          

            $query->joinWith(['collaborate.collaboratorDetail' => function ($query) use ($isFilter) {

                $query->select(['id','role', 'username', 'name', 'email', 'bio', 'description', 'image', 'is_verified', 'country_code', 'phone', 'country', 'city', 'sex', 'dob', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
              
               
            }]);
            
           
        if($this->user_id){
            $query->leftJoin('pin', 'post.id = pin.reference_id and pin.type=1');
            $query->orderBy([
                new Expression('CASE WHEN pin.reference_id IS NOT NULL THEN 0 ELSE 1 END'), // Pinned posts on top
            ]);
        }
      
        if ($this->is_recent) {
            $query->addOrderBy('id desc');
        } elseif ($this->is_ai_feed) {
            $categoryId = (int) ($this->category_id ?? 0);
            $rankSql = SqlDialect::feedRankingExpression(0.22, 0.48, 0.42, $categoryId > 0 ? $categoryId : null);
            if (Yii::$app->db->driverName === 'pgsql') {
                $rankBody = preg_replace('/\s+DESC$/i', '', $rankSql);
                $query->addSelect(['feed_rank' => new Expression($rankBody)]);
                $query->addOrderBy(['feed_rank' => SORT_DESC]);
            } else {
                $query->addOrderBy(new Expression($rankSql));
            }
        } else {
            if ($this->is_favorite) {
                $query->joinWith('favorite');
                $query->andWhere(['user_favorite.user_id' => $userId]);
            }else{
                $query->addOrderBy(self::randomOrderExpression());
            }
            
        }
       

        /// for only public user or followers
       /*$query->joinWith(['followers' => function ($query) use ($userId) {
            //$query->where(['follower_id'=>$userId]);

        }]);*/

        //$query->joinWith('followers');
        //$this->user_id

        if($this->is_collaborate ){
            $query->andWhere(
                [
                    'or',
                    ['collaborate.collaborator_id' => $userId],
                    ['collaborate.author_id' => $userId],
                ]
            );
            $query->andWhere(['collaborate.status'=>[Collaborate::STATUS_PENDING,Collaborate::STATUS_ACTIVE]]);
        }

        //|| !$this->is_collaborate
        if($userId != $this->user_id   ){
            $query->joinWith('followers');
            $query->andWhere(
                [
                    'or',

                    ['user.profile_visibility' => User::PROFILE_VISIBILITY_PUBLIC],
                    ['follower.follower_id' => $userId],
                    ['post.user_id' => $userId],
                    ['collaborate.collaborator_id' => $userId,'collaborate.status'=>[Collaborate::STATUS_PENDING,Collaborate::STATUS_ACTIVE]], // for collaborator
                    new Expression(' collaborate.status="'.Collaborate::STATUS_ACTIVE.'" and collaborate.collaborator_id IN(' . $subQueryCollabFollower->createCommand()->getRawSql() . ')'), // for user followers
                ]
            );
        }
     

        if ($this->is_popular_post) {
            $popuplarPointCondition = Yii::$app->params['postPopularityPoint']['popuplarPointCondition'];
            $query->andWhere(['>', 'post.popular_point', $popuplarPointCondition]);
        }
        if ($this->is_following_user_post && $userId != $this->user_id ) {

          
            //$query->andWhere(['follower.follower_id'=>$userId]);
            if ($this->is_my_post) {
                $query->andWhere(
                    [
                        'or',

                        ['follower.follower_id' => $userId],
                        ['post.user_id' => $userId]

                    ]
                );
            } else {
                //$query->andWhere(['follower.follower_id' => $userId]);
                $query->andWhere(
                    [
                        'or',
                        ['follower.follower_id' => $userId],
                        new Expression(' collaborate.status="'.Collaborate::STATUS_ACTIVE.'" and collaborate.collaborator_id IN(' . $subQueryCollabFollower->createCommand()->getRawSql() . ')'), // for user followers
                    ]
                );
            }
        } else {
            if ($this->is_my_post) {
                $query->andWhere(['post.user_id' => $userId]);
            }
        }

        if ($this->is_reel) {

            $query->andWhere(['post.type' => Post::TYPE_REEL]);
        }

        if ($this->category_id) {
            $query->andWhere(['post.category_id' => (int) $this->category_id]);
        }
        /*
        } else {

            $postArr = [];
            $postArr[] = Post::TYPE_NORMAL;
            $postArr[] = Post::TYPE_COMPETITION;
            $postArr[] = Post::TYPE_CLUB;
            $postArr[] = Post::TYPE_RESHARE_POST;
            $postArr[] = Post::TYPE_EVENT;
            $postArr[] = Post::TYPE_CAMPAIGN;
            

            $query->andWhere(
                [
                    'or',
                    ['post.type' => $postArr],
                    ['post.type' => Post::TYPE_REEL, 'post.is_add_to_post' => Post::COMMON_YES]

                ]
            );
        }*/

        if ($this->minimum_follower_user > 0) {
            $subquery = Follower::find()
            ->select('user_id')
            ->where(['NOT', ['type' => Follower::FOLLOW_REQUEST]])
            ->groupBy('user_id')
            ->having(['>=', 'COUNT(*)', $this->minimum_follower_user]);

             $query->andWhere(['post.user_id' => $subquery]);
        }

        if ($this->is_video_post) {

            $query->joinWith(['postGallary' => function ($query) use ($isFilter) {
                  $query->andWhere(['post_gallary.media_type' => PostGallary::MEDIA_TYPE_VIDEO]);

            }]);
        }
        $query->distinct();

       
        // grid filtering conditions
       if ($this->is_winning_post) {
            $query->andFilterWhere([
                'post.is_winning' => $this->is_winning_post,
            ]);
        }
        $query->andFilterWhere([
            'post.type' => $this->type,
            'post.user_id' => $this->user_id,
            'post.club_id' => $this->club_id,
            'post.event_id' => $this->event_id,
            'post.campaign_id' => $this->campaign_id,
            'post.audio_id' => $this->audio_id,
            'hash_tag.hashtag' => $this->hashtag
        ]);

        $query->andFilterWhere(
            [
                'or',

                ['hash_tag.hashtag' => $this->title],
                ['like', 'title', $this->title]


            ]
        );

      //  echo $query->createCommand()->getRawSql();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

      
        //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
      





        return $dataProvider;
    }

    public function search_OLD($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        // $countryId   =  Yii::$app->user->identity->country_id;
        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId);
       
        //  $conditionTime =time();

        $isFilter = false;
        $this->load($params, '');
        if ($this->user_id || $this->hashtag || $this->is_following_user_post) { /// for whether within country or overall
            $isFilter = true; /// overall
        }
        

        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title','post.description', 'post.competition_id', 'post.club_id','post.event_id','post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address', 'post.audio_id', 'post.audio_start_time', 'post.audio_end_time', 'post.is_add_to_post','post.is_share_post','origin_post_id','share_comment','post.poll_id','post.is_comment_enable','post.display_whose'])
            ->joinWith(['user' => function ($query) use ($isFilter) {

                $query->select(['id','role', 'username', 'name', 'email', 'bio', 'description', 'image', 'is_verified', 'country_code', 'phone', 'country', 'city', 'sex', 'dob', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
            }])
            ->joinWith('hashtags')
            ->where(['post.status'=> Post::STATUS_ACTIVE])
            
            ->andWhere(['NOT', ['post.user_id' => $userIdsBlockedMe]]);
            $query->andWhere(['user.status' => User::STATUS_ACTIVE]);
           
        //->orderBy(['post.id'=>SORT_DESC]);
        if ($this->is_recent) {
            $query->orderBy('id desc');
        } else {
            if ($this->is_favorite) {
                $query->joinWith('favorite');
                $query->andWhere(['user_favorite.user_id' => $userId]);
            }else{
                $query->orderBy(self::randomOrderExpression());
            }
            
        }

        /// for only public user or followers
       /*$query->joinWith(['followers' => function ($query) use ($userId) {
            //$query->where(['follower_id'=>$userId]);

        }]);*/

        //$query->joinWith('followers');
        //$this->user_id
        if($userId != $this->user_id ){
            $query->joinWith('followers');
            $query->andWhere(
                [
                    'or',

                    ['user.profile_visibility' => User::PROFILE_VISIBILITY_PUBLIC],
                    ['follower.follower_id' => $userId],
                    ['post.user_id' => $userId]

                ]
            );
        }


        if ($this->is_popular_post) {
            $popuplarPointCondition = Yii::$app->params['postPopularityPoint']['popuplarPointCondition'];
            $query->andWhere(['>', 'post.popular_point', $popuplarPointCondition]);
        }
        if ($this->is_following_user_post && $userId != $this->user_id ) {

          
            //$query->andWhere(['follower.follower_id'=>$userId]);
            if ($this->is_my_post) {
                $query->andWhere(
                    [
                        'or',

                        ['follower.follower_id' => $userId],
                        ['post.user_id' => $userId]

                    ]
                );
            } else {
                $query->andWhere(['follower.follower_id' => $userId]);
            }
        } else {
            if ($this->is_my_post) {
                $query->andWhere(['post.user_id' => $userId]);
            }
        }

        /*if ($this->is_reel) {

            $query->andWhere(['post.type' => Post::TYPE_REEL]);
        } else {

            $postArr = [];
            $postArr[] = Post::TYPE_NORMAL;
            $postArr[] = Post::TYPE_COMPETITION;
            $postArr[] = Post::TYPE_CLUB;
            $postArr[] = Post::TYPE_RESHARE_POST;
            $postArr[] = Post::TYPE_EVENT;
            $postArr[] = Post::TYPE_CAMPAIGN;
            

            $query->andWhere(
                [
                    'or',
                    ['post.type' => $postArr],
                    ['post.type' => Post::TYPE_REEL, 'post.is_add_to_post' => Post::COMMON_YES]

                ]
            );
        }*/

        if ($this->minimum_follower_user > 0) {
            $subquery = Follower::find()
            ->select('user_id')
            ->where(['NOT', ['type' => Follower::FOLLOW_REQUEST]])
            ->groupBy('user_id')
            ->having(['>=', 'COUNT(*)', $this->minimum_follower_user]);

             $query->andWhere(['post.user_id' => $subquery]);
        }

        if ($this->is_video_post) {

            $query->joinWith(['postGallary' => function ($query) use ($isFilter) {
                  $query->andWhere(['post_gallary.media_type' => PostGallary::MEDIA_TYPE_VIDEO]);

            }]);
         
            
        }
        
        

        $query->distinct();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        // grid filtering conditions
       if ($this->is_winning_post) {
            $query->andFilterWhere([
                'post.is_winning' => $this->is_winning_post,
            ]);
        }
        $query->andFilterWhere([
            'post.type' => $this->type,
            'post.user_id' => $this->user_id,
            'post.club_id' => $this->club_id,
            'post.event_id' => $this->event_id,
            'post.campaign_id' => $this->campaign_id,
            'post.audio_id' => $this->audio_id,
            'hash_tag.hashtag' => $this->hashtag
        ]);

        $query->andFilterWhere(
            [
                'or',

                ['hash_tag.hashtag' => $this->title],
                ['like', 'title', $this->title]


            ]
        );

      





        return $dataProvider;
    }



    /**
     * search story post
     */

    public function searchStory($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        // $countryId   =  Yii::$app->user->identity->country_id;

        $isFilter = false;
        $this->load($params, '');


        $conditionTime = strtotime('-24 hours', time());


        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title','post.description', 'post.competition_id', 'post.club_id','post.event_id','post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address','post.poll_id','post.is_comment_enable','post.display_whose'])
            ->joinWith(['user' => function ($query) use ($isFilter) {
                $query->select(['name', 'username', 'email', 'image', 'id','role', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
            }])
            ->joinWith('hashtags')
            ->where(['<>', 'post.status', Post::STATUS_DELETED])
            ->andWhere(['<>', 'post.user_id', $userId])
            ->andWhere(['post.type' => Post::TYPE_STORY])
            ->andWhere(['>', 'post.created_at', $conditionTime])
            ->orderBy(['post.id' => SORT_DESC]);
        //->orderBy(new Expression('rand()'));

        $query->joinWith(['followers' => function ($query) use ($userId) {
            //$query->where(['follower_id'=>$userId]);
        }]);
        $query->andWhere(
            [
                'or',

                ['follower.follower_id' => $userId],
                ['post.user_id' => $userId]

            ]
        );

        //  $query->andWhere(['follower.follower_id'=>$userId]);

        $query->distinct();

        return $query->all();


        /*

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>false
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
       
        return $dataProvider;*/
    }


    public function searchMyStory($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        // $countryId   =  Yii::$app->user->identity->country_id;

        $isFilter = false;
        $this->load($params, '');


        $conditionTime = strtotime('-24 hours', time());


        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title', 'post.description','post.competition_id', 'post.club_id','post.event_id','post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address','post.poll_id','post.is_comment_enable','post.display_whose'])
            ->joinWith(['user' => function ($query) use ($isFilter) {
                $query->select(['name', 'username', 'email', 'image', 'id','role', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
            }])
            ->joinWith('hashtags')
            ->where(['<>', 'post.status', Post::STATUS_DELETED])
            ->andWhere(['post.user_id' => $userId])
            ->andWhere(['post.type' => Post::TYPE_STORY])
            ->orderBy(['post.id' => SORT_DESC]);


        $query->distinct();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>  [
                'pageSize' => 20
            ]
        ]);

        //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        return $dataProvider;
    }

    /**
     * search post promotion data
     */

    public function PostPromotionAd($params)
    {
        $userId   =  @Yii::$app->user->identity->id;
        $modelBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modelBlockedUser->getUserIdsWhomeBlockMe($userId);

        $gender = @Yii::$app->user->identity->sex;
        $postPromotion = new PostPromotion();
        $todayDate = strtotime("now");
        $dob = @Yii::$app->user->identity->dob;
        $diff = abs(strtotime($dob) - time());
        $yearsOld = floor($diff / (365 * 60 * 60 * 24));
        $country_id = @Yii::$app->user->identity->country_id;

        $state_id = @Yii::$app->user->identity->state_id;
        $city_id = @Yii::$app->user->identity->city_id;

        $profile_category_type = @Yii::$app->user->identity->profile_category_type;
        $profile_category_type = ($profile_category_type !== '') ? $profile_category_type : null;
        $latitude = @Yii::$app->user->identity->latitude;
        $latitude = ($latitude != '') ? $latitude : 0.00;
        $longitude = @Yii::$app->user->identity->longitude;
        $longitude = ($longitude != '') ? $longitude : 0.00;

        $currentTime = time();
        $getUserInterest = UserInterest::find()->where(['user_id' => $userId, 'status' => UserInterest::STATUS_ACTIVE])->all();
        $userInterest = [];
        if ($getUserInterest) {
            foreach ($getUserInterest as $getInterest) {
                $userInterest[] = $getInterest['interest_id'];
            }
        }

        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title', 'post.description','post.competition_id', 'post.club_id','post.event_id','post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address', 'post.audio_id', 'post.audio_start_time', 'post.audio_end_time', 'post.is_add_to_post','post.poll_id','post.is_comment_enable','post.display_whose'])
            
            ->joinWith('promotionPost');
            // $query->joinWith('promotionPost.promotionPostViewLimit');
            
            // $query->orderBy('post.id desc');
        $query->joinWith('promotionPost.audience');
        $query->joinWith('promotionPost.audience.promotionInterest');
        $query->joinWith('promotionPost.audience.promotionLocation');       
        $query->andWhere(
            ['>', 'post_promotion.expiry', $currentTime],

        );
        $query->andWhere(
            ['!=', 'post.user_id', $userId],

        );
        // $query->andWhere(['>', 'post_promotion.daily_promotion_limit', $postPromotion->getPromotionPostViewLimit()->scalar()]);
        $subquery = (new \yii\db\Query())
        ->select('COUNT(post_promotion_id) AS total')
        ->from('post_view')
        ->leftJoin('post_promotion', 'post_view.post_promotion_id = post_promotion.id')
        ->where(['DATE(FROM_UNIXTIME(post_view.ad_post_impression_created_at))' => new \yii\db\Expression('CURDATE()')]);
       
        $query->andWhere(['>', 'post_promotion.daily_promotion_limit', $subquery]);

        $query->andWhere(

            ['post_promotion.status' => PostPromotion::STATUS_ACTIVE]
        );


        $query->andFilterWhere([
            'or',
            [
                'and',
                ['post_promotion.is_audience_automatic' => 1],
                
            ],
            [
                'and',
                ['post_promotion.is_audience_automatic' => null],
                // Apply audience conditions here
                [
                    'or',
                    ['is', 'audience.gender', null],
                    ['=', 'audience.gender', $gender]

                ],
                [
                    'or',
                    ['is', 'audience.profile_category_type', null],
                    ['=', 'audience.profile_category_type', $profile_category_type]

                ],
                [
                    'or',
                    ['is', 'audience.age_start_range', null],
                    ['<=', 'audience.age_start_range', $yearsOld]
    
                ],
                [
                    'or',
                    ['is', 'audience.age_end_range', null],
                    ['>=', 'audience.age_end_range', $yearsOld]
    
                ],
                
                [
            'or',
            [
                'and',
                ['audience.location_type' => 1],
                [
                    'or',
                    ['promotion_location.location_id' => $country_id, 'promotion_location.type' => 'country'],
                    ['promotion_location.location_id' => $state_id, 'promotion_location.type' => 'state'],
                    ['promotion_location.location_id' => $city_id, 'promotion_location.type' => 'city'],
                ],
            ],
            // searching location in kilometer radius
            [
                'and',
                ['audience.location_type' => 2],
                ['audience.id' => Audience::find()
                    ->select('ids')
                    ->from([
                        'subquery' => Audience::find()
                            ->select([
                                'audience.id AS ids',
                                '6371 * ACOS(COS(RADIANS(:latitude)) * COS(RADIANS(audience.latitude)) * COS(RADIANS(audience.longitude) - RADIANS(:longitude)) + SIN(RADIANS(:latitude)) * SIN(RADIANS(audience.latitude))) AS distance',
                            ])
                            ->where([
                                '<',
                                '6371 * ACOS(COS(RADIANS(:latitude)) * COS(RADIANS(audience.latitude)) * COS(RADIANS(audience.longitude) - RADIANS(:longitude)) + SIN(RADIANS(:latitude)) * SIN(RADIANS(audience.latitude)))',
                                new Expression('audience.radius')
                            ])
                            ->params([':latitude' => $latitude, ':longitude' => $longitude])
                    ])
                    ->andWhere('distance < audience.radius')
                ],
            ],

            ] 
         ]
        ]);    
        if ($userInterest) {   
            $query->andWhere([
                'or',
                ['and', ['post_promotion.audience_id' => null], ['is', 'promotion_interest.interest_id', null]],
                ['and', ['!=', 'post_promotion.audience_id', null], ['IN', 'promotion_interest.interest_id', $userInterest]],
            ]);
        }
        
        $query->distinct('post.id');


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        if (!$this->validate()) {
            
            return $dataProvider;
        }

        return $dataProvider;
    }

   /**
    * search Current Login User post promotion data
    */
    public function MyPostPromotionAd($params)
    {
        $userId   =  @Yii::$app->user->identity->id;
        $this->load($params, '');
        $modelBlockedUser = new BlockedUser();
      //  $userIdsBlockedMe = $modelBlockedUser->getUserIdsWhomeBlockMe($userId);

        

        $currentTime = time();


        $query = Post::find()
            ->select(['post.id', 'post.type', 'post.post_content_type','post.content_type_reference_id','post.unique_id', 'post.user_id', 'post.title', 'post.description','post.competition_id', 'post.club_id','post.event_id','post.campaign_id', 'post.image', 'post.total_view', 'post.total_like', 'post.total_comment', 'post.total_share', 'post.popular_point', 'post.status', 'post.created_at', 'post.latitude', 'post.longitude', 'post.address', 'post.audio_id', 'post.audio_start_time', 'post.audio_end_time', 'post.is_add_to_post','post.poll_id','post.is_comment_enable','post.display_whose'])
            
            ->joinWith('promotionPost');
            // $query->joinWith('promotionPost.promotionPostViewLimit');
            
        
        if(@$this->promotion_status ==1) {
            $query->andWhere(
                ['>', 'post_promotion.expiry', $currentTime]
             );
        } elseif(@$this->promotion_status ==2) {
               $query->andWhere(
                ['<', 'post_promotion.expiry', $currentTime]
            );
        } else{
            $query->andWhere(
              ['>', 'post_promotion.expiry', $currentTime]
            );
        }

        $query->andWhere(
            ['=', 'post.user_id', $userId],

        );
        $query->andWhere(
            ['=', 'post_promotion.created_by', $userId],

        );
        
        $query->distinct('post.id');

        $query->orderBy(['post_promotion.id' => SORT_DESC]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        if (!$this->validate()) {
            
            return $dataProvider;
        }

        return $dataProvider;
    }

}
