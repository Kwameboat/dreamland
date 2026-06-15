<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\LiveTv;
use api\modules\v1\models\TvShow;
use api\modules\v1\models\TvShowEpisode;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class TvShowEpisodeSearch extends TvShowEpisode
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['tv_show_id'], 'integer'],
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

    
        
        $query = TvShowEpisode::find()
        ->where(['tv_show_episode.status'=>TvShowEpisode::STATUS_ACTIVE])
        ->orderBy(['tv_show_episode.id'=>SORT_DESC]);

       

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
            'tv_show_episode.tv_show_id' => $this->tv_show_id
            
        ]);


        $query->andFilterWhere(
            [
                'or',
                    ['like', 'tv_show_episode.name', $this->name]
            ]
        );

        return $dataProvider;




    }



    
}
