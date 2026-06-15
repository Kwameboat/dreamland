<?php
namespace api\modules\v1\models;

// use JetBrains\PhpStorm\Language;
use api\modules\v1\models\Language;
use Yii;
use api\modules\v1\models\User;

class DatingDailyProfileView extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    // public $counter;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dating_daily_profile_view';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['user_id'], 'required'],
            [['id','view_profile_user_id','created_at','user_id'], 'integer'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'view_profile_user_id' => Yii::t('app','Profile ID'),
            'created_at' => Yii::t('app','Date'),
            'user_id' => Yii::t('app','User Id')
            
        ];
    }

    public function fields()
    {
        // $fields = parent::fields();

        // $fields['name'] = (function($model){
        //     return $model->languageName->name;
        // });
        // return $fields;
    }

    public function getPreferenceMatchProfilesIdsAvailable($user_id , $date )
    {

       $query= $this->find() 
        ->select(['id','user_id','view_profile_user_id','created_at'])
        // ->joinWith(['preferenceInterest'=> function ($query) {
        // $query->select(['user_preference_interest.user_id','user_preference_interest.interest_id']);
        // }])
        // ->joinWith(['preferenceLanguage'=> function ($query) {
        //     $query->select(['id','user_id','language_id']);
        // }])
         ->where(['user_id' => $user_id ,'created_at' => $date])->all();

        $profileIds = array();
         foreach($query as $data){
          $profileIds[]=  $data['view_profile_user_id'];
         }
         if(!empty($profileIds)){
         $dataQuery = User::find()    
        ->select(['user.id','user.name','user.username','user.email','user.bio','user.description','user.image','user.is_verified','user.country_code','user.phone','user.country','user.city','user.sex','TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age','user.dob', 'user.paypal_id','user.available_balance','user.available_coin','user.is_biometric_login','user.is_push_notification_allow','user.like_push_notification_status','user.comment_push_notification_status','user.is_chat_user_online','user.chat_last_time_online','user.account_created_with','user.location','user.latitude','user.longitude','user.height','user.color','user.religion','user.marital_status','user.smoke_id','user.drinking_habit','user.qualification','user.occupation','user.state_id','user.city_id','user.work_experience_month','user.work_experience_year','user.profile_category_type']);
        $dataQuery->joinWith(['profileInterest'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        
        $dataQuery->joinWith(['profileLanguage'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        $dataQuery->joinWith(['profileSkip'=> function ($query) {
            // $query->select(['user_interest.user_id','user_interest.interest_id']);
        }]);
        $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
       
         if(!empty($user_id)){
         $dataQuery->andWhere(['!=','user.id' , $user_id]);
         }
        
        if(!empty($profileIds)){
            $dataQuery->andWhere(['IN','user.id' , $profileIds]);
        }
        
        $dataQuery ->andWhere(['dating_profile_view_action.type'=> NULL]);
        
        
         return $dataQuery->all();
        }else{
            return;
           
        }
        
    }

    public function getDailyProfilesAvailable($user_id , $date )
    {

      return $query= $this->find() 
        ->select(['id','user_id','view_profile_user_id','created_at'])
         ->where(['user_id' => $user_id ,'created_at' => $date])->count();
        
    }
    

}
