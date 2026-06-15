<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;

class DatingProfileViewAction extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dating_profile_view_action';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','profile_user_id','type','created_by','created_at'], 'integer'],
            [['profile_user_id'], 'required', 'on'=>'create']
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            // 'action_user_by' => Yii::t('app', 'Like by User'),
            // 'action_from_user_id' => Yii::t('app', 'User Profile Id'),
            'profile_user_id' => Yii::t('app', 'User Profile Id'),
            'type' => Yii::t('app', 'Like or Skip'),
            'created_by' => Yii::t('app', 'Current User'),
            'created_at'=> Yii::t('app', 'created At'),
            
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->user_id =   Yii::$app->user->identity->id;
            $this->created_by =   Yii::$app->user->identity->id;
        }
    
        return parent::beforeSave($insert);
    }


    public function updateMatchProfile($profile_user_id,$userId,$type){
        // echo $userId;
        // exit;
        $result = $this->findOne($profile_user_id);
        $modelPostLike = new DatingProfileViewAction();
        $modelDatingMatchProfile = new DatingMatchProfile();
        $totalResult = $modelPostLike->find()->where(['user_id'=>$userId,'profile_user_id'=>$profile_user_id,'type'=>1])->orWhere(
            ['user_id'=>$profile_user_id,'profile_user_id'=>$userId,'type'=>1]
        )->all();
        $totalCount = count($totalResult);
        if($totalCount >1){
            $modelDatingMatchProfile->updateDatingMatchProfileData($totalResult);
            return  $totalCount;
        }else{
            return $totalCount;
        }
              
    } 

    public function getLikeProfilesByUser($id , $limit )
    {

       $query= $this->find() 
        ->select(['id', 'user_id', 'profile_user_id', 'type', 'created_by', 'created_at'])

        // ->joinWith(['profileViewAction'=> function ($query) {
        //     // $query->select(['user_ids']);
        // }])
         ->where(['user_id' => $id, 'type'=>1])->all();
         $userIds = array();
        foreach($query as $matchData){
             $userIds[]= @$matchData->profile_user_id;
         }
    //    print_r($userIds);
    //    exit;
        if(count($query)>0){
        $dataQuery = User::find()    
        ->select(['user.id','user.name','user.username','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age','user.dob', 'user.paypal_id','user.available_balance','user.available_coin','user.is_biometric_login','user.is_push_notification_allow','user.like_push_notification_status','user.comment_push_notification_status','user.is_chat_user_online','user.chat_last_time_online','user.account_created_with','user.location','user.latitude','user.longitude','user.height','user.color','user.religion','user.marital_status','user.smoke_id','user.drinking_habit','user.qualification','user.occupation','user.state_id','user.city_id','user.work_experience_month','user.work_experience_year','user.profile_category_type']);
        $dataQuery->joinWith(['profileInterest'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        
        $dataQuery->joinWith(['profileLanguage'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
        if(!empty($id)){
            $dataQuery->andWhere(['!=','user.id' , $id]);
            }
        if(!empty($userIds)){
        $dataQuery->andWhere(['IN','user.id' , $userIds]);
        }
        $dataQuery->orderBy('id')
              ->limit($limit);
         return $dataQuery->all();
        }
    }

    public function getRemoveProfileFromViewAction($userId , $profile_user_id){
        $results = $this->find()->where(['user_id'=>$userId,'profile_user_id'=>$profile_user_id,'type'=>1])->orWhere(
            ['user_id'=>$profile_user_id,'profile_user_id'=>$userId,'type'=>1]
        )->all();
        if(!empty($results)){
        $matchIds= array();
        foreach($results as $data){
           $matchIds[]= $data['id'];
        }
        // get both users data available in dating match profile db 
        if(!empty($matchIds)){
          $getMatchProfile =   DatingMatchProfile::find()
            ->andwhere(['IN','profile_view_action_id',$matchIds])
            ->all();
             // delete both users data from dating match profile db
        if(!empty($getMatchProfile)){
            $deleteMatchBothProfile = DatingMatchProfile::deleteAll(['and',['IN','profile_view_action_id',$matchIds]]);
        
        }

        }
        // delete data from dating profile view action profile to user like profile
        $removeProfileData =  $this->find()
        ->where(['user_id'=>$userId])
        ->andwhere(['profile_user_id'=>$profile_user_id])
        ->andwhere(['type'=>1])
        ->one()
        ->delete();
    }
                     
    }

    public function getLikeCurrentProfileByOtherUsers($id , $limit )
    {

       $query= $this->find() 
        ->select(['id', 'user_id', 'profile_user_id', 'type', 'created_by', 'created_at'])

        // ->joinWith(['profileViewAction'=> function ($query) {
        //     // $query->select(['user_ids']);
        // }])
         ->where(['profile_user_id' => $id, 'type'=>1])->all();
         $userIds = array();
        foreach($query as $matchData){
             $userIds[]= @$matchData->user_id;
         }
    //    print_r($userIds);
    //    exit;
        if(count($query)>0){
        $dataQuery = User::find()    
        ->select(['user.id','user.name','user.username','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age','user.dob', 'user.paypal_id','user.available_balance','user.available_coin','user.is_biometric_login','user.is_push_notification_allow','user.like_push_notification_status','user.comment_push_notification_status','user.is_chat_user_online','user.chat_last_time_online','user.account_created_with','user.location','user.latitude','user.longitude','user.height','user.color','user.religion','user.marital_status','user.smoke_id','user.drinking_habit','user.qualification','user.occupation','user.state_id','user.city_id','user.work_experience_month','user.work_experience_year','user.profile_category_type']);
        $dataQuery->joinWith(['profileInterest'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        
        $dataQuery->joinWith(['profileLanguage'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
        if(!empty($id)){
            $dataQuery->andWhere(['!=','user.id' , $id]);
            }
        if(!empty($userIds)){
        $dataQuery->andWhere(['IN','user.id' , $userIds]);
        }
        $dataQuery->orderBy('id')
              ->limit($limit);
         return $dataQuery->all();
        }
    }
    
    public function getDailyProfilesView(){
        $todayDate = date('Y-m-d');
        $currentDate =  strtotime($todayDate);
       return $this->find()->where(['user_id' =>@Yii::$app->user->identity->id , 'DATE(created_at)'=>$currentDate ])->count();
    }
    

}
