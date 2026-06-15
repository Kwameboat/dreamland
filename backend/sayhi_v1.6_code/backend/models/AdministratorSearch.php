<?php
namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Administrator;


/**
 * CountryySearch represents the model behind the search form of `app\models\Countryy`.
 */
class AdministratorSearch extends Administrator
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name','username','email'], 'safe'],
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
        $query = Administrator::find()
        //->where(['role'=>Administrator::ROLE_ADMIN])
        ->where(['IN','role',[Administrator::ROLE_ADMIN,Administrator::ROLE_SUBADMIN]])
        ->andWhere(['<>','status',Administrator::STATUS_DELETED]);

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
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username]);

        return $dataProvider;
    }
}
