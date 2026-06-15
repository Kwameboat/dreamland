<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\PickleballCourt;



use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class PickleballCourtSearch extends PickleballCourt
{
    public $is_popular;
    /**
     * {@inheritdoc}
     */
    
    public function rules()
    {
        return [
            [['name','address','latitude','longitude'], 'string'],
            [['type','is_popular'], 'integer'],
            [['is_popular'], 'safe'],
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



    public function search($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');
    
        $query = PickleballCourt::find()
        ->select(['pickleball_court.*'])
        ->addSelect(['count(pickleball_match.id) as totalMatch'])
        ->where(['pickleball_court.status'=>PickleballCourt::STATUS_ACTIVE])
        ->joinWith(['pickleballMatch']);
        
        $query->groupBy(['pickleball_court.id']);
        if($this->is_popular){
            $query->orderBy(['totalMatch'=>SORT_DESC]);
        }else{
            $query->orderBy(['pickleball_court.name'=>SORT_ASC]);
        }
        $displayRadius=100;
        if($this->latitude && $this->longitude){
            $query->addSelect('(
                    3959 * acos (
                    cos ( radians('.$this->latitude.') )
                    * cos( radians( pickleball_court.latitude ) )
                    * cos( radians( pickleball_court.longitude ) - radians('.$this->longitude.') )
                    + sin ( radians('.$this->latitude.') )
                    * sin( radians( pickleball_court.latitude ) )
                )
            ) AS distance');
            $query->having(['<', 'distance', $displayRadius ]);
          //  $query->orderBy(['distance'=>SORT_ASC]);
          
        }



        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
         $query->andFilterWhere([
            'pickleball_court.type' => $this->type
            
        ]);
        $query->andFilterWhere(
            [
                'or',
                    ['like', 'pickleball_court.name', $this->name],
                    ['like', 'pickleball_court.address', $this->address]
            ]
        );
        //return $query->asArray()->all();
        return $dataProvider;

    }



    
}
