<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Coupon;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class CouponSearch extends Coupon
{
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name','code','description'], 'string'],
            [['business_id','id','business_category_id'], 'integer'],
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
        $query = Coupon::find()
        ->where(['coupon.status'=>Coupon::STATUS_ACTIVE])
        ->joinWith('business')
        ->orderBy(['coupon.name'=>SORT_ASC]);

       

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
            'coupon.business_id' => $this->business_id
            
        ]);
        $query->andFilterWhere([
            'business.business_category_id' => @$this->business_category_id
            
        ]);

        $query->andFilterWhere([
            'coupon.id' => $this->id
            
        ]);

        $query->andFilterWhere([
            'coupon.code' => $this->code
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'coupon.name', $this->name],
                    ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;




    }

    //Search favorite coupon
    public function CouponMyFavorite($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');
        $query = Coupon::find()
        ->where(['coupon.status'=>Coupon::STATUS_ACTIVE])
        ->joinWith('couponMyFavorite')
        ->joinWith('business')
        ->andWhere(['user_favorite.user_id'=>$userId])
        ->orderBy(['coupon.name'=>SORT_ASC]);

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
            'coupon.business_id' => $this->business_id
            
        ]);
        $query->andFilterWhere([
            'business.business_category_id' => @$this->business_category_id
            
        ]);
      
        $query->andFilterWhere(
            [
                'or',
                    ['like', 'coupon.name', $this->name],
                    ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;

    }

    
}
