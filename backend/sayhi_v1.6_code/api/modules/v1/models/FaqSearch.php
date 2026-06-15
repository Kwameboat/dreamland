<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Faq;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class FaqSearch extends Faq
{
    
    /**
     * {@inheritdoc}
     */
    
    public function rules()
    {
        return [
            [['question','answer'], 'string'],
            [['id'], 'integer'],
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



    public function search($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

    
        
        $query = Faq::find()
        ->where(['faq.status'=>Faq::STATUS_ACTIVE])
        ->orderBy(['faq.id'=>SORT_ASC]);

       

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
            'faq.id' => $this->id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'faq.question', $this->question],
                    ['like', 'question', $this->question]
            ]
        );

        return $dataProvider;




    }
}
