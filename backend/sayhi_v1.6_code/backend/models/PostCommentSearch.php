<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\PostComment;

class PostCommentSearch extends PostComment
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['total_like'], 'integer'],
            
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



    public function searchReportedPostComment($params)
    {
        $query = PostComment::find()
        ->where(['<>','post_comment.status',PostComment::STATUS_DELETED])
        ->innerJoinWith('reportedPostCommentActive');
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
            'post_comment.user_id' => $this->user_id
        ]);
       
        // $query->andFilterWhere(['like', 'title', $this->title]);
        return $dataProvider;
    }


}
