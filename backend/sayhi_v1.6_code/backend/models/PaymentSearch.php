<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Payment;


class PaymentSearch extends Payment
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_id'], 'string'],
            [['amount','coin'], 'number'],
            //[['transaction_id'], 'safe'],
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
        $query = Payment::find()
         ->where(['type'=>Payment::TYPE_PRICE])
         ->andWhere(['payment_type'=>Payment::PAYMENT_TYPE_PACKAGE])
         ->andWhere(['transaction_type'=>Payment::TRANSACTION_TYPE_CREDIT])
        ->orderBy(['payment.created_at'=>SORT_DESC]);
        // add conditions that should always apply here

        
         
        

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            
        ]);
       
       $query->andFilterWhere(['like', 'payment.transaction_id', $this->transaction_id]);
      // $query->andFilterWhere(['like', 'user.name', $this->user_id]);
       

        return $dataProvider;
    }

    public function searchAdminWallet($params)
    {
        $adminId =1;
        $query = Payment::find()
         ->where(['type'=>Payment::TYPE_COIN,'user_id'=>$adminId])
         //->andWhere(['payment_type'=>Payment::PAYMENT_TYPE_PACKAGE])
//         ->andWhere(['transaction_type'=>Payment::TRANSACTION_TYPE_CREDIT])
        ->orderBy(['payment.created_at'=>SORT_DESC]);
        // add conditions that should always apply here

        
         
        

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
       $query->andFilterWhere([
            'coin' => $this->coin
            
        ]);
       
     //  $query->andFilterWhere(['like', 'payment.transaction_id', $this->transaction_id]);
      // $query->andFilterWhere(['like', 'user.name', $this->user_id]);
       

        return $dataProvider;
    }

   
    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function promotionsearch($params , $promotion_id)
    {
        $query = Payment::find()
      
         ->andWhere(['post_promotion_id'=>$promotion_id])
       
        ->orderBy(['payment.created_at'=>SORT_DESC]);
        // add conditions that should always apply here

        
         
        

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
       $query->andFilterWhere([
            'amount' => $this->amount,
            
        ]);
       
       $query->andFilterWhere(['like', 'payment.transaction_id', $this->transaction_id]);
     

        return $dataProvider;
    }
}
