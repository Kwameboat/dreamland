<?php
namespace api\modules\v1\models;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\Collaborate;
use yii\db\Expression;

class CollaborateSearch extends Collaborate
{
 
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['status'], 'integer'],
            
            
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
        $userId = Yii::$app->user->identity->id;
        $this->setAttributes($params);
        $query = Collaborate::find()
        ->where(['status'=>Collaborate::STATUS_ACTIVE]);
        $query->andWhere(
            [
                'or',

                ['collaborate.collaborator_id' => $userId],
                ['collaborate.author_id' => $userId],
                

            ]
        );

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>  [
                'pageSize' => 20,
            ]
        ]);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        return $dataProvider;
    }

    
}
