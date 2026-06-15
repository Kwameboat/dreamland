<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Audio;
use common\models\Story;

class StorySearch extends Story
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['user_id','filter_id','description'], 'safe'],
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
        $conditionTime = strtotime('-24 hours', time());
        // $this->load($params,'');
        $query = Story::find()
        ->where(['<>','status',Story::STATUS_DELETED]);
        $query->orderBy(['id'=>SORT_DESC]);
    //    $filter = $params['StorySearch']['filter_id'];
        // echo $this->filter_id;
        // exit;
        // if($filter==Story::STORY_TYPE_ACTIVE){
        //     $query->andWhere(['>','story.created_at',$conditionTime]); 
        // }
        // if($filter==Story::STORY_TYPE_COMPLETE){
        //     $query->andWhere(['<','story.created_at',$conditionTime]); 
        // }
       
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if($this->filter_id==Story::STORY_TYPE_ACTIVE){
            $query->andWhere(['>','story.created_at',$conditionTime]); 
        }
        if($this->filter_id==Story::STORY_TYPE_COMPLETE){
            $query->andWhere(['<','story.created_at',$conditionTime]); 
        }
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'user_id' => $this->user_id
        ]);
       
        // $query->andFilterWhere(['like', 'name', $this->name]);
        // $query->andFilterWhere(['like', 'artist', $this->artist]);
        return $dataProvider;
    }

    public function searchReportedStory($params)
    {
        $query = Story::find()
        ->where(['<>','story.status',Story::STATUS_DELETED])
        ->innerJoinWith('reportedStoryActive');
        //->orderBy(['id'=>SORT_DESC]);
        
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at'=>SORT_DESC],
                ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'story.user_id' => $this->user_id
        ]);
       
        $query->andFilterWhere(['like', 'description', $this->description]);
        return $dataProvider;
    }


}
