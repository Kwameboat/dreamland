<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\DriverDocument;

class DriverDocumentSearch extends DriverDocument
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['id'], 'integer'],
           // [['title','category_id','campaigner_id'], 'safe'],
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
        $query = DriverDocument::find();
        
        

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
    //    $query->andFilterWhere([
    //         'category_id' => $this->category_id
    //     ]);
       
    //     $query->andFilterWhere([
    //         'campaigner_id' => $this->campaigner_id
    //     ]);

    //     $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }

  
}
