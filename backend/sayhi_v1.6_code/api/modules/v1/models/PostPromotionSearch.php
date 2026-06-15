<?php
namespace api\modules\v1\models;
use yii;
use api\modules\v1\models\PostPromotion;
use yii\base\Model;
use yii\data\ActiveDataProvider;
//use api\modules\v1\models\Setting;
use  yii\db\Expression;


class PostPromotionSearch extends PostPromotion
{
    
  public $type;
    
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'string'],
          
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
    public function searchMyPromotion($params)
    {
      
      
      $userId =(int)@Yii::$app->user->identity->id;

      $this->setAttributes($params);

      $currentTime = time();


      //echo $this->type;

      
      $query = PostPromotion::find()
        ->select('post_promotion.*')
        ->JoinWith(['post'])
       
        ->where(['<>','post_promotion.status',PostPromotion::STATUS_DELETED])
        ->andWhere(['post.user_id'=>$userId]);
       
        if($this->type=='ACTIVE'){
          $query->andwhere(['>','post_promotion.expiry',$currentTime]);
        }else if($this->type=='COMPLETED'){
          $query->andwhere(['<','post_promotion.expiry',$currentTime]);
        }

        $query->groupBy(['post_promotion.id']);
        

        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
           // 'pagination' => false
            'pagination' => [
              'pageSize' => 20,
          ]
        ]);

      //  $this->load($params);

        

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

      
       return $dataProvider;
    }



    
    
}
