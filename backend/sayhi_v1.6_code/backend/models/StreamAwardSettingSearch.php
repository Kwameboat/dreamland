<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\StreamAwardSetting;

class StreamAwardSettingSearch extends StreamAwardSetting
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position_id','award_coin'], 'integer'],
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
        $query = StreamAwardSetting::find()
        
        ->where(['<>','status',StreamAwardSetting::STATUS_DELETED]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
       $query->andFilterWhere([
            'position_id' => $this->position_id
        ]);
        $query->andFilterWhere([
            'award_coin' => $this->award_coin
        ]);
       
        return $dataProvider;
    }

  
}
