<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Audio;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class audioSearch extends Audio
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name','artist'], 'string'],
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

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    
    /**
     * search post
     */

    public function search($params)
    {
        
        
        $this->load($params,'');

        
        $query = Audio::find()
        ->where(['<>','audio.status',Audio::STATUS_DELETED])
        ->orderBy(['audio.name'=>SORT_ASC]);
        

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
            'category_id' => $this->category_id,
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(['or',
            ['like', 'name', $this->name],
            ['like', 'artist', $this->name]]
        );

        return $dataProvider;
    }
    
    
}
