<?php
namespace api\modules\v1\models;
use api\modules\v1\models\Ad;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use Yii;

class AdSearch extends Ad
{
    
    public $min_price;
    public $max_price;
    public $city_id;
    public $country_id;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title','location','city_id'], 'string'],
            [['category_id','sub_category_id','max_price','min_price','user_id','package_banner_id','featured','country_id','created_at','is_follower','status'], 'integer'],
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
        $this->load($params);
        
        $query = Ad::find()
        //->where(['<>','status',Ad::STATUS_DELETED]);
        ->joinWith('user')
        ->innerJoinWith('locations')
        ->select(['ad.id','ad.user_id','ad.category_id','ad.title','ad.sub_category_id','ad.status','ad.phone','ad.price','ad.view','ad.description','featured','featured_exp_date','currency','is_banner_ad','package_banner_id','ad.created_at','deal_start_date','deal_end_date','deal_price'])
        ->where(['ad.status'=>Ad::STATUS_ACTIVE])
        ->andWhere(['user.status'=>User::STATUS_ACTIVE]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

       

        $this->setAttributes($params);
        if($this->is_follower==1){
          $model = new Ad();
          $userId = Yii::$app->user->identity->id; 
          $followingUserId = $model->getAllFollowingId($userId);
          $query->andWhere(['IN','ad.user_id',$followingUserId]);
        }

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
       
        // grid filtering conditions
         $query->andFilterWhere([
            'ad.user_id' => $this->user_id,
            'ad.category_id' => $this->category_id,
            'ad.sub_category_id' => $this->sub_category_id,
            'ad.package_banner_id' => $this->package_banner_id,
            'ad.featured' => $this->featured,
            // 'user_location.country_id' => $this->country_id,
            
            
        ]);

    //    if(trim($this->city_id)){
    //       $cities = explode(',',trim($this->city_id));
    //       $query->andFilterWhere(['in', 'user_location.city_id', $cities]);
    //    }

      /*
       if($this->featured){
          $query->andFilterWhere(['>=', 'featured_exp_date', time()]);
         
       }
       */
      
      $query->andFilterWhere(['like', 'title', $this->title]);
      $query->andFilterWhere(['>=', 'price', $this->min_price]);
      $query->andFilterWhere(['<=', 'price', $this->max_price]);
      

        return $dataProvider;
    }

    
    public function myAdSearch($params)
    {
        
        $this->load($params,'');

        $userId = @Yii::$app->user->identity->id; 
        $query  = Ad::find()
        ->innerJoinWith('locations')
        ->select(['ad.id','ad.user_id','ad.category_id','ad.title','ad.sub_category_id','ad.status','ad.phone','ad.price','ad.view','ad.description','featured','featured_exp_date','currency','is_banner_ad','package_banner_id','ad.created_at','ad.deal_start_date','ad.deal_end_date','ad.deal_price'])
        ->where(['ad.user_id'=>$userId]);
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

       
        $this->load($params);
        
        // $this->setAttributes($params);
       

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        // grid filtering conditions
         $query->andFilterWhere([
           
            'ad.category_id' => $this->category_id,
            'ad.sub_category_id' => $this->sub_category_id,
            'ad.featured' => $this->featured,
            'ad.status' => $this->status,
              
        ]);


      
      $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    } 
}
