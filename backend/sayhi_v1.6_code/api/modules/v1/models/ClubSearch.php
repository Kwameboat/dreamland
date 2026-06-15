<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Club;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class ClubSearch extends Club
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['category_id','user_id','my_joined_club'], 'integer'],
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

    public function searchClub($params)
    {
        $userId   =  Yii::$app->user->identity->id;


        $this->load($params,'');

        $myJoinedClub  =(int)$this->my_joined_club;
        
        $query = Club::find()
        ->where(['club.status'=>Club::STATUS_ACTIVE])
        
        ->joinWith(['createdByUser' => function($query){
            $query->select(['id','name','username','email','bio','description','image','country_code','phone','country','city','sex','is_chat_user_online','chat_last_time_online']);
        }])
        ->orderBy(['club.id'=>SORT_ASC]);

     


        if($myJoinedClub){ /// will list my joined club
            
            $query->joinWith('clubUser');
            $query->andWhere(['club_user.user_id'=>$userId])
            ->andWhere(['<>','club.user_id',$userId]);

        }else{ /// if my join club not then only show public club but if user is club owner then list all (public and privete)

            if($this->user_id != $userId ){
              $query->andWhere(['club.privacy_type'=>Club::PRIVACY_TYPE_PUBLIC]);
            }

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
        // grid filtering conditions
         $query->andFilterWhere([
            'club.category_id' => $this->category_id,
            'club.user_id' => $this->user_id,
        ]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(['or',
            ['like', 'club.name', $this->name]]
            //['like', 'artist', $this->name]]
        );

        return $dataProvider;




    }



   
    
}
