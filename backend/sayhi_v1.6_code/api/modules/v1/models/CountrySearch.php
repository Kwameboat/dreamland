<?php
namespace api\modules\v1\models;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use api\modules\v1\models\Country;
class CountrySearch extends Country
{
    
    /**
     * {@inheritdoc}
     */
    // public $my_joined_club;
    public $fullname;
    public function rules()
    {
        return [
            [['name'], 'string'],
            // [['reference_id'], 'integer'],
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
     * search story post
     */

    public function search($params)
    {
        // $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');
        // print_r($this->load($params,''));
        $name=       @$params['name'];
        $query = Country::find()
        ->select([
            'country.id',
            'country.name',
            'city.id',
            'city.name',
            'city.country_id',
            "IF(country.name = :name, 'country', 'city') AS type",
            "IF(country.name = :name, country.name, CONCAT(city.name, ', ', country.name)) AS fullname"
       
        ])
        // ->addSelect([
        //     "CASE WHEN country.name = :title THEN 'country' ELSE 'city' END AS type",
        //     "CASE WHEN country.name = :title THEN country.name ELSE CONCAT(city.name, ', ', country.name) END AS fullname"
        // ])
        ->joinWith('city')
        ->where(['country.status' => Country::STATUS_ACTIVE]);
        // ->orderBy(['city.name' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        if (!empty($params['name'])) {
            $query->andWhere(
                'country.name LIKE :name OR city.name LIKE :name',
                [':name' => '%' . $params['name'] . '%']
            );
        }

        return $dataProvider;

    }



    
}
