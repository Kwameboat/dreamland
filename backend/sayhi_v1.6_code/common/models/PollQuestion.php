<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\LiveTvCategory;


/**
 * This is the model class 
 *
 */
class PollQuestion extends \yii\db\ActiveRecord
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
        return 'poll_question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title','poll_id','status'], 'required'],
            
            [['poll_id'], 'integer'],
            [['title'], 'string'],
            
            [['title','poll_id'], 'required','on'=>['create','update']],
            
            // [['category_id','image','priority'], 'safe'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'poll_id' => Yii::t('app', 'Poll'),
            'title' => Yii::t('app', 'Title'),  
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

    public function getTotalVoteCount(){
        return $this->hasMany(PollQuestionAnswer::className(), ['poll_question_id' => 'id'])->andOnCondition(['poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE])->count();
    }

    public function getOptionAnswerDetail()
    {
        return $this->hasMany(PollQuestionAnswer::className(), ['poll_question_id' => 'id']);

    }

    public function getOptionDetail()
    {
        return $this->hasMany(PollQuestionOption::className(), ['question_id' => 'id']);

    }


}
