<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\User;
use api\modules\v1\models\ClubUser;
use api\modules\v1\models\ClubInvitationRequest;




class Club extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_DELETED = 0;


    const PRIVACY_TYPE_PUBLIC=1;
    const PRIVACY_TYPE_PRIVATE=2;
    
    const COMMON_YES = 1;
    const COMMON_NO = 0;

    const POST_LIKE = 1;
    const POST_COMMENT = 2;
    const CLUB_JOIN = 5;
    
    const TYPE_TOP_CLUB =2;
    const TYPE_TRENDING_CLUB =1;
    const IS_TRENDING_MIN_VALUE = 5;
    const IS_TRENDING_CLUB_LIMIT = 10;
    const IS_TOP_CLUB_LIMIT = 1000;

    public $club_user_id;
    public $type;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'club';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        
        return [
            [['id','user_id','category_id','privacy_type','is_request_based','status','is_chat_room','chat_room_id','created_at','created_by','updated_at','updated_by'], 'integer'],
            [['name','description','image'], 'string'],
            [[ 'name','privacy_type' ], 'required','on'=>['create','update']],
            [['name'], 'checkUniqueName','on'=>['create','update']],
            [[ 'id' ], 'required','on'=>['join']],
            [[ 'id','club_user_id' ], 'required','on'=>['remove']],
            [['club_user_id'], 'safe'],
            
           
    
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
           
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by   =   Yii::$app->user->identity->id;
            
        }

        
        return parent::beforeSave($insert);
    }

    public function extraFields()
    {
        return ['totalJoinedUser','createdByUser','clubPost','postLikeDetails','clubJoin'];
    }

    
    public function fields()
    {
        $fields = parent::fields();
        $fields[] = 'imageUrl';
       // $fields[] = 'competitionImage';
       $fields['is_joined'] = (function($model){
         return (@$model->isJoined) ? 1: 0;
       });
       $fields['is_join_requested'] = (function($model){
        return (@$model->isJoinRequested) ? 1: 0;
      });
      $fields['is_trending'] = (function($model){
        return ((@$model->clubTotalPoint) > Club::IS_TRENDING_MIN_VALUE) ? 1: 0 ;
      });
        return $fields;
    }

    
     /**START valication function custom  */
     public function checkUniqueName($attribute, $params, $validator)
     {
        
         if(!$this->hasErrors()){
             if($this->isNewRecord){
                 $count= Club::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','status',self::STATUS_DELETED])->count();
             }else{
                
                 $count= Club::find()->where([$attribute=>$this->$attribute])->andWhere(['<>','id',$this->id])->andWhere(['<>','status',self::STATUS_DELETED])->count();
             }
             
             if($count){
                 $this->addError($attribute, 'Club name already exist');     
             }
             
         }
        
     }


    public function getClubUser()
    {
       return $this->hasMany(ClubUser::className(), ['club_id'=>'id'])->andOnCondition(['club_user.status'=>ClubUser::STATUS_ACTIVE])->orderBy(["club_user.is_admin" => SORT_DESC]);
        
    }

    public function getCreatedByUser()
    {
       return  $this->hasOne(User::className(), ['id'=>'user_id']);
       
        
    }
    

    public function getImageUrl()
    {
        if($this->image){
            
            return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CHAT,$this->image);

            //return Yii::$app->params['pathUploadChat'] . "/" . $this->image;
        }else{
            return '';
        }
        
    }
    public function getIsJoined()
    {
        return $this->hasOne(ClubUser::className(), ['club_id' => 'id'])->andOnCondition(['club_user.status'=>ClubUser::STATUS_ACTIVE,'club_user.user_id' => @Yii::$app->user->identity->id]);
        
        
    }

    public function getIsJoinRequested()
    {
        return $this->hasOne(ClubInvitationRequest::className(), ['club_id' => 'id'])->andOnCondition(['club_invitation_request.type'=>ClubInvitationRequest::TYPE_REQUEST,'club_invitation_request.status'=>ClubInvitationRequest::STATUS_PENDING,'club_invitation_request.user_id' => @Yii::$app->user->identity->id]);
        
        
    }
    public function getTotalJoinedUser()
    {
        return (int)$this->hasMany(ClubUser::className(), ['club_id'=>'id'])->andOnCondition(['club_user.status'=>ClubUser::STATUS_ACTIVE])->count();
        
    }

    public function getClubPost()
    {
       return  $this->hasMany(Post::className(), ['club_id'=>'id'])
       ->andOnCondition(['post.status'=>Post::STATUS_ACTIVE , 'post.type'=>Post::TYPE_CLUB]);         
    }

    public function getClubJoin()
    {
    //    echo  $daysVal = @(int)Yii::$app->request->queryParams['days'];
    //    exit;
       return (int) $this->hasMany(ClubUser::className(), ['club_id'=>'id'])
       ->andOnCondition(['club_user.status'=>ClubUser::STATUS_ACTIVE ])->count();         
    }

    public function getPostLikeDetails()
    {
        // echo $type= $this->id;
        // print_r(@$this->clubPostByClubId['id']);
        $clubPostId=[];
        foreach($this->clubPost as $data){
           $clubPostId[]= $data['id'];
        }
        // exit;
        //  return  $this->hasMany(PostLike::className(), ['post_id'=>'post.id']);
        return PostLike::find()->where(['IN','id',$clubPostId]);
        
    }
    

    Public function getClubTotalPoint($clubId=NULL ,$time=NULL){

        if(empty($clubId)){
            $clubId = $this->id;
        }

        if(empty($time)){
            $type = @(int)Yii::$app->request->queryParams['type'];
            if($type == Club::TYPE_TRENDING_CLUB){
                $days =  date('Y-m-d', strtotime("-7 days"));
                $time = strtotime($days);
            }elseif($type == Club::TYPE_TOP_CLUB){
                $days =  date('Y-m-d', strtotime("-1000 days"));
                $time = strtotime($days);  
            }
        }

        $postResult = Post::find()->where(['club_id'=>$clubId])->all();
        if(count($postResult)>0){
            $postId =[];
            foreach($postResult as $postData){
                $postId[]= $postData['id'];
            }
        }
        $totalLikePoint =0;
        if(!empty($postId)){
            $postLikeResult = PostLike::find()->where(['IN','post_id',$postId])->andWhere(['>','created_at',$time])->all();
            $totalLike = count($postLikeResult);
            $likePoint = Club::POST_LIKE;
            $totalLikePoint = ($totalLike)*($likePoint);
        }
        $totalCommentPoint =0;
        if(!empty($postId)){
            $postLikeResult = PostComment::find()->where(['IN','post_id',$postId])->andWhere(['>','created_at',$time])->all();
            $totalComment = count($postLikeResult);
            $commentPoint = Club::POST_COMMENT;
            $totalCommentPoint = ($totalComment)*($commentPoint);
        }

        // Club Join User 
        $clubUserResult = ClubUser::find()->where(['club_id'=>$clubId])->andWhere(['>','created_at',$time])->all();

        $totalClubUserPoint =0;    
        $totalClubUser = count($clubUserResult);
        $clubUserPoint = Club::CLUB_JOIN;
        $totalClubUserPoint = ($totalClubUser)*($clubUserPoint);
        $totalPoint = $totalLikePoint+$totalCommentPoint+$totalClubUserPoint;
        return (int) $totalPoint;

    }

    

    
    

    

}
