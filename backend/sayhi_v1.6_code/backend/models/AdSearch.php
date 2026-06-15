<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Ad;


class AdSearch extends Ad
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'string'],
            [['user_id'], 'integer'],

            [['title'], 'safe'],
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
    public function search($params,$type='active')
    {
        $query = Ad::find()
        ->where(['<>','status',Ad::STATUS_DELETED]);

        if($type=='active'){
            $query->andWhere(['status'=>Ad::STATUS_ACTIVE]);
        }elseif($type=='pending'){
            $query->andWhere(['status'=>Ad::STATUS_PENDING]);
        }elseif($type=='expire'){
            $query->andWhere(['status'=>Ad::STATUS_EXPIRED]);
        }


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
       
        $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }

    public function searchReportedAd($params)
    {
    
       
        $query = Ad::find()
        ->innerJoinWith('reportedAdActive')
        ->where(['<>','ad.status',Ad::STATUS_DELETED]);
        $query->andWhere(['ad.status'=>Ad::STATUS_ACTIVE]);
        

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
       /* $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ]);
        */
        $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }

    
}
