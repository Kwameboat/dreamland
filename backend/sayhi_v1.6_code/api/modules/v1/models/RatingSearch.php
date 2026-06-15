<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Rating;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class RatingSearch extends Rating
{
    
    /**
     * {@inheritdoc}
     */
  
    public function rules()
    {
        return [
            
            [['type','reference_id'], 'integer']
           
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
    
    /**
     * search 
     */

    public function search($params)
    {
        $userId     =     Yii::$app->user->identity->id;
        
        $this->load($params,'');

        
        $query = Rating::find()
        ->where(['rating.status'=>Rating::STATUS_ACTIVE])
        ->joinWith(['user' => function($query) {
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }])
        ->orderBy(['rating.id'=>SORT_DESC]);

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
            'type' => $this->type,
            'reference_id' => $this->reference_id
            
        ]);
      
        return $dataProvider;
    }


    
    
}
