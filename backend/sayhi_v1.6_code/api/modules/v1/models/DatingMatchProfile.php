<?php
namespace api\modules\v1\models;

use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\Post;

class DatingMatchProfile extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dating_match_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'profile_view_action_id', 'created_at'], 'integer'],
            [['profile_view_action_id'], 'required', 'on' => 'create']


        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User Id'),
            'profile_view_action_id' => Yii::t('app', 'Profile'),
            'created_at' => Yii::t('app', 'created At'),

        ];
    }

    // public function beforeSave($insert)
    // {
    //     if ($insert) {
    //         $this->created_at = time();
    //         // $this->user_id =   Yii::$app->user->identity->id;

    //     }

    //     return parent::beforeSave($insert);
    // }


    public function updateDatingMatchProfileData($result)
    {

        // $interestData = explode(',',$interest);
        $values = [];

        foreach ($result as $data) {
            //   print_r($data);     
            $userValue['user_id'] = $data['user_id'];
            $userValue['profile_view_action_id'] = $data['id']; // this id is getting from dating profile view action id
            $userValue['created_at'] = strtotime('now');
            $values[] = $userValue;

        }

        if (count($values) > 0) {

            // Yii::$app
            // ->db
            // ->createCommand()
            // ->delete('user_preference_interest', ['user_id' => $userId])
            // ->execute();

            Yii::$app->db
                ->createCommand()
                ->batchInsert('dating_match_profile', ['user_id', 'profile_view_action_id', 'created_at'], $values)
                ->execute();
        }
    }


    public function getMatchProfilesByUser($id, $limit)
    {

        $query = $this->find()
            ->select(['dating_match_profile.id', 'dating_match_profile.user_id', 'dating_match_profile.profile_view_action_id', 'dating_match_profile.created_at'])

            ->joinWith([
                'profileViewAction' => function ($query) {
                    // $query->select(['user_ids']);
                }
            ])
            ->where(['dating_match_profile.user_id' => $id]);

           $result =  $query->all();

        $userIds = array();
        foreach ($result as $matchData) {
            $userIds[] = @$matchData['profileViewAction']->profile_user_id;
        }
        if (count($result) > 0) {
            $dataQuery = User::find()
                ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age', 'user.dob', 'user.paypal_id', 'user.available_balance', 'user.available_coin', 'user.is_biometric_login', 'user.is_push_notification_allow', 'user.like_push_notification_status', 'user.comment_push_notification_status', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.account_created_with', 'user.location', 'user.latitude', 'user.longitude', 'user.height', 'user.color', 'user.religion', 'user.marital_status', 'user.smoke_id', 'user.drinking_habit', 'user.qualification', 'user.occupation', 'user.state_id', 'user.city_id', 'user.work_experience_month', 'user.work_experience_year', 'user.profile_category_type']);
            $dataQuery->joinWith([
                'profileInterest' => function ($result) {
                    // $query->select(['user_interest.user_id','user_interest.interest_id']);
                }
            ]);

            $dataQuery->joinWith([
                'profileLanguage' => function ($result) {
                    // $query->select(['user_interest.user_id','user_interest.interest_id']);
                }
            ]);
            $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
            if (!empty($userIds)) {
                $dataQuery->andWhere(['IN', 'user.id', $userIds]);
            }
            $dataQuery->orderBy('id')
                ->limit($limit);
            return $dataQuery;
            // return $dataQuery->all();
        }else{
            return $query;
        }


        


    }

    public function getProfileViewAction()
    {
        return $this->hasOne(DatingProfileViewAction::className(), ['id' => 'profile_view_action_id']);

    }



}