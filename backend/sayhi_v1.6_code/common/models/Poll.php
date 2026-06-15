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
class Poll extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const COMMON_NO=0;
    const COMMON_YES=1;

    const TYPE_POLL =1;
    const TYPE_POST =2;
    const CREATED_BY_POLL_ADMIN =1;
    const CREATED_BY_POLL_USER =2;
    // public $imageFile;

    
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'poll';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'status','category_id','start_time','end_time'], 'required'],
            
            [['status', 'id','category_id','campaigner_id','created_at','created_by','updated_at','updated_by','type','created_by_poll'], 'integer'],
            [['title','description'], 'string'],
            
            [['title','category_id'], 'required','on'=>['create','update']],
            
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
            'category_id' => Yii::t('app', 'Category'),
            'campaigner_id' => Yii::t('app', 'Campaigner'),
            'title' => Yii::t('app', 'Title'),
            'start_time' => Yii::t('app', 'Start Date'),
            'end_time' => Yii::t('app', 'End Date'),
            'description' => Yii::t('app', 'Description'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Create Date'),
            'created_by' => Yii::t('app', 'Create By'),
            'updated_at' => Yii::t('app', 'Update Date'),
            'updated_by' => Yii::t('app', 'Update By'),                    
            'type' => Yii::t('app', 'Type'), 
            'created_by_poll' => Yii::t('app', 'Poll created by'), 
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

    
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::className(), ['id' => 'campaigner_id']);

    }

    public function getTotalVoteCount(){
        return $this->hasMany(PollQuestionAnswer::className(), ['poll_id' => 'id'])->andOnCondition(['poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE])->count();
    }

    public function getOptionDetail()
    {
        return $this->hasMany(PollQuestionOption::className(), ['poll_id' => 'id'])->andOnCondition(['status'=> PollQuestionOption::STATUS_ACTIVE] );

    }

    public function getType()
    {
       if($this->type==$this::TYPE_POST){
           return 'Post';
       }else if($this->type==$this::TYPE_POLL){
           return 'Poll';    
       }
    }

    public function getPollCreatedBy()
    {
       if($this->type==$this::CREATED_BY_POLL_ADMIN){
           return 'Admin';
       }else if($this->type==$this::CREATED_BY_POLL_USER){
           return 'User';    
       }
    }
}
