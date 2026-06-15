<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Gift;
use api\modules\v1\models\GiftHistory;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class GiftSearch extends Gift
{
    
    /**
     * {@inheritdoc}
     */
    
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['category_id'], 'integer'],
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

    
        
        $query = Gift::find()
        ->where(['gift.status'=>LiveTv::STATUS_ACTIVE])
        ->orderBy(['gift.name'=>SORT_ASC]);

       

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
            'gift.category_id' => $this->category_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'gift.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    public function searchPopular($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

    
        
        $query = Gift::find()
        ->select(['gift.*','count(gift_history.id) as totalUsedGift'])
        ->where(['gift.status'=>LiveTv::STATUS_ACTIVE])
        ->joinWith('giftHistory')
        ->groupBy(['gift.id'])
        ->orderBy(['totalUsedGift'=>SORT_DESC])
        ->limit(20);

        
       

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
            
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
     

        return $dataProvider;




    }



    
}
