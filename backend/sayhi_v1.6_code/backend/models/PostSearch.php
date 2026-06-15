<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Post;

class PostSearch extends Post
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['total_like'], 'integer'],
            
            [['title','user_id'], 'safe'],
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
        $postAllow = [];
        $postAllow[] = Post::TYPE_NORMAL;
        $postAllow[] = Post::TYPE_COMPETITION;
        $postAllow[] = Post::TYPE_CLUB;
    
        
        $query = Post::find()
        ->where(['<>','status',Post::STATUS_DELETED])
        ->andWhere(['type'=>$postAllow]);

        //->orderBy(['id'=>SORT_DESC]);
        
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at'=>SORT_DESC],
                'attributes' => [
                    'total_like',
                    'total_view',
                    'total_comment',
                    'popular_point',
                    'status',
                    'created_at',
                    'user_id',
                    'title',


                    
                    ],
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
            'post.user_id' => $this->user_id
        ]);
       
        $query->andFilterWhere(['like', 'title', $this->title]);
        return $dataProvider;
    }


    public function searchReportedPost($params)
    {
        $query = Post::find()
        ->where(['<>','post.status',Post::STATUS_DELETED])
        ->innerJoinWith('reportedPostActive');
        //->orderBy(['id'=>SORT_DESC]);
        
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at'=>SORT_DESC],
                'attributes' => [
                    'total_like',
                    'total_view',
                    'total_comment',
                    'popular_point',
                    'status',
                    'created_at',
                    'user_id',
                    'title',


                    
                    ],
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
            'post.user_id' => $this->user_id
        ]);
       
        $query->andFilterWhere(['like', 'title', $this->title]);
        return $dataProvider;
    }

    public function searchReelPost($params)
    {
        $query = Post::find()
        ->where(['<>','post.status',Post::STATUS_DELETED])
        ->andWhere(['post.type'=> Post::TYPE_REEL]);     
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
            'post.user_id' => $this->user_id
        ]);
       
        $query->andFilterWhere(['like', 'title', $this->title]);
        return $dataProvider;
    }

}
