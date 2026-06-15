<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\JobApplications;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class JobApplicationSearch extends JobApplications
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cover_letter'], 'string'],
            [['job_id','id','status','user_id'], 'integer'],
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
        $userId   =  @Yii::$app->user->identity->id;
        $this->load($params,'');


        
        $query = JobApplications::find()
        ->where(['job_applications.user_id'=>$userId])
        ->orderBy(['job_applications.id'=>SORT_ASC]);

       

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
            'job_applications.job_id' => $this->job_id
            
        ]);

        $query->andFilterWhere([
            'job_applications.id' => $this->id
            
        ]);
        $query->andFilterWhere([
            'job_applications.user_id' => $this->user_id
            
        ]);
        $query->andFilterWhere([
            'job_applications.status' => $this->status
            
        ]);
        $query->andFilterWhere(
            [
                'or',
                    ['like', 'job_applications.cover_letter', $this->cover_letter],
            ]
        );

        return $dataProvider;




    }


    
}
