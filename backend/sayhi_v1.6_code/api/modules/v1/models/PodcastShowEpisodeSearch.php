<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Podcast;
use api\modules\v1\models\PodcastShow;
use api\modules\v1\models\PodcastShowEpisode;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PodcastShowEpisodeSearch extends PodcastShowEpisode
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['podcast_show_id'], 'integer'],
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

    
        
        $query = PodcastShowEpisode::find()
        ->where(['podcast_show_episode.status'=>PodcastShowEpisode::STATUS_ACTIVE])
        ->orderBy(['podcast_show_episode.id'=>SORT_DESC]);

       

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
            'podcast_show_episode.podcast_show_id' => $this->podcast_show_id
            
        ]);


        $query->andFilterWhere(
            [
                'or',
                    ['like', 'podcast_show_episode.name', $this->name]
            ]
        );

        return $dataProvider;




    }



    
}
