<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Category;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class CategorySearch extends Category
{
    
    /**
     * {@inheritdoc}
     */

    public function rules()
    {
        return [
            [['name'], 'string'],
            [['type','level','parent_id'], 'integer'],
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
     * search category 
     */

    public function searchCategory($params)
    {
       


        $this->load($params,'');
        
        $query = Category::find()
        ->where(['category.status'=>Category::STATUS_ACTIVE])
        
       
        ->orderBy(['category.id'=>SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
          
            return $dataProvider;
        }
        // grid filtering conditions
         $query->andFilterWhere([
            'category.type' => $this->type,
            'category.level' => $this->level,
            'category.parent_id' => $this->parent_id,
        ]);
        
        $query->andFilterWhere(['or',
            ['like', 'category.name', $this->name]]
         
        );

        return $dataProvider;




    }



   
    
}
