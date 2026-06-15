<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Podcast;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PodcastSearch extends Podcast
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['category_id'], 'integer'],
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

    
        
        $query = Podcast::find()
        ->where(['podcast.status'=>Podcast::STATUS_ACTIVE])
        ->orderBy(['podcast.id'=>SORT_DESC]);

       

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
            'podcast.category_id' => $this->category_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'podcast.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }



    public function podcastMySubscribed($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

        $query = Podcast::find()
        ->where(['podcast.status'=>Podcast::STATUS_ACTIVE])
        ->joinWith('podcastMySubscriber')
        ->andWhere(['podcast_subscriber.user_id'=>$userId])
        ->orderBy(['podcast.name'=>SORT_ASC]);

       

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
            'podcast.category_id' => $this->category_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'podcast.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    


    
    
    public function PodcastMyFavorite($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

        $query = Podcast::find()
        ->where(['podcast.status'=>Podcast::STATUS_ACTIVE])
        ->joinWith('podcastMyFavorite')
        ->andWhere(['podcast_favorite.user_id'=>$userId])
        ->orderBy(['podcast.name'=>SORT_ASC]);

       

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
            'podcast.category_id' => $this->category_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'podcast.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    
}
