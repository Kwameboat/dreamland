<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Poll;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PollSearch extends Poll
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['title'], 'string'],
            [['category_id','campaigner_id'], 'integer'],
          //  [['title'], 'safe'],
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
        $userId   =  @Yii::$app->user->identity->id;
        $this->load($params,'');
        $currentDate = time();

        $subquery = (new \yii\db\Query())
        ->select('poll_id')
        ->from('poll_question_answer')
        ->where(['user_id' => $userId]);
        
        $query = Poll::find()
        ->where(['poll.status'=>Poll::STATUS_ACTIVE])
        ->andWhere(['type'=>Poll::TYPE_POLL])
        ->andWhere(['not in', 'poll.id', $subquery])
        ->andWhere(['>=','poll.end_time',$currentDate])
        ->orderBy(['poll.id'=>SORT_DESC]);

       

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
            'poll.category_id' => $this->category_id
            
        ]);

        $query->andFilterWhere([
            'poll.campaigner_id' => $this->campaigner_id
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'poll.title', $this->title],
                    ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;




    }


    
}
