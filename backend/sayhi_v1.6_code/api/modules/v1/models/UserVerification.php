<?php
namespace api\modules\v1\models;
use Yii;
//use api\modules\v1\models\GiftHistory;



class UserVerification extends \yii\db\ActiveRecord
{
    
    const STATUS_PENDING=1;
    const STATUS_CANCELLED=2;
    const STATUS_REJECTED=3;
    const STATUS_ACCEPTED = 10;
    
    
    
    public  $document;
    
    
  
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_verification';
    }

    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           [[ 'user_message','admin_message','document_type'], 'string'],
           [['id','user_id', 'created_at','created_by', 'updated_by'], 'integer'],
           [['updated_by', 'updated_at','document'], 'safe'],
           ['status', 'in', 'range' => [0,9,10]],
           [['document' ], 'required','on'=>'create'],
           [['id' ], 'required','on'=>'cancel'],
           
            //[['competition_id','gallary' ], 'required','on'=>'competitionImage'],

            
            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
        
            
        ];
    }

    
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by  =   Yii::$app->user->identity->id;
            $this->user_id       =   Yii::$app->user->identity->id;
          
        }else{

           
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;

        }
        return parent::beforeSave($insert);
    }

    public function fields()
    {
        $fields = parent::fields();
      //  $fields[] = "imageUrl"; // now postGallary table used

          $fields[] = "verificationDocument";
       
        

        
       
        return $fields;
    }
    
    public function extraFields()
    {
      //  return ['user','clubDetail','giftSummary'];
    }
    
   
    /*public function getStatusString()
    {
        if($this->status==$this::STATUS_ACCEPTED){
           return 'Accepted';    
        }else if($this->status==$this::STATUS_DELETED){
            return 'Deleted';    
        }else if($this->status==$this::STATUS_BLOCKED){
            return 'Blocked';    
        }
       
    }*/
   
    
    

       
    
  
    /**
     * RELEATION START
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }

   
    public function getVerificationDocument(){
        return $this->hasMany(UserVerificationDocument::className(), ['user_verification_id'=>'id']);
     }
     

     

  

    
}
