<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Comment;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class CommentSearch extends Comment
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['comment'], 'string'],
            [['reference_id','user_id','parent_id'], 'integer'],
          
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
     * search story post
     */

    public function search($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

    
        $parent_id = @(int) $this->parent_id;
        
        $query = Comment::find()
        ->where(['comment.type'=>Comment::TYPE_COUPON])
        ->andWhere(['comment.status'=>Comment::STATUS_ACTIVE])
        ->orderBy(['comment.id'=>SORT_ASC]);


        if($parent_id){
            $query->andWhere(['comment.level'=> Comment::LEVEL_TWO]);
            $query->andWhere(['comment.parent_id'=> $parent_id]);
        }else{
            $query->andWhere(['comment.level'=> Comment::LEVEL_ONE]);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        // grid filtering conditions
        $query->andFilterWhere([
            'comment.reference_id' => $this->reference_id
            
        ]);

        $query->andFilterWhere([
            'comment.user_id' => $this->user_id
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                   
                    ['like', 'comment', $this->comment]
            ]
        );

        return $dataProvider;




    }

    
}
