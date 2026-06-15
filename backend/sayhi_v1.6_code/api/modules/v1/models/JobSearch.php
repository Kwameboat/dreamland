<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Job;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class JobSearch extends Job
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title','description'], 'string'],
            [['category_id','id'], 'integer'],
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
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

    
        
        $query = Job::find()
        ->where(['jobs.status'=>Job::STATUS_ACTIVE])
        ->orderBy(['jobs.title'=>SORT_ASC]);

       

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
            'jobs.category_id' => $this->category_id
            
        ]);

        $query->andFilterWhere([
            'jobs.id' => $this->id
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'jobs.title', $this->title],
                    ['like', 'jobs.description', $this->description]
            ]
        );

        return $dataProvider;




    }


    
}
