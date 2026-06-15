<?php
namespace api\modules\v1\models;

// use JetBrains\PhpStorm\Language;
use api\modules\v1\models\Language;
use Yii;

class UserLanguage extends \yii\db\ActiveRecord
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
        return 'user_language';
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


    

    public function updateUserLanguage($userId,$language){
        //print_r($locations);


        $languageData = explode(',',$language);
        $values=[];
        
        foreach($languageData as $data){
          
          
            $userValue['user_id']           =   $userId;
            $userValue['language_id']       =   $data;
            $userValue['status']            =   SELF::STATUS_ACTIVE;
            $userValue['created_at']        = strtotime('now');
            $values[]=$userValue;

        }   

        if(count($values)>0){

            Yii::$app
            ->db
            ->createCommand()
            ->delete('user_language', ['user_id' => $userId])
            ->execute();

            Yii::$app->db
            ->createCommand()
            ->batchInsert('user_language', ['user_id','language_id','status','created_at'],$values)
            ->execute();
        }
    }


    public function getLanguageName()
    {
        return $this->hasOne(Language::className(), ['id'=>'language_id'])->select('id,name');
    }


    

}
