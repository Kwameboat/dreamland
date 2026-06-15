<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\EventTicketBooking;

class EventTicketBookingSearch extends EventTicketBooking
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['id'], 'integer'],
            [['event_id','is_check_in','user_first_name'], 'safe'],
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
        $query = EventTicketBooking::find();
        
       // ->where(['<>','status',EventTicket::STATUS_DELETED]);

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
            'event_id' => $this->event_id,
            'is_check_in' => $this->is_check_in
        ]);
       
       //$query->andFilterWhere(['like', 'user_first_name', $this->user_first_name]);
      // $query->orFilterWhere(['like', 'user_last_name', $this->user_first_name]);
        $searchWords=[];
        if($this->user_first_name){
            $searchWords =  explode(' ',$this->user_first_name);
        }
        $condition = ['or'];
        foreach ($searchWords as $key) {
            $condition[] = ['like', 'user_first_name', $key];
        }
       // $query->andFilterWhere($condition);

        $conditionLastName = ['or'];
        foreach ($searchWords as $key) {
            $conditionLastName[] = ['like', 'user_last_name', $key];
        }
        //$query->orFilterWhere($conditionLastName);
        $query->andFilterWhere([
            'OR',
            $condition,
            $conditionLastName
        ]);

    
      /*$query->andFilterWhere(['IN', 'user_first_name', function($query) use ($searchWords) {
        foreach ($searchWords as $word) {
            $query1->orFilterWhere(['LIKE', 'user_first_name', $word]);
        }
       }]);*/

        return $dataProvider;
    }

  
}
