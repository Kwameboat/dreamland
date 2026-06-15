<?php

namespace api\modules\v1\models;

use Yii;
use api\modules\v1\models\Post;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use api\modules\v1\models\PickleballMatch;

class PickleballMatchSearch extends PickleballMatch
{
    public $status_type;
    public $is_my_match;
    public $match_result_type;
    public $day_range;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['match_type', 'status_type', 'is_my_match', 'match_result_type', 'day_range'], 'integer'],

            [['status_type', 'is_my_match', 'match_result_type', 'day_range'], 'safe'],
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


    /**
     * search post
     */

    public function searchMatch($params)
    {
        $userId = Yii::$app->user->identity->id;
        //  $conditionTime =time();
        $this->load($params, '');
        $matchStatusArr = [];
        if ($this->status_type == 1) { //ongoin
            $matchStatusArr[] = PickleballMatch::STATUS_ACTIVE;

        } else if ($this->status_type == 2) { //completed
            $matchStatusArr[] = PickleballMatch::STATUS_COMPLETED;
            $matchStatusArr[] = PickleballMatch::STATUS_CANCELLED;
        }

        $query = PickleballMatch::find()
            ->where(['<>', 'pickleball_match.status', PickleballMatch::STATUS_DELETED])
            ->joinWith([
                'matchTeam.teamPlayer.playerDetail' => function ($query) {
                    $query->select(['name', 'username', 'email', 'image', 'id', 'is_chat_user_online', 'chat_last_time_online', 'location', 'latitude', 'longitude']);
                }
            ]);

        if (count($matchStatusArr) > 0) {
            $query->andWhere(['pickleball_match.status' => $matchStatusArr]);
        }
        if ($this->is_my_match) {
            $query->andWhere(['pickleball_team_player.player_id' => $userId]);
            if ($this->match_result_type) {

                $query->andWhere('pickleball_team_player.team_id = pickleball_match_team.id  and  pickleball_match_team.winner_status=' . $this->match_result_type);

            }
        }
        if ($this->day_range) {
            $timeRange =   strtotime("-$this->day_range days");
            $query->andWhere(['>','start_time',$timeRange]);

        }
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

        $query->andFilterWhere([
            'pickleball_match.match_type' => $this->match_type,

        ]);
        return $dataProvider;
    }

}
