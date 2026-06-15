<?php
namespace api\modules\v1\models;
use Yii;
use api\modules\v1\models\Event;
use api\modules\v1\models\EventTicketBooking;
//use api\modules\v1\models\GiftHistory;


use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class EventSearch extends Event
{
    
    /**
     * {@inheritdoc}
     */

     public $current_status;
    
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['category_id','current_status','organisor_id','status'], 'integer'],
            [['current_status','latitude','longitude'], 'safe'],
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

        $currentStatus = $this->current_status;
        $currentTime = time();


        $query = Event::find()
        ->where(['event.status'=>Event::STATUS_ACTIVE]);
        //->orderBy(['gift.name'=>SORT_ASC]);

        $query->andwhere(['>','event.end_date',$currentTime]);
        
        /*if($currentStatus==Event::CURRENT_STATUS_UPCOMING){
            $query->andwhere(['>','event.start_date',$currentTime]);
        }else if($currentStatus==Event::CURRENT_STATUS_ACTIVE){
            $query->andwhere(['<','event.start_date',$currentTime]);
            $query->andwhere(['>','event.end_date',$currentTime]);
            
        }else if($currentStatus==Event::CURRENT_STATUS_COMPLETED){
            $query->andwhere(['<','event.end_date',$currentTime]);
        }*/

        
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
            'event.category_id' => $this->category_id
            
        ]);
        $query->andFilterWhere([
            'event.organisor_id' => $this->organisor_id
            
        ]);
        
       // $query->andFilterWhere(['>=', 'event.latitude',$this->latitude])->andFilterWhere(['<=', 'event.longitude',$this->longitude]);
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'event.name', $this->name],
                    ['like', 'event.description', $this->name]
            ]
        );

        return $dataProvider;




    }
    
    public function searchMyEvent($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');
        $currentStatus = $this->current_status;
        
        $query = Event::find()
        ->where(['<>','event.status',Event::STATUS_DELETED]);
        $query->andwhere(['event.created_by'=>$userId]);

        $currentTime = time();


        if($currentStatus==Event::CURRENT_STATUS_UPCOMING){
            $query->andwhere(['event.status'=>Event::STATUS_ACTIVE]);
            $query->andwhere(['>','event.start_date',$currentTime]);
        }else if($currentStatus==Event::CURRENT_STATUS_ACTIVE){
            $query->andwhere(['event.status'=>Event::STATUS_ACTIVE]);
            $query->andwhere(['<','event.start_date',$currentTime]);
            $query->andwhere(['>','event.end_date',$currentTime]);
            
        }else if($currentStatus==Event::CURRENT_STATUS_COMPLETED){
            $query->andWhere(
                [
                    'or',

                    ['<','event.end_date',$currentTime],
                    ['status'=>[Event::STATUS_DEACTIVE]],
                   
                ]
            );

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
            'event.category_id' => $this->category_id
            
        ]);
        $query->andFilterWhere([
            'event.status' => $this->status
            
        ]);
       
        $query->andFilterWhere(
            [
                'or',
                    ['like', 'event.name', $this->name],
                    ['like', 'event.description', $this->name]
            ]
        );
       return $dataProvider;
    }
   

    public function searchMyBookedEvent($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

        $currentStatus = $this->current_status;

        $currentTime = time();


        $query = EventTicketBooking::find()
      
        //->where(['event_ticket_booking.user_id'=>$userId])
        ->where(
            [
                'or',
                    ['event_ticket_booking.user_id'=>$userId],
                    ['event_ticket_booking.gifted_to'=>$userId]
            ]
        )

         ->joinWith('event')
         /*->joinWith(['giftedToUser' => function($query) {
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }])*/
        ->joinWith(['giftedByUser' => function($query) {
            $query->select(['name','username','email','image','id','is_chat_user_online','chat_last_time_online','location','latitude','longitude']);
        }]);
        //->orderBy(['gift.name'=>SORT_ASC]);

       // $query->andwhere(['>','event.end_date',$currentTime]);
       // $query->andwhere(['<>','event_ticket_booking.status',EventTicketBooking::STATUS_CANCELLED]);

        if($currentStatus==Event::CURRENT_STATUS_UPCOMING){
            $query->andwhere(['>','event.start_date',$currentTime]);
            $query->andwhere(['<>','event_ticket_booking.status',EventTicketBooking::STATUS_CANCELLED]);
        }else if($currentStatus==Event::CURRENT_STATUS_COMPLETED){
            $query->andwhere(['<','event.start_date',$currentTime]);
            $query->andwhere(['<>','event_ticket_booking.status',EventTicketBooking::STATUS_CANCELLED]);
            
        }else if($currentStatus==Event::CURRENT_STATUS_CANCELLED){
            //$query->andwhere(['<','event.start_date',$currentTime]);
            $query->andwhere(['event_ticket_booking.status'=>EventTicketBooking::STATUS_CANCELLED]);
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
          //  'event_ticket_booking.user_id' => $userId,
            
            'event.category_id' => $this->category_id
            
        ]);

        
        //$query->andFilterWhere(['like', 'name', $this->name]);
        //$query->andFilterWhere(['like', 'artist', $this->name]);

        $query->andFilterWhere(
            [
                'or',
                    ['like', 'event.name', $this->name],
                    ['like', 'event.description', $this->name]
            ]
        );

        return $dataProvider;




    }

    /*
    public function searchPopular($params)
    {
        $userId   =  Yii::$app->user->identity->id;
        $this->load($params,'');

    
        
        $query = Gift::find()
        ->select(['gift.*','count(gift_history.id) as totalUsedGift'])
        ->where(['gift.status'=>LiveTv::STATUS_ACTIVE])
        ->joinWith('giftHistory')
        ->groupBy(['gift.id'])
        ->orderBy(['totalUsedGift'=>SORT_DESC])
        ->limit(20);

        
       

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
            
        ]);
        
      //  $this->setAttributes($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
     

        return $dataProvider;




    }
    */



    
}
