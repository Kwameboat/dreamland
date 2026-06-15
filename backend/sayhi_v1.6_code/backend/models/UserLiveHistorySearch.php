<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\UserLiveHistory;

class UserLiveHistorySearch extends UserLiveHistory
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            // [['name','category_id'], 'safe'],
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
        $query = UserLiveHistory::find()
        
        ->where(['status'=>UserLiveHistory::STATUS_COMPLETED])
        ->orderBy(['user_live_history.id'=>SORT_DESC]);
        // ->andWhere(['post_type'=>GiftHistory::STATUS_DELETED]);
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
            'user_id' => $this->user_id
        ]);
       
        // $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }

  
}
