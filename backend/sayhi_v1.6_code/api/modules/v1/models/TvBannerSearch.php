<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\TvBanner;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class TvBannerSearch extends TvBanner
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['reference_id'], 'integer'],
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

    
        
        $query = TvBanner::find()
        ->where(['tv_banner.status'=>TvBanner::STATUS_ACTIVE])
        ->orderBy(['tv_banner.id'=>SORT_DESC]);

       

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
            'tv_banner.reference_id' => $this->reference_id
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'tv_banner.name', $this->name]
            ]
        );

        return $dataProvider;




    }



    public function liveTvMySubscribed($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

        $query = LiveTv::find()
        ->where(['live_tv.status'=>LiveTv::STATUS_ACTIVE])
        ->joinWith('liveTvSubscriber')
        ->andWhere(['live_tv_subscriber.user_id'=>$userId])
        ->orderBy(['live_tv.name'=>SORT_ASC]);

       

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
            'live_tv.category_id' => $this->category_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'live_tv.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    


    
    
    public function liveTvMyFavorite($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

        $query = LiveTv::find()
        ->where(['live_tv.status'=>LiveTv::STATUS_ACTIVE])
        ->joinWith('liveTvMyFavorite')
        ->andWhere(['live_tv_favorite.user_id'=>$userId])
        ->orderBy(['live_tv.name'=>SORT_ASC]);

       

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
            'live_tv.category_id' => $this->category_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'live_tv.name', $this->name],
                    // ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    
}
