<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\GiftHistory;

class GiftHistorySearch extends GiftHistory
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['reciever_id'], 'integer'],
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
        $query = GiftHistory::find()
        
        ->where(['send_on_type'=>GiftHistory::SEND_TO_TYPE_LIVE]);
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
            'reciever_id' => $this->reciever_id
        ]);
       
        // $query->andFilterWhere(['like', 'name', $this->name]);

        return $dataProvider;
    }

  
}
