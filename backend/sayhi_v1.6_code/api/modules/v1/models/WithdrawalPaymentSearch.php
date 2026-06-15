<?php
namespace api\modules\v1\models;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use api\modules\v1\models\WithdrawalPayment;
use yii\db\Expression;

class WithdrawalPaymentSearch extends WithdrawalPayment
{
    
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['month'], 'string'],
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
    public function searchMyWithdrawalPayment($params)
    {
        $userId = Yii::$app->user->identity->id;
        $this->setAttributes($params);
    
        $query = WithdrawalPayment::find()
        ->where(['withdrawal_payment.user_id'=>$userId])
        ->orderBy(['withdrawal_payment.created_at'=>SORT_DESC]);
        /*$monthArr = explode(',',$this->month);
        $query->andWhere(['IN',"(date_format(FROM_UNIXTIME(created_at), '%Y-%m' ))", $monthArr]);*/
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
       
        // grid filtering conditions
        $query->andFilterWhere([
            'withdrawal_payment.status' => $this->status
        ]);
       
      // $query->andFilterWhere(['like', 'order_number', $this->order_number]);
       // $result = $dataProvider->getModels();
       // print_r($result);
        
        return $dataProvider;
    }

    

    
}
