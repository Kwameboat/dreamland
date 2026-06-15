<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\UserInterest;
use api\modules\v1\models\UserLanguage;
use api\modules\v1\models\ProfileCategoryType;
use api\modules\v1\models\Package;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\User;
use api\modules\v1\models\DatingDailyProfileView;
use api\modules\v1\models\DatingDailyProfileViewAction;
/**
 * User model

 */
class UserPreference extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 9;
    const STATUS_DELETED = 0;
    const PROFILE_LIKE = 1;
    const PROFILE_SKIP = 2;

    public $interest;
    public $language;



    /**
     * @inheritdoc
     */
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_preference';
    }

    public function rules()
    {
        return [
            // [['user_id','profile_category_type','country','state','city','religion','marital_status','language','smoke_id','drinking_habit','work_experience','interest'], 'required'],

            [['id', 'user_id', 'profile_category_type', 'country', 'state', 'city', 'marital_status', 'smoke_id', 'work_experience_from', 'work_experience_to', 'age_from', 'age_to', 'gander', 'height_from', 'height_to', 'passionate', 'holistic_path_from', 'holistic_path_to'], 'integer'],
            [['interest', 'language'], 'safe'],
            [['religion', 'drinking_habit', 'color'], 'string'],


        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['auth_key'], $fields['password_hash'], $fields['password_reset_token']);
        //    $fields['is_reported'] = (function($model){
        //      return (@$model->isReported) ? 1: 0;
        //    });
        //    $fields[] = 'picture';
        //    $fields[] = 'userStory';

        $fields['profileCategoryName'] = (function ($model) {
            return @$model->profileCategoriesName->name;
        });

        // $fields['profileCategoryName'] = (function($model){
        //     return @$model->profileCategoriesName->name;
        // });

        return $fields;
    }

    public function extraFields()
    {
        return ['preferenceInterest', 'preferenceLanguage', 'profileLike'];
    }

    public function getPreferenceProfile($id)
    {
        return $this->find()
            ->select(['id', 'user_id', 'profile_category_type', 'country', 'state', 'city', 'religion', 'marital_status', 'smoke_id', 'drinking_habit', 'work_experience_from', 'work_experience_to', 'age_from', 'age_to', 'gander', 'color', 'height_from', 'height_to', 'passionate', 'holistic_path_from', 'holistic_path_to'])
            ->where(['user_id' => $id])->one();

    }

    public function getPreferenceMatchProfiles($id, $limit)
    {
       
        $todayDate = date('Y-m-d');
        $currentDate = strtotime($todayDate);
        $dailyViewLimit = 0;
        $viewTodayProfileCount = 0;
        $modelDatingProfileViewAction = new DatingProfileViewAction();
        $viewTodayProfileCount = $modelDatingProfileViewAction->find()->where(['user_id' => $id, 'DATE(created_at)' => $currentDate])->count();
        $dailyViewLimit = $limit - $viewTodayProfileCount;

        $query = $this->find()
            ->select(['user_preference.id', 'user_preference.user_id', 'user_preference.profile_category_type', 'user_preference.country', 'user_preference.state', 'user_preference.city', 'user_preference.religion', 'user_preference.marital_status', 'user_preference.smoke_id', 'user_preference.drinking_habit', 'user_preference.work_experience_from', 'user_preference.work_experience_to', 'user_preference.age_from', 'user_preference.age_to', 'user_preference.gander', 'user_preference.color', 'user_preference.height_from', 'user_preference.height_to', 'user_preference.passionate', 'user_preference.holistic_path_from', 'user_preference.holistic_path_to'])
            ->joinWith([
                'preferenceInterest' => function ($query) {
                    $query->select(['user_preference_interest.user_id', 'user_preference_interest.interest_id']);
                }
            ])
            ->joinWith([
                'preferenceLanguage' => function ($query) {
                    $query->select(['id', 'user_id', 'language_id']);
                }
            ])
            ->where(['user_preference.user_id' => $id])->one();
        $profile_category_type = @$query['profile_category_type'];
        $country = @$query['country'];
        $state = @$query['state'];
        $city = @$query['city'];
        $religion = @$query['religion'];
        $marital_status = @$query['marital_status'];
        $smoke_id = @$query['smoke_id'];
        $drinking_habit = @$query['drinking_habit'];
        $gander = @$query['gander'];
        $age_from = @$query['age_from'];
        $age_to = @$query['age_to'];
        $work_experience_from = @$query['work_experience_from'];
        $work_experience_to = @$query['work_experience_to'];
        $color = @$query['color'];
        $height_from = @$query['height_from'];
        $height_to = @$query['height_to'];
        $passionate = @(int) $query['passionate'];
        $holistic_path_from = @(int) $query['holistic_path_from'];
        $holistic_path_to = @(int) $query['holistic_path_to'];



        $preferenceInterest = @$query['preferenceInterest'];




        $interestId = array();
        if (!empty($preferenceInterest)) {
            foreach ($preferenceInterest as $intrest) {
                $interestId[] = @$intrest->interest_id;
            }
        }
        $language = @$query['preferenceLanguage'];
        $languageId = array();
        if (!empty($language)) {
            foreach ($language as $langData) {
                $languageId[] = @$langData->language_id;
            }
        }

        if (!empty($query)) {
            $dataQuery = User::find()
                ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age', 'user.dob', 'user.paypal_id', 'user.available_balance', 'user.available_coin', 'user.is_biometric_login', 'user.is_push_notification_allow', 'user.like_push_notification_status', 'user.comment_push_notification_status', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.account_created_with', 'user.location', 'user.latitude', 'user.longitude', 'user.height', 'user.color', 'user.religion', 'user.marital_status', 'user.smoke_id', 'user.drinking_habit', 'user.qualification', 'user.occupation', 'user.state_id', 'user.city_id', 'user.work_experience_month', 'user.work_experience_year', 'user.profile_category_type', 'user.profile_visibility','user.passionate', 'user.holistic_path']);
            $dataQuery->joinWith([
                'profileInterest' => function ($query) {
                    // $query->select(['user_interest.user_id','user_interest.interest_id']);
                }
            ]);

            $dataQuery->joinWith([
                'profileLanguage' => function ($query) {
                    // $query->select(['user_interest.user_id','user_interest.interest_id']);
                }
            ]);
            $dataQuery->joinWith([
                'profileSkip' => function ($query) {
                    // $query->select(['user_interest.user_id','user_interest.interest_id']);
                }
            ]);
            $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
            $dataQuery->andWhere(['user.role' => User::ROLE_CUSTOMER]);
            if (!empty($gander)) {
                $dataQuery->andWhere(['user.sex' => $gander]);
            }
            if (!empty($profile_category_type)) {
                $dataQuery->orWhere(
                    // ['user.profile_category_type' => $profile_category_type]
                    [
                        'or',
                        ['is', 'user.profile_category_type', null],
                        ['=', 'user.profile_category_type', $profile_category_type]

                    ]
                );
            }
            if (!empty($country)) {
                $dataQuery->orWhere(
                    // ['country' => $country]
                    [
                        'or',
                        ['is', 'user.country', null],
                        ['=', 'user.country', $country]

                    ]
                );
            }
            if (!empty($state)) {
                $dataQuery->orWhere(
                    // ['state_id' => $state]
                    [
                        'or',
                        ['is', 'user.state_id', null],
                        ['=', 'user.state_id', $state]

                    ]
                );
            }
            if (!empty($city)) {
                $dataQuery->orWhere(
                    // ['city_id' => $city]
                    [
                        'or',
                        ['is', 'user.city_id', null],
                        ['=', 'user.city_id', $city]

                    ]
                );
            }
            if (!empty($religion)) {
                $dataQuery->orWhere(
                    // ['religion' => $religion]
                    [
                        'or',
                        ['is', 'user.religion', null],
                        ['=', 'user.religion', $religion]

                    ]
                );
            }
            if (!empty($marital_status)) {
                $dataQuery->orWhere(
                    // ['marital_status' => $marital_status]
                    [
                        'or',
                        ['is', 'user.marital_status', null],
                        ['=', 'user.marital_status', $marital_status]

                    ]
                );
            }
            if (!empty($smoke_id)) {

                $dataQuery->orWhere(
                    // ['smoke_id' => $smoke_id]
                    [
                        'or',
                        ['is', 'user.smoke_id', null],
                        ['=', 'user.smoke_id', $smoke_id]

                    ]
                );
            }
            if (!empty($drinking_habit)) {
                $dataQuery->orWhere(
                    // ['drinking_habit' => $drinking_habit]
                    [
                        'or',
                        ['is', 'user.drinking_habit', null],
                        ['=', 'user.drinking_habit', $drinking_habit]

                    ]
                );
            }
            if (!empty($work_experience_to)) {
                $dataQuery->andWhere(['>=', 'work_experience_year', $work_experience_from])->
                    andWhere(['<=', 'work_experience_year', $work_experience_to]);
            }
            if (!empty($interestId)) {
                $dataQuery->orWhere(
                    // ['IN','user_interest.interest_id' , $interestId]
                    [
                        'or',
                        ['is', 'user_interest.interest_id', null],
                        ['=', 'user_interest.interest_id', $interestId]

                    ]
                );
            }
            if (!empty($languageId)) {
                $dataQuery->orWhere(
                    // ['IN','user_language.language_id' , $languageId]
                    [
                        'or',
                        ['is', 'user_language.language_id', null],
                        ['IN', 'user_language.language_id', $languageId]

                    ]
                );
            }
            if (!empty($age_from) && !empty($age_to)) {
                $dataQuery->andWhere(
                    ['>=', 'year(CURRENT_TIMESTAMP) - year(dob)', $age_from]
                )->andWhere(
                        ['<=', 'year(dob)-year(CURRENT_TIMESTAMP)', $age_to]
                    );
            }
            if (!empty($color)) {
                $dataQuery->orWhere(
                    // ['color' => $color]
                    [
                        'or',
                        ['is', 'user.color', null],
                        ['=', 'user.color', $color]

                    ]
                );
            }
            // if(!empty($height)){
            //     $dataQuery->orWhere(
            //         // ['height' => $height]
            //         [
            //             'or',
            //             ['is', 'user.height', null],
            //             ['=', 'user.height', $height]

            //         ]
            //     );
            // }
            if (!empty($height_from) && !empty($height_to)) {
                $dataQuery->andWhere(
                    // ['>=', 'height',$height_from])->andWhere(['<=', 'height',$height_to]

                    [
                        'or',
                        ['is', 'user.height', null],
                        [
                            'and',
                            ['>=', 'user.height', $height_from],
                            ['<=', 'user.height', $height_to]
                        ]
                    ]
                );
            }
            
            if ($passionate>0) {
                $dataQuery->andWhere(['user.passionate' => $passionate]);
            }
            if ($holistic_path_from>0) {
                $dataQuery->andWhere(['>=','user.holistic_path', $holistic_path_from]);
            }
            if ($holistic_path_to>0) {
                $dataQuery->andWhere(['<=','user.holistic_path', $holistic_path_to]);
            }
          
            $dataQuery->andWhere(['dating_profile_view_action.type' => NULL]);
          //  $dataQuery->andWhere(['NOT', ['user.sex' => NULL]]);
          //  $dataQuery->andWhere(['NOT', ['user.dob' => NULL]]);
          //  $dataQuery->andWhere(['NOT', ['user.image' => NULL]]);
            if (!empty($id)) {
                $dataQuery->andWhere(['!=', 'user.id', $id]);
            }
            $dataQuery->orderBy('id')
                ->limit($dailyViewLimit);
            return $dataQuery->all();
        } else {
            
            $userDetails = User::find()->andWhere(['id' => $id])->one();
            if (!empty($userDetails)) {

                $height = $userDetails['height'];
                $age = $userDetails['dob'];
                $age_from = 16;
                $age_to = 100;
                $heightMin = 121; // height in cm
                $heightMax = 243; // height in cm
                $dataQuery = User::find()
                    ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age', 'user.dob', 'user.paypal_id', 'user.available_balance', 'user.available_coin', 'user.is_biometric_login', 'user.is_push_notification_allow', 'user.like_push_notification_status', 'user.comment_push_notification_status', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.account_created_with', 'user.location', 'user.latitude', 'user.longitude', 'user.height', 'user.color', 'user.religion', 'user.marital_status', 'user.smoke_id', 'user.drinking_habit', 'user.qualification', 'user.occupation', 'user.state_id', 'user.city_id', 'user.work_experience_month', 'user.work_experience_year', 'user.profile_category_type', 'user.profile_visibility']);
                $dataQuery->joinWith([
                    'profileInterest' => function ($query) {
                        // $query->select(['user_interest.user_id','user_interest.interest_id']);
                    }
                ]);

                $dataQuery->joinWith([
                    'profileLanguage' => function ($query) {
                        // $query->select(['user_interest.user_id','user_interest.interest_id']);
                    }
                ]);
                $dataQuery->joinWith([
                    'profileSkip' => function ($query) {
                        // $query->select(['user_interest.user_id','user_interest.interest_id']);
                    }
                ]);
                $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
                $dataQuery->andWhere(['user.role' => User::ROLE_CUSTOMER]);
                if (!empty($age)) {
                    $dataQuery->andWhere(['>=', 'year(CURRENT_TIMESTAMP) - year(dob)', $age_from])->andWhere(['<=', 'year(dob)-year(CURRENT_TIMESTAMP)', $age_to]);
                }

                if (!empty($height)) {
                    $dataQuery->andWhere(['>=', 'height', $heightMin])->andWhere(['<=', 'height', $heightMax]);
                }
                $dataQuery->andWhere(['dating_profile_view_action.type' => NULL]);
                //$dataQuery->andWhere(['NOT', ['user.sex' => NULL]]);
                //$dataQuery->andWhere(['NOT', ['user.dob' => NULL]]);
                //$dataQuery->andWhere(['NOT', ['user.image' => NULL]]);
                if (!empty($id)) {
                    $dataQuery->andWhere(['!=', 'user.id', $id]);
                }
                $dataQuery->orderBy('id')
                    ->limit($dailyViewLimit);
                return $dataQuery->all();
            }
        }
    }

    public function getPreferenceInterest()
    {
        return $this->hasMany(UserPreferenceInterest::className(), ['user_id' => 'user_id'])->select(['interest_id']);
        // ->joinWith('interestDetailName');
    }

    public function getPreferenceLanguage()
    {
        return $this->hasMany(UserPreferenceLanguage::className(), ['user_id' => 'user_id'])->select(['language_id']);
        // ->joinWith('languageName');
    }

    public function getProfileCategoriesName()
    {
        return $this->hasOne(ProfileCategoryType::className(), ['id' => 'profile_category_type'])->select(['name']);
        // ->joinWith('languageName');
    }

    public function beforeSave($insert)
    {
        if ($insert) {

            $userId = Yii::$app->user->identity->id;
            Yii::$app
                ->db
                ->createCommand()
                ->delete('user_preference', ['user_id' => $userId])
                ->execute();

        } else {
            $userId = Yii::$app->user->identity->id;

        }

        return parent::beforeSave($insert);
    }

    public function getMatchProfilesByUser($id, $limit)
    {

        $query = $this->find()
            ->select(['user_preference.id', 'user_preference.user_id', 'user_preference.profile_category_type', 'user_preference.country', 'user_preference.state', 'user_preference.city', 'user_preference.religion', 'user_preference.marital_status', 'user_preference.smoke_id', 'user_preference.drinking_habit', 'user_preference.work_experience_from', 'user_preference.work_experience_to', 'user_preference.age_from', 'user_preference.age_to', 'user_preference.gander', 'user_preference.color', 'user_preference.height_from', 'user_preference.height_to'])
            ->joinWith([
                'preferenceInterest' => function ($query) {
                    $query->select(['user_preference_interest.user_id', 'user_preference_interest.interest_id']);
                }
            ])
            ->joinWith([
                'preferenceLanguage' => function ($query) {
                    $query->select(['id', 'user_id', 'language_id']);
                }
            ])
            ->where(['user_preference.user_id' => $id])->one();

        $profile_category_type = @$query['profile_category_type'];
        $country = @$query['country'];
        $state = @$query['state'];
        $city = @$query['city'];
        $religion = @$query['religion'];
        $marital_status = @$query['marital_status'];
        $smoke_id = @$query['smoke_id'];
        $drinking_habit = @$query['drinking_habit'];
        $gander = @$query['gander'];
        $age_from = @$query['age_from'];
        $age_to = @$query['age_to'];
        $work_experience_from = @$query['work_experience_from'];
        $work_experience_to = @$query['work_experience_to'];
        $color = @$query['color'];
        $height_from = @$query['height_from'];
        $height_to = @$query['height_to'];
        $preferenceInterest = @$query['preferenceInterest'];
        foreach ($preferenceInterest as $intrest) {
            $interestId[] = $intrest->interest_id;
        }

        $language = $query['preferenceLanguage'];
        foreach ($language as $langData) {
            $languageId[] = $langData->language_id;
        }

        $dataQuery = User::find()
            ->select(['user.id', 'user.name', 'user.username', 'user.email', 'user.bio', 'user.description', 'user.image', 'user.is_verified', 'user.country_code', 'user.phone', 'user.country', 'user.city', 'user.sex', 'TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age', 'user.dob', 'user.paypal_id', 'user.available_balance', 'user.available_coin', 'user.is_biometric_login', 'user.is_push_notification_allow', 'user.like_push_notification_status', 'user.comment_push_notification_status', 'user.is_chat_user_online', 'user.chat_last_time_online', 'user.account_created_with', 'user.location', 'user.latitude', 'user.longitude', 'user.height', 'user.color', 'user.religion', 'user.marital_status', 'user.smoke_id', 'user.drinking_habit', 'user.qualification', 'user.occupation', 'user.state_id', 'user.city_id', 'user.work_experience_month', 'user.work_experience_year', 'user.profile_category_type', 'user.profile_visibility']);
        $dataQuery->joinWith([
            'profileInterest' => function ($query) {
                // $query->select(['user_interest.user_id','user_interest.interest_id']);
            }
        ]);

        $dataQuery->joinWith([
            'profileLanguage' => function ($query) {
                // $query->select(['user_interest.user_id','user_interest.interest_id']);
            }
        ]);
        $dataQuery->joinWith([
            'profileSkip' => function ($query) {
                // $query->select(['user_interest.user_id','user_interest.interest_id']);
            }
        ]);
        $dataQuery->andWhere(['user.status' => User::STATUS_ACTIVE]);
        if (!empty($gander)) {
            $dataQuery->andWhere(['user.sex' => $gander]);
        }
        if (!empty($profile_category_type)) {
            $dataQuery->andWhere(['user.profile_category_type' => $profile_category_type]);
        }
        if (!empty($id)) {
            $dataQuery->andWhere(['!=', 'user.id', $id]);
        }
        if (!empty($country)) {
            $dataQuery->andWhere(['country' => $country]);
        }
        if (!empty($state)) {
            $dataQuery->andWhere(['state_id' => $state]);
        }
        if (!empty($city)) {
            $dataQuery->andWhere(['city_id' => $city]);
        }
        if (!empty($religion)) {
            $dataQuery->andWhere(['religion' => $religion]);
        }
        if (!empty($marital_status)) {
            $dataQuery->andWhere(['marital_status' => $marital_status]);
        }
        if (!empty($smoke_id)) {
            $dataQuery->andWhere(['smoke_id' => $smoke_id]);
        }
        if (!empty($drinking_habit)) {
            $dataQuery->andWhere(['drinking_habit' => $drinking_habit]);
        }
        if (!empty($work_experience_to)) {
            $dataQuery->andWhere(['>=', 'work_experience_year', $work_experience_from])->
                andWhere(['<=', 'work_experience_year', $work_experience_to]);
        }
        if (!empty($interestId)) {
            $dataQuery->andWhere(['IN', 'user_interest.interest_id', $interestId]);
        }
        if (!empty($languageId)) {
            $dataQuery->andWhere(['IN', 'user_language.language_id', $languageId]);
        }
        if (!empty($age_from) && !empty($age_to)) {
            $dataQuery->andWhere(['>=', 'year(CURRENT_TIMESTAMP) - year(dob)', $age_from])->andWhere(['<=', 'year(dob)-year(CURRENT_TIMESTAMP)', $age_to]);
        }
        if (!empty($color)) {
            $dataQuery->andWhere(['color' => $color]);
        }
        if (!empty($height_from) && !empty($height_to)) {
            // $dataQuery->andWhere(['height' => $height]);
            $dataQuery->andWhere(['>=', 'height', $height_from])->andWhere(['<=', 'height', $height_to]);
        }
        $dataQuery->andWhere(['dating_profile_view_action.type' => NULL]);
        // $dataProvider = new ActiveDataProvider([
        //     'query' => $dataQuery]);
        $dataQuery->orderBy('id')
            ->limit($limit);

        return $dataQuery->all();

    }

    public function getPreferenceMatchProfilesIds($result)
    {
        $profileIDs = array();
        foreach ($result as $data) {
            $profileIDs[] = $data->id;
        }
        // testing start here 
        $values = [];
        $todayDate = date('Y-m-d');
        foreach ($profileIDs as $profiledata) {

            $userValue['user_id'] = Yii::$app->user->identity->id;
            $userValue['view_profile_user_id'] = $profiledata;
            $userValue['created_at'] = strtotime($todayDate);
            $values[] = $userValue;

        }

        if (count($values) > 0) {

            Yii::$app->db
                ->createCommand()
                ->batchInsert('dating_daily_profile_view', ['user_id', 'view_profile_user_id', 'created_at'], $values)
                ->execute();
        }
    }

}
