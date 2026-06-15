<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\LiveTvCategory;
use common\models\User;
use common\models\PollQuestionOption;
/**
 * This is the model class 
 *
 */
class PollQuestionAnswer extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    
    // public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'poll_question_answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','poll_id','poll_question_id','question_option_id','created_at','status'], 'required'],
            
            [['id','user_id','poll_id','poll_question_id','question_option_id','created_at'], 'integer'],
            // [['title'], 'string'],
            
            // [['poll_question_id','poll_id','question_option_id'], 'required','on'=>['create','update']],
            
            // [['category_id','image','priority'], 'safe'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User',
            'poll_id' => Yii::t('app', 'Poll'),
            // 'poll_question_id' => Yii::t('app', 'Poll Question'),  
            'question_option_id' => Yii::t('app', 'Poll Question Option'),
            'poll_question_id' => Yii::t('app', 'Poll Question'),
            'created_at' => Yii::t('app', 'Date'),
            'status' =>  Yii::t('app', 'Status'),                 
            
        ];
    }

    public function getPaidDropDownData()
    {
        return array(self::COMMON_NO => 'No', self::COMMON_YES => 'Yes');
    }


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_INACTIVE => 'Inactive');
    }

    public function getStatus()
    {
       if($this->status==$this::STATUS_INACTIVE){
           return 'Inactive';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }
    }

    
    public function getPoll()
    {
        return $this->hasOne(Poll::className(), ['id' => 'poll_id']);

    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::className(), ['id' => 'campaigner_id']);

    }

    public function getOptionName()
    {
        return $this->hasOne(PollQuestionOption::className(), ['id' => 'question_option_id']);

    }

    public function getQuestionName()
    {
        return $this->hasOne(PollQuestion::className(), ['id' => 'poll_question_id']);

    }

    public function getUserName()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }

}
