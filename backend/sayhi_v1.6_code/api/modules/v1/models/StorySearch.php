<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Story;
use api\modules\v1\models\BlockedUser;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class StorySearch extends Post
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           // [['hashtag','title'], 'string'],
          //  [['user_id','is_popular_post','is_following_user_post','is_my_post','is_winning_post','is_recent'], 'integer'],
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
     * search story post
     */

    public function searchStory($params)
    {
        $userId   =  Yii::$app->user->identity->id;
       // $countryId   =  Yii::$app->user->identity->country_id;
        
        $isFilter=false;
        $this->load($params,'');

        $modleBlockedUser = new BlockedUser();
        $userIdsBlockedMe = $modleBlockedUser->getUserIdsWhomeBlockMe($userId);
        //print_r($blockedUserIds);

        $conditionTime = strtotime('-24 hours', time());

        
        $query = Story::find()
        //->select(['post.id','post.type','post.user_id','post.title','post.competition_id','post.is_winning','post.image','post.total_view','post.total_like','post.total_comment','post.total_share','post.popular_point','post.status','post.created_at'])
        ->joinWith(['user' => function($query) use ($isFilter){
            $query->select(['name','username','email','image','id','status']);
        }])
        ->where(['story.status'=>Story::STATUS_ACTIVE])
        ->andwhere(['user.status'=>User::STATUS_ACTIVE])
        ->andWhere(['<>','story.user_id',$userId])
        ->andWhere(['>','story.created_at',$conditionTime])
        ->andWhere(['NOT',['story.user_id'=>$userIdsBlockedMe]])
        ->orderBy(['story.id'=>SORT_DESC]);
        //->orderBy(new Expression('rand()'));

        $query->joinWith(['followers' => function($query) use ($userId){
            //$query->where(['follower_id'=>$userId]);
        }]);
        $query->andWhere(
            ['or',
                
                ['follower.follower_id'=>$userId],
                ['story.user_id'=>$userId]
                
            ]);
    
      

        $query->distinct();

        return $query->all();


    }


    public function searchMyStory($params)
    {
        $userId   =  Yii::$app->user->identity->id;
       // $countryId   =  Yii::$app->user->identity->country_id;
        
        $isFilter=false;
        $this->load($params,'');

        
        $conditionTime = strtotime('-24 hours', time());

        
        $query = Story::find()
        //->select(['post.id','post.type','post.user_id','post.title','post.competition_id','post.is_winning','post.image','post.total_view','post.total_like','post.total_comment','post.total_share','post.popular_point','post.status','post.created_at'])
        ->joinWith(['user' => function($query) use ($isFilter){
            $query->select(['name','username','email','image','id']);
        }])
        ->where(['<>','story.status',Story::STATUS_DELETED])
        ->andWhere(['story.user_id'=>$userId])
        ->orderBy(['story.id'=>SORT_DESC]);
        

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

    
    public function searchMyActiveStory($params)
    {
        $userId   =  Yii::$app->user->identity->id;
       // $countryId   =  Yii::$app->user->identity->country_id;
        
        $isFilter=false;
        $this->load($params,'');

        
        $conditionTime = strtotime('-24 hours', time());

        
        $query = Story::find()
        //->select(['post.id','post.type','post.user_id','post.title','post.competition_id','post.is_winning','post.image','post.total_view','post.total_like','post.total_comment','post.total_share','post.popular_point','post.status','post.created_at'])
        ->joinWith(['user' => function($query) use ($isFilter){
            $query->select(['name','username','email','image','id']);
        }])
        ->where(['<>','story.status',Story::STATUS_DELETED])
        ->andWhere(['story.user_id'=>$userId])
        ->andWhere(['>','story.created_at',$conditionTime])
        ->orderBy(['story.id'=>SORT_DESC]);
        

        $query->distinct();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>  false
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
       
        return $dataProvider;
    }
    
    
}
