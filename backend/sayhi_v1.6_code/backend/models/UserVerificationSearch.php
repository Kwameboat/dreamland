<?php
namespace backend\models;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\UserVerification;


class UserVerificationSearch extends UserVerification
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           [['document_type'], 'string'],
            [['id'], 'number'],
         
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
    public function search($params,$type)
    {
        $query = UserVerification::find();
       /*if($type=='completed'){
        $query->where(['status'=>UserVerification::STATUS_PENDING]);     
       }*/
      //  ->where(['>','id',0])
       // ->orderBy(['withdrawal_payment.created_at'=>SORT_DESC]);
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            
        ]);
        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            
        ]);   
        
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
       /*$query->andFilterWhere([
            'document_type' => $this->document_type
            //'status' => $this->status,
            
        ]);*/
       
       $query->andFilterWhere(['like', 'user_verification.document_type', $this->document_type]);
      // $query->andFilterWhere(['like', 'user.name', $this->user_id]);
       

        return $dataProvider;
    }

   
    
}
