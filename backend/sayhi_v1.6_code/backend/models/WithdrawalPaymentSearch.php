<?php
namespace backend\models;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\WithdrawalPayment;


class WithdrawalPaymentSearch extends WithdrawalPayment
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_id'], 'string'],
            [['amount','id'], 'number'],
         //   [['status'], 'integer'],
          // [['status','id'], 'safe'],
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
        $query = WithdrawalPayment::find();
       if($type=='completed'){
        $query->where(['status'=>WithdrawalPayment::STATUS_ACCEPTED]);     
       }
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
       $query->andFilterWhere([
            'amount' => $this->amount,
            'status' => $this->status,
            
        ]);
       
       $query->andFilterWhere(['like', 'withdrawal_payment.transaction_id', $this->transaction_id]);
      // $query->andFilterWhere(['like', 'user.name', $this->user_id]);
       

        return $dataProvider;
    }

   
    
}
