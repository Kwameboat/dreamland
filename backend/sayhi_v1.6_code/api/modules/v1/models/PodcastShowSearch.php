<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Podcast;
use api\modules\v1\models\PodcastShow;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PodcastShowSearch extends PodcastShow
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['category_id','podcast_channel_id','id'], 'integer'],
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

    
        
        $query = PodcastShow::find()
        ->where(['podcast_show.status'=>PodcastShow::STATUS_ACTIVE])
        ->orderBy(['podcast_show.name'=>SORT_ASC]);

       

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
            'podcast_show.id' => $this->id
            
        ]);
        $query->andFilterWhere([
            'podcast_show.podcast_channel_id' => $this->podcast_channel_id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'podcast_show.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    
}
