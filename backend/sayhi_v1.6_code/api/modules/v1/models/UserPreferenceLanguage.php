<?php
namespace api\modules\v1\models;
use Yii;

class UserPreferenceLanguage extends \yii\db\ActiveRecord
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_preference_language';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['language_id','status','user_id'], 'required'],
            [['language_id','status','user_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'language_id' => Yii::t('app','Language'),
            'status' => Yii::t('app','Status'),
            'user_id' => Yii::t('app','User')
            
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['name'] = (function($model){
            return @$model->languageName->name;
        });
        return $fields;
    }


    

    public function updateUserPreferenceLanguage($userId,$language){
        //print_r($locations);


        $languageData = explode(',',$language);
        $values=[];
        
        foreach($languageData as $data){
          
          
            $userValue['user_id']           =   $userId;
            $userValue['language_id']       =   $data;
            $userValue['status']            =   self::STATUS_ACTIVE;
            // $userValue['created_at']        = strtotime('now');
            $values[]=$userValue;

        }   

        if(count($values)>0){

            Yii::$app
            ->db
            ->createCommand()
            ->delete('user_preference_language', ['user_id' => $userId])
            ->execute();

            Yii::$app->db
            ->createCommand()
            ->batchInsert('user_preference_language', ['user_id','language_id','status'],$values)
            ->execute();
        }
    }
        // when Language value is empty then delete old data
    public function deleteUserPreferenceLanguage($userId){

        $result = $this->find()->where(['user_id'=>$userId])->all();
        
        if(count($result)>0){

            Yii::$app
            ->db
            ->createCommand()
            ->delete('user_preference_language', ['user_id' => $userId])
            ->execute();

        }
    }

    public function getLanguageName()
    {
        return $this->hasOne(Language::className(), ['id'=>'language_id'])->select('id,name');
    }

    public function getPreferenceLanguage()
    {
        return $this->hasOne(UserPreferenceLanguage::className(), ['user_id'=>'id']);
    }


}
