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
class PollQuestionOption extends \yii\db\ActiveRecord
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
        return 'poll_qustion_options';
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

    
    public function getQuestion()
    {
        return $this->hasOne(PollQuestion::className(), ['id' => 'question_id']);

    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::className(), ['id' => 'campaigner_id']);

    }

    public function getTotalOptionVoteCount(){
        return $this->hasMany(PollQuestionAnswer::className(), ['question_option_id' => 'id'])->andOnCondition(['poll_id'=>@$this->poll_id,'poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE])->count();
    }

    public function insertPollOptions($inputPollOptions){
 
        $pollId              =   $inputPollOptions['pollId'];
        $pollOptionArr       =   $inputPollOptions['poll_options'];   
        $values=[];

        

        if(count($pollOptionArr)>1){
            array_pop($pollOptionArr);
        }
        
        foreach($pollOptionArr as $pollOption){
              $dataInner['competition_id']             = $pollId;
              $dataInner['title']                     =  $pollOption;
              $dataInner['status']                     =  PollQuestionOption::STATUS_ACTIVE;
          

              $values[]=$dataInner;
  
          }   
  
          if(count($values)>0){ 
              Yii::$app->db
              ->createCommand()
              ->batchInsert('poll_qustion_options', ['poll_id','title','status'],$values)
              ->execute();
          }


    }

}
