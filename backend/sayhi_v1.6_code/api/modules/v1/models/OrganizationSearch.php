<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Campaign;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class OrganizationSearch extends Organization
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
       
          return Model::scenarios();
    }


     /**
     * search story post
     */

    public function search($params)
    {
        $currentdate = time();
        $this->load($params,'');

    
        $query = Organization::find()
     ->where(['Organization.status'=>Organization::STATUS_ACTIVE])
        ->orderBy(['Organization.id'=>SORT_DESC]);

       

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
       
        if (!$this->validate()) {
          
            return $dataProvider;
        }
       
        
        $query->andFilterWhere(
            [
                'or',
                    ['like', 'organization.name', $this->name],
                     ['like', 'organization.id', $this->id]
            ]
        );

        return $dataProvider;


       

    }
    

   


    


    
    
    

    
}
