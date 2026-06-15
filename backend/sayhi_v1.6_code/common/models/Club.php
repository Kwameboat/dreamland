<?php
namespace common\models;
//use common\models\User;
use common\models\ClubUser;
use common\models\Post;
use common\models\ClubCategory;
use common\models\Setting;
use Yii;

class Club extends \yii\db\ActiveRecord
{
    
    const COMMON_NO = 0;
    const COMMON_YES = 1;
    
    
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_BLOCKED = 9;


    
    const PRIVACY_TYPE_PUBLIC=1;
    const PRIVACY_TYPE_PRIVATE=2;
    
    public $imageFile;
    


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'club';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'id','user_id','category_id','is_chat_room','chat_room_id', 'privacy_type', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            
            [['name','image','description'], 'string'],
            //[['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
            'category_id' => Yii::t('app', 'Category'),
            
            'image' => Yii::t('app', 'image'),
            
            
        ];
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by = Yii::$app->user->identity->id;

        } else {
            $this->updated_at = time();
            $this->updated_by = Yii::$app->user->identity->id;

        }

        return parent::beforeSave($insert);
    }
  

    
    public function getStatusString()
    {
       if($this->status==$this::STATUS_BLOCKED){
           return 'Blocked';
       }else if($this->status==$this::STATUS_ACTIVE){
           return 'Active';    
       }else if($this->status==$this::STATUS_DELETED){
         return 'Deleted';    
       }
    }


    public function getPrivacyTypeString()
    {
       if($this->privacy_type==$this::PRIVACY_TYPE_PUBLIC){
           return 'Public';
       }else if($this->privacy_type==$this::PRIVACY_TYPE_PRIVATE){
           return 'Private';    
       }
    }

    public function getIsChatRoomString()
    {
       if($this->is_chat_room==$this::COMMON_YES){
           return 'Yes';
       }else if($this->is_chat_room==$this::COMMON_NO){
           return 'No';    
       }
    }

    

    

    public function getStatusButton()
    {
        if($this->status==$this::STATUS_ACTIVE){
         //  return 'Active';   
            
            
             return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Active').'</button>';      
            

           
        }else if($this->status==$this::STATUS_DELETED){
            
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Deleted').'</button>'; 
        }else if($this->status==$this::STATUS_BLOCKED){
            
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Blocked').'</button>'; 
        }
       
    }


    public function getStatusDropDownData()
    {
        return array(self::STATUS_ACTIVE => 'Active', self::STATUS_BLOCKED => 'Blocked');
    
    }


    


    public function getImageUrl()
    {
        
        return Yii::$app->fileUpload->getFileUrl(Yii::$app->fileUpload::TYPE_CHAT,$this->image);

        
    }
    

    /**
     * RELEATION START
     */
    
    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);

    }
    public function getClubCategory()
    {
        return $this->hasOne(ClubCategory::className(), ['id' => 'category_id']);

    }

    public function getClubUser()
    {
        return $this->hasMany(ClubUser::className(), ['club_id' => 'id']);

    }


    public function getPost()
    {
        return $this->hasMany(Post::className(), ['club_id' => 'id']);

    }

    public function getTotalClubCount()
    {
        return Club::find()->where(['<>','status',self::STATUS_DELETED])->count();
    }

    public function getLastTweleveMonth()
    {
        $month =  strtotime("+1 month");
        for ($i = 1; $i <= 12; $i++) {
            $months[(int)date("m", $month)] = date("M", $month);
            $month = strtotime('+1 month', $month);
        }
        return $months;
        
    }


    public function getLastTweleveMonthClub()
    {
        
        $totalAds = [];
        $monthArr =[];
        $months = $this->getLastTweleveMonth();
        
        $res= Yii::$app->db->createCommand("SELECT month(from_unixtime(created_at)) as month, count(id) as total_ad FROM club where status!=0 and from_unixtime(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) group by month")->queryAll();

        foreach($months as $key => $month){
            $found_key = array_search($key, array_column($res, 'month'));  
            //echo gettype($found_key), "\n";
            if(is_int($found_key)){
                $totalAd =  $res[$found_key]['total_ad'];
            }else{
                $totalAd = 0;
            }
            //echo $totalAds;
            /*echo '=====================';
            echo '<br>';
            echo $key.'#'.$month;
            echo '<br>';*/

            //print_r($found_key);
            
            $totalAds[]=$totalAd;
           
            $monthArr[]=$month;

        }
        $output=[];

        $output['data'] = $totalAds;
        $output['dataCaption'] = $monthArr;
        return $output;

        
    }

}
