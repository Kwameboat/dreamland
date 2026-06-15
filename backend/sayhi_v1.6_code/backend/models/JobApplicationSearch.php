<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Job;
use common\models\JobApplication;

class JobApplicationSearch extends JobApplication
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','job_id'], 'integer'],
            // [['title','category_id'], 'safe'],
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
        $query = JobApplication::find()
        
        ->where(['<>','status',Job::STATUS_DELETED]);

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
            'job_id' => $this->job_id
        ]);
       
        // $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }

  
}
