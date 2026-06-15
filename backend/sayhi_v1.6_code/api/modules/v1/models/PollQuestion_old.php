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

class PollQuestion extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE=10;
    const STATUS_INACTIVE=9;
    const STATUS_DELETED=0;

   
    public $imageFile;
    public $transaction_id;
   

    
    
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
            
            [['poll_id','id'], 'integer'],
            [['title'], 'string'],
            
            [['title','poll_id'], 'required','on'=>['create','update']],
            
            
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

        $fields['poll'] = (function($model){
            return @$model->poll->title;
           // return (@$model->isReported) ? 1: 0;
        });
        $fields['total_vote_count'] = (function($model){
            return (int)@$model->totalVoteCount;
        });

        $fields['is_vote'] = (function($model){
            // return @$model->isVote;
            return (@$model->isVote) ? @$model->isVote->question_option_id: 0;
        });

        // $fields['pollQuestionOption'] = (function($model){
        //     return @$model->pollQuestionOption->title;
        //    // return (@$model->isReported) ? 1: 0;
        // });
        // $fields['campaignerName'] = (function($model){
        //     return @$model->campaigner->name;
        //    // return (@$model->isReported) ? 1: 0;
        // });

        return $fields;
    }


    public function extraFields()
    {
        return ['pollList','pollQuestionOption'];
    }
   
 

    public function getCategory(){

        return $this->hasOne(Category::className(), ['id' => 'category_id']);

    }

    public function getCampaigner(){

        return $this->hasOne(Organization::className(), ['id' => 'campaigner_id']);

    }
    
    public function getPollList()
    {
        return $this->hasMany(Poll::className(), ['category_id' => 'id'])->andOnCondition(['poll.status' => Poll::STATUS_ACTIVE])->limit(10);

    }

    public function getPoll()
    {
       return $this->hasMany(Poll::className(), ['id' => 'poll_id'])->andOnCondition(['poll.status' => Poll::STATUS_ACTIVE])->one();
        

    }

    // public function getPollQuestionOption()
    // {
    //     return $this->hasMany(PollQuestionOption::className(), ['question_id' => 'poll_id'])->andOnCondition(['poll_qustion_options.status' => PollQuestionOption::STATUS_ACTIVE])->limit(10);

    // }

    public function getPollQuestionOption(){

        return $this->hasMany(PollQuestionOption::className(), ['question_id' => 'id'])->andOnCondition(['status' => PollQuestionOption::STATUS_ACTIVE])->all();

    }

    public function getTotalVoteCount(){
        return $this->hasMany(PollQuestionAnswer::className(), ['poll_question_id' => 'id'])->andOnCondition(['poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE])->count();
    }

    public function getIsVote()
    {
        
        return $this->hasOne(PollQuestionAnswer::className(), ['poll_question_id' => 'id'])->andOnCondition(['poll_question_answer.user_id' => @Yii::$app->user->identity->id,'poll_question_answer.status' => PollQuestionAnswer::STATUS_ACTIVE]);
        
        
    }
}
