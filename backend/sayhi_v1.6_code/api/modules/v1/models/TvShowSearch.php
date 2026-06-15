<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\LiveTv;
use api\modules\v1\models\TvShow;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class TvShowSearch extends TvShow
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['category_id','tv_channel_id','id'], 'integer'],
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

    
        
        $query = TvShow::find()
        ->where(['tv_show.status'=>TvShow::STATUS_ACTIVE])
        ->orderBy(['tv_show.name'=>SORT_ASC]);

       

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
            'tv_show.tv_channel_id' => $this->tv_channel_id
            
        ]);

        $query->andFilterWhere([
            'tv_show.id' => $this->id
            
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'tv_show.name', $this->name],
                    ['like', 'description', $this->name]
            ]
        );

        return $dataProvider;




    }

    
}
