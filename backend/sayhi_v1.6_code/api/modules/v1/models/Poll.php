<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;
use api\modules\v1\models\LiveTvSubscriber;
use api\modules\v1\models\LiveTvViewer;
use api\modules\v1\models\LiveTvFavorite;
use api\modules\v1\models\LiveTvCategory;
use api\modules\v1\models\TvShow;
use common\models\TvShowEpisode;

class Poll extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

    const TYPE_POLL =1;
    const TYPE_POST =2;
    const CREATED_BY_POLL_ADMIN =1;
    const CREATED_BY_POLL_USER =2;

    public $imageFile;
    public $transaction_id;
    public $options;

    
    
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
            [['title','category_id'], 'required'],
            
            [['status', 'id','category_id','campaigner_id','created_at','created_by','updated_at','updated_by','type','created_by_poll'], 'integer'],

            [['title','description'], 'string'],
            [['title','category_id','type' ], 'required','on'=>'create'],
            [['title','category_id','type' ], 'required','on'=>'update'],
            [['start_time','end_time'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            
        ];
    }
    
    public function fields()
    {
        
        $fields = parent::fields();
        unset($fields['parent_id'],$fields['status'],$fields['priority'],$fields['leval']);
        //$fields[] = 'categoryName';

        $fields['categoryName'] = (function($model){
            return @$model->category->name;
           // return (@$model->isReported) ? 1: 0;
        });
        $fields['campaignerName'] = (function($model){
            return @$model->campaigner->name;
           // return (@$model->isReported) ? 1: 0;
        });
        $fields['total_vote_count'] = (function($model){
            return (int)@$model->totalVoteCount;
        });

        $fields['is_vote'] = (function($model){
            // return @$model->isVote;
            return (@$model->isVote) ? @$model->isVote->question_option_id: 0;
        });
        $fields['pollOptions'] = (function($model){
            // return @$model->isVote;
            return $model->pollOptions;
        });
        
        return $fields;
    }


    public function extraFields()
    {
        return ['pollQuestion','pollOptions'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

    public function getCampaigner(){

        return $this->hasOne(Organization::className(), ['id' => 'campaigner_id']);

    }
    
    public function getPollQuestion()
    {
        return $this->hasMany(PollQuestion::className(), ['poll_id' => 'id'])->andOnCondition(['poll_question.status' => PollQuestion::STATUS_ACTIVE])->limit(10);

    }

    public function getPollQuestions()
    {
        return $this->hasMany(PollQuestion::className(), ['poll_id' => 'id'])->andOnCondition(['poll_question.status' => PollQuestion::STATUS_ACTIVE])->limit(10);

    }

    public function getPollOptions()
    {
        return $this->hasMany(PollQuestionOption::className(), ['poll_id' => 'id'])->andOnCondition(['poll_qustion_options.status' => PollQuestionOption::STATUS_ACTIVE])->limit(10);

    }

    public function getTotalVoteCount(){
        return $this->hasMany(PollQuestionAnswer::className(), ['poll_id' => 'id'])->andOnCondition(['poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE])->count();
    }

    public function getIsVote()
    {
        
        return $this->hasOne(PollQuestionAnswer::className(), ['poll_id' => 'id'])->andOnCondition(['poll_question_answer.user_id' => @Yii::$app->user->identity->id,'poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE]);
        
        
    }


}
