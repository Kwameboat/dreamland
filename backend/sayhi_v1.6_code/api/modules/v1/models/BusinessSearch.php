<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\LiveTv;
use api\modules\v1\models\Business;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class BusinessSearch extends Business
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name','description'], 'string'],
            [['business_category_id','id'], 'integer'],
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

    
        
        $query = Business::find()
        ->where(['business.status'=>Business::STATUS_ACTIVE])
        ->orderBy(['business.name'=>SORT_ASC]);

       

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
            'business.business_category_id' => $this->business_category_id
            
        ]);

        $query->andFilterWhere([
            'business.id' => $this->id
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'business.name', $this->name],
                    ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;




    }

       //Search My favorite business
       public function BusinessMyFavorite($params)
       {
           $userId   =  @Yii::$app->user->identity->id;
           $this->load($params,'');
           $query = Business::find()
           ->where(['business.status'=>Business::STATUS_ACTIVE])
           ->joinWith('businessMyFavorite')
           ->andWhere(['user_favorite.user_id'=>$userId])
           ->orderBy(['business.name'=>SORT_ASC]);
   
           $dataProvider = new ActiveDataProvider([
               'query' => $query,
               'pagination' => [
                   'pageSize' => 20,
               ]
           ]);
           
           if (!$this->validate()) {
              
               return $dataProvider;
           }
         
           $query->andFilterWhere([
               'business.business_category_id' => @$this->business_category_id
               
           ]);
         
           $query->andFilterWhere(
               [
                   'or',
                       ['like', 'business.name', $this->name],
                       ['like', 'business.description', $this->description]
               ]
           );
   
           return $dataProvider;
   
       }

    
}
