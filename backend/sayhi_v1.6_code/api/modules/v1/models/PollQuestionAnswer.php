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

class PollQuestionAnswer extends \yii\db\ActiveRecord
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
        return 'poll_question_answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['poll_id','question_option_id','status'], 'required'],
            
            [['poll_id','id','created_at','poll_question_id','question_option_id','status','user_id'], 'integer'],
            // [['title'], 'string'],
            
            [['question_option_id','poll_id'], 'required','on'=>['create','update']],
            
            
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
        // $fields[] = 'categoryName';

        // $fields['pollQuestion'] = (function($model){
        //     return @$model->poll->title;
        //    // return (@$model->isReported) ? 1: 0;
        // });
        // $fields['poll'] = (function($model){
        //     return @$model->poll;          
        // });
        $fields['options'] = (function($model){
            return @$model->options;          
        });
        return $fields;
    }


    public function extraFields()
    {
        return ['poll','options'];
    }
   
     public function getOptions()
    {
        return $this->hasMany(PollQuestionOption::className(), ['id' => 'question_option_id'])->andOnCondition(['poll_qustion_options.status' => PollQuestionOption::STATUS_ACTIVE])->all();

    }

    public function getPoll()
    {
        return $this->hasMany(Poll::className(), ['id' => 'poll_id'])->andOnCondition(['poll.status' => Poll::STATUS_ACTIVE])->all();

    }

}
