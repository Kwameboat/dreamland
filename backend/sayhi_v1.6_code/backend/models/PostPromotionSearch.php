<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\PostPromotion;

class PostPromotionSearch extends PostPromotion
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
        //      [['id'], 'integer'],
        //    [['title'], 'safe'],
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

        $currentTime = time();
        $query = PostPromotion::find()
        ->select('post_promotion.*')
        ->JoinWith(['post'])
        ->JoinWith(['audience'])
        ->where(['<>','post_promotion.status',PostPromotion::STATUS_DELETED]);

          $query->andwhere(['>=','post_promotion.expiry',$currentTime]);

        $query->groupBy(['post_promotion.id']);
      

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            
            return $dataProvider;
        }

        return $dataProvider;
    }

    public function searchCompletePromotion($params)
    {

        $currentTime = time();
        $query = PostPromotion::find()
        ->select('post_promotion.*')
        ->JoinWith(['post'])
        ->JoinWith(['audience'])
        ->where(['<>','post_promotion.status',PostPromotion::STATUS_DELETED]);

        $query->andwhere(['<','post_promotion.expiry',$currentTime]);

        $query->groupBy(['post_promotion.id']);
      

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        return $dataProvider;
    }

  
}
