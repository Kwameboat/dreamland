<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Interest;

class UserPreferenceInterest extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    public $counter;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_preference_interest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['interest_id','user_id'], 'required'],
            [['interest_id','user_id'], 'integer'],
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'interest_id' => Yii::t('app','Interest'),
            // 'status' => Yii::t('app','Status'),
            'user_id' => Yii::t('app','User')
            
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
  
        $fields['name'] = (function($model){
            return $model->interestDetailName->name;
        });
        return $fields;
    }

    public function extraFields()
    {
        return ['interestDetailName'];
    }
    

    public function updateUserPreferenceInterest($userId,$interest){

        $interestData = explode(',',$interest);
        $values=[];
        
        foreach($interestData as $data){
                   
            $userValue['user_id']           =   $userId;
            $userValue['interest_id']       =   $data;
            $userValue['status']       =   Self::STATUS_ACTIVE;
            // $userValue['created_at']        = strtotime('now');
            $values[]=$userValue;

        }   

        if(count($values)>0){

            Yii::$app
            ->db
            ->createCommand()
            ->delete('user_preference_interest', ['user_id' => $userId])
            ->execute();

            Yii::$app->db
            ->createCommand()
            ->batchInsert('user_preference_interest', ['user_id','interest_id','status'],$values)
            ->execute();
        }
    }

    // when interest value is empty then delete old data
    public function deleteUserPreferenceInterest($userId){

        $result = $this->find()->where(['user_id'=>$userId])->all();
        if(count($result)>0){

            Yii::$app
            ->db
            ->createCommand()
            ->delete('user_preference_interest', ['user_id' => $userId])
            ->execute();
        }
    }
    
    
    public function getInterestDetailName()
    {
        return $this->hasOne(Interest::className(), ['id'=>'interest_id'])->select('id,name');
    }

    // public function getPreferenceInterest()
    // {
    //     return $this->hasOne(UserPreferenceInterest::className(), ['user_id'=>'id']);
    // }

    public function getPreferenceInterest()
    {
        return $this->hasOne(UserPreferenceInterest::className(), ['user_preference_interest.user_id'=>'user_preference.id']);
        
    }
}


