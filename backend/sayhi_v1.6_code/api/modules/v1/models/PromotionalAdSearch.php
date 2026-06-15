<?php
namespace api\modules\v1\models;
use api\modules\v1\models\PromotionalAd;
use yii\base\Model;
use yii\data\ActiveDataProvider;


class PromotionalAdSearch extends PromotionalAd
{
    
    
    public $category_id;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            
            [['category_id','country_id'], 'integer'],
            [['country_id'], 'required'],
            

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
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        
        $currentTime = time();
        $query = PromotionalAd::find()
        //->where(['<>','status',Ad::STATUS_DELETED]);
        ->innerJoinWith('promotionalAdCategory')
        ->select(['promotional_ad.id','promotional_ad.ad_type','promotional_ad.country_id','promotional_ad.name','promotional_ad.image','promotional_ad.video','promotional_ad.start_date','promotional_ad.end_date'])
        ->where(['promotional_ad.status'=>Ad::STATUS_ACTIVE])
        ->andWhere(['>','promotional_ad.end_date',$currentTime])
        ->andWhere(['<','promotional_ad.start_date',$currentTime]);
        
                
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
      //  $this->load($params);

        $this->setAttributes($params);

        if (!$this->validate()) {
            
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $query->andFilterWhere(['promotional_ad.country_id'=> $this->country_id]);
       $query->andFilterWhere(['promotional_ad_category.category_id'=> $this->category_id]);
      

        return $dataProvider;
    }

    
    
}
