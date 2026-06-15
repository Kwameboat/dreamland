<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Poll;
use common\models\PollQuestion;
use common\models\PollQuestionAnswer;
use common\models\User;

class PollQuestionAnswerSearch extends PollQuestionAnswer
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['id'], 'integer'],
            [['user_id','poll_question_id','poll_option_id','question_option_id','poll_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
          return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = PollQuestionAnswer::find()
        
        ->where(['<>','status',PollQuestionAnswer::STATUS_DELETED]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
       $query->andFilterWhere([
            'poll_id' => $this->poll_id
        ]);
        // $query->andFilterWhere([
        //     'user_id' => $this->user_id
        // ]);
        // $query->andFilterWhere([
        //     'poll_question_id' => $this->poll_question_id
        // ]);
        // $query->andFilterWhere([
        //     'question_option_id' => $this->question_option_id
        // ]);
       

        // $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }

  
}
