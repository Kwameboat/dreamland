<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Highlight;

class HighlightSearch extends Highlight
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['user_id'], 'safe'],
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

    public function searchReportedHighlight($params)
    {
        $query = Highlight::find()
        ->where(['<>','highlight.status',Highlight::STATUS_DELETED])
        ->innerJoinWith('reportedHighlightActive');
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
            'highlight.user_id' => $this->user_id
        ]);
       
        
        return $dataProvider;
    }


}
