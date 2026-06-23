<?php

namespace backend\models;

use app\models\User;
use common\models\DreamlandAudience;
use common\helpers\DreamlandCreatorApproval;
use Yii;
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
        $query = DreamlandAudience::creatorQuery();

        if ($this->canCountReels()) {
            $db = Yii::$app->db;
            $userTable = $db->quoteTableName(User::tableName());
            $userIdCol = $db->quoteColumnName('id');
            $postTable = $db->quoteTableName('post');
            $liveTable = $db->quoteTableName('user_live_history');
            $userRef = "{$userTable}.{$userIdCol}";

            $query->select([
                User::tableName() . '.*',
                'reel_count' => new Expression(
                    "(SELECT COUNT(*) FROM {$postTable} p WHERE p.user_id = {$userRef} AND p.type = 4 AND p.status <> 0)"
                ),
                'live_count' => new Expression(
                    "(SELECT COUNT(*) FROM {$liveTable} ulh WHERE ulh.user_id = {$userRef})"
                ),
            ]);
        }

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
            'id' => $this->id,
            'status' => $this->status,
        ]);
        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'email', $this->email]);
        $query->andFilterWhere(['like', 'username', $this->username]);

        switch ($this->filter) {
            case 'active':
                $query->andWhere(['status' => User::STATUS_ACTIVE]);
                if (DreamlandAudience::hasUserColumn('dreamland_creator_status')) {
                    $query->andWhere(['dreamland_creator_status' => DreamlandCreatorApproval::STATUS_APPROVED]);
                }
                break;
            case 'banned':
                $query->andWhere(['status' => User::STATUS_INACTIVE]);
                break;
            case 'pending':
                if (DreamlandAudience::hasUserColumn('dreamland_creator_status')) {
                    $query->andWhere([
                        'or',
                        ['dreamland_creator_status' => DreamlandCreatorApproval::STATUS_PENDING],
                        [
                            'and',
                            ['role' => User::ROLE_AGENT],
                            [
                                'or',
                                ['dreamland_creator_status' => DreamlandCreatorApproval::STATUS_NONE],
                                ['dreamland_creator_status' => ''],
                                ['dreamland_creator_status' => null],
                            ],
                        ],
                    ]);
                    $query->andWhere([
                        'not in',
                        'dreamland_creator_status',
                        [
                            DreamlandCreatorApproval::STATUS_APPROVED,
                            DreamlandCreatorApproval::STATUS_REJECTED,
                        ],
                    ]);
                }
                break;
        }

        return $dataProvider;
    }

    private function canCountReels(): bool
    {
        try {
            $schema = Yii::$app->db->schema;
            return $schema->getTableSchema('post', true) !== null
                && $schema->getTableSchema('user_live_history', true) !== null;
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), __METHOD__);
            return false;
        }
    }
}
