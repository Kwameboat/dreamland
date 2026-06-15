<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Poll;
use api\modules\v1\models\PollQuestion;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PollQuestionSearch extends PollQuestion
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [      
            [['poll_id'], 'integer'],
            [['title'], 'string'],            
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

    
        
        $query = PollQuestion::find()
        ->where(['poll_question.status'=>PollQuestion::STATUS_ACTIVE])
        ->orderBy(['poll_question.id'=>SORT_ASC]);

       

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
            'poll_question.poll_id' => $this->poll_id
            
        ]);

        // $query->andFilterWhere([
        //     'poll_question.campaigner_id' => $this->campaigner_id
            
        // ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'poll_question.title', $this->title]
                    // ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;




    }


    
}
