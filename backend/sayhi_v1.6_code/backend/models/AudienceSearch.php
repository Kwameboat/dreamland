<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Audience;

class AudienceSearch extends Audience
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['total_like'], 'integer'],
            
            [['title','user_id'], 'safe'],
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
   
}
