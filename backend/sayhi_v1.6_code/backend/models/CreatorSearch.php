<?php

namespace backend\models;

use app\models\User;
use common\models\DreamlandAudience;
use common\helpers\DreamlandCreatorApproval;
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
        $db = \Yii::$app->db;
        $userRef = $db->quoteTableName('user') . '.' . $db->quoteColumnName('id');
        $postTable = $db->quoteTableName('post');
        $liveTable = $db->quoteTableName('user_live_history');

        $query = DreamlandAudience::creatorQuery()
            ->select([
                'user.*',
                'reel_count' => new Expression(
                    "(SELECT COUNT(*) FROM {$postTable} p WHERE p.user_id = {$userRef} AND p.type = :reelType AND p.status <> 0)",
                    [':reelType' => $reelType]
                ),
                'live_count' => new Expression(
                    "(SELECT COUNT(*) FROM {$liveTable} ulh WHERE ulh.user_id = {$userRef})"
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
                if (DreamlandCreatorApproval::hasCreatorStatusColumn()) {
                    $query->andWhere(['user.dreamland_creator_status' => DreamlandCreatorApproval::STATUS_APPROVED]);
                }
                break;
            case 'banned':
                $query->andWhere(['user.status' => User::STATUS_INACTIVE]);
                break;
            case 'pending':
                if (DreamlandCreatorApproval::hasCreatorStatusColumn()) {
                    $query->andWhere(['user.dreamland_creator_status' => DreamlandCreatorApproval::STATUS_PENDING]);
                } else {
                    $query->andWhere(['user.role' => User::ROLE_AGENT]);
                    $query->andWhere(['user.status' => User::STATUS_ACTIVE]);
                }
                break;
        }

        return $dataProvider;
    }
}
