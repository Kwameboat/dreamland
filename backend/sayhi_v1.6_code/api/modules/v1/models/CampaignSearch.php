<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Campaign;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class CampaignSearch extends Campaign
{
    
    /**
     * {@inheritdoc}
     */
    public $my_joined_club;
    public function rules()
    {
        return [
            [['title'], 'string'],
            [['category_id','campaigner_id','campaign_for_id','created_by','updated_by'], 'integer'],
          //  [['title'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
       
          return Model::scenarios();
    }


     /**
     * search story post
     */

    public function search($params)
    {
        $currentdate = time();
        $this->load($params,'');
        $query = Campaign::find()
        ->where(['campaign.status'=>Campaign::STATUS_ACTIVE]);
        if(@$params['is_active'] ==Campaign::TYPE_RUNNING_CAMPAIGN){
            $query->andWhere(['<=', 'start_date',$currentdate])->andWhere(['>=', 'end_date',$currentdate]);
        }
        if(@$params['is_active'] ==Campaign::TYPE_EXPIRED_CAMPAIGN){
            $query->andWhere(['<', 'end_date',$currentdate]);
        }
        
        $query->orderBy(['campaign.id'=>SORT_DESC]);

     
        // 
        // ->orderBy(['campaign.id'=>SORT_DESC]);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
    //    print_r($dataProvider);
    //    exit;
        if (!$this->validate()) {
          
            return $dataProvider;
        }
       
         $query->andFilterWhere([
            'campaign.category_id' => $this->category_id
            
        ]);

       
        $query->andFilterWhere([
            'campaign.campaigner_id' => $this->campaigner_id
          
            
        ]);

        
        $query->andFilterWhere([
            'campaign.campaign_for_id' => $this->campaign_for_id         
        ]);

        $query->andFilterWhere([
            'campaign.created_by' => $this->created_by         
        ]);
        $query->andFilterWhere([
            'campaign.updated_by' => $this->updated_by         
        ]);
        
      
        $query->andFilterWhere(
            [
                'or',
                    ['like', 'campaign.title', $this->title],
                    ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;

    }

    // search

    public function CampaignMyFavorite($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

        $query = Campaign::find()
        ->where(['campaign.status'=>Campaign::STATUS_ACTIVE])

        ->joinWith('campaignMyFavorite')
        ->andWhere(['campaign_favorite.user_id'=>$userId])
        ->orderBy(['campaign.title'=>SORT_ASC]);

       

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);
        
        if (!$this->validate()) {
           
            return $dataProvider;
        }
      
         $query->andFilterWhere([
            'campaign.category_id' => $this->category_id
            
        ]);
      

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'campaign.title', $this->title],
                    ['like', 'description', $this->description]
            ]
        );

        return $dataProvider;




    }

   


    


    
    
    

    
}
