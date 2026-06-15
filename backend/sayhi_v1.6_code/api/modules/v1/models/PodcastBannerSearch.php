<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\PodcastBanner;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PodcastBannerSearch extends PodcastBanner
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

    
        
        $query = PodcastBanner::find()
        ->where(['podcast_banner.status'=>TvBanner::STATUS_ACTIVE])
        ->orderBy(['podcast_banner.id'=>SORT_DESC]);

       

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
            'podcast_banner.reference_id' => $this->reference_id
            
        ]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'podcast_banner.name', $this->name]
            ]
        );

        return $dataProvider;




    }



   

    
}
