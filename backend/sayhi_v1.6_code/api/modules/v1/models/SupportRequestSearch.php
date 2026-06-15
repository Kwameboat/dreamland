<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\SupportRequest;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class SupportRequestSearch extends SupportRequest
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['is_reply'], 'integer'],
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
        
        $userId = Yii::$app->user->identity->id;
        $this->load($params,'');

        
        $query = SupportRequest::find()
        ->where(['support_request.user_id'=>$userId,'support_request.status'=>SupportRequest::STATUS_ACTIVE])
        ->orderBy(['support_request.id'=>SORT_DESC]);

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
            'is_reply' => $this->is_reply,
        ]);
     

        return $dataProvider;
    }
    
    
}
