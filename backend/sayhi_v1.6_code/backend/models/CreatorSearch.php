<?php

namespace backend\models;

use app\models\User;
use common\models\DreamlandAudience;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class CreatorSearch extends User
{
    /** @var string all|active|banned|pending */
    public $filter = 'all';

    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['email', 'name', 'username', 'filter'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $reelType = 4;
        $query = DreamlandAudience::creatorQuery()
            ->select([
                'user.*',
                'reel_count' => new Expression(
                    '(SELECT COUNT(*) FROM post p WHERE p.user_id = user.id AND p.type = :reelType AND p.status <> 0)',
                    [':reelType' => $reelType]
                ),
                'live_count' => new Expression(
                    '(SELECT COUNT(*) FROM user_live_history ulh WHERE ulh.user_id = user.id)'
                ),
            ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'user.id' => $this->id,
            'user.status' => $this->status,
        ]);
        $query->andFilterWhere(['like', 'user.name', $this->name]);
        $query->andFilterWhere(['like', 'user.email', $this->email]);
        $query->andFilterWhere(['like', 'user.username', $this->username]);

        switch ($this->filter) {
            case 'active':
                $query->andWhere(['user.status' => User::STATUS_ACTIVE]);
                break;
            case 'banned':
                $query->andWhere(['user.status' => User::STATUS_INACTIVE]);
                break;
            case 'pending':
                $query->andWhere(['user.status' => User::STATUS_PENDING]);
                break;
        }

        return $dataProvider;
    }
}
