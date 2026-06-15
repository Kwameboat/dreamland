<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\UserReaction;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class UserReactionSearch extends UserReaction
{
    
    /**
     * {@inheritdoc}
     */

    public function rules()
    {
        return [
          
            [['type','reference_id','reaction'], 'integer'],
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
     * search category 
     */

    public function search($params)
    {
        $this->load($params,'');
        $query = UserReaction::find()
        ->where(['user_reaction.reaction'=>UserReaction::REACTION_INTERESTING])
        ->orderBy(['user_reaction.id'=>SORT_DESC])
        ->joinWith(['user' => function ($query) {
            $query->select(['id','name', 'username', 'email', 'image', 'role', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
        }]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
          
            return $dataProvider;
        }
        // grid filtering conditions
         $query->andFilterWhere([
            'user_reaction.type' => $this->type,
            'user_reaction.reference_id' => $this->reference_id,
            'user_reaction.reaction' => $this->reaction
        ]);
        return $dataProvider;
    }
   
}
