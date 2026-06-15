<?php
namespace common\models;
use Yii;
use yii\helpers\ArrayHelper;
use common\models\User;

class WithdrawalPayment extends \yii\db\ActiveRecord
{
    
    const  STATUS_DELETED                 = 0;
    const  STATUS_PENDING                 = 1;
    const  STATUS_REJECTED                = 2;
    const  STATUS_ACCEPTED                = 10;
    
   
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'withdrawal_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','created_at','created_by','updated_at','updated_by','status'], 'integer'],
            [['amount'], 'number'],
            [['transaction_id','description'], 'string'],
            [['amount'], 'required','on'=>'create']
            

            

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'amount' => Yii::t('app', 'Amount'),
            'transaction_id' => Yii::t('app', 'Transaction Id'),
            'description' => Yii::t('app', 'Description'),
            'created_at'=> Yii::t('app', 'Created At'),
        ];
    }
   
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
            $this->created_by =   Yii::$app->user->identity->id;
          
        }else{
            $this->updated_at = time();
            $this->updated_by =   Yii::$app->user->identity->id;
        }

        
        return parent::beforeSave($insert);
    }


    
    public function getStatusButton()
    {
        
        if($this->status==$this::STATUS_PENDING){
            return'<button type="button" class="btn btn-sm pending_btn">'.Yii::t('app','Pending').'</button>'; 
        }else if($this->status==$this::STATUS_REJECTED){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Rejected').'</button>'; 
        }else if($this->status==$this::STATUS_ACCEPTED){
            return'<button type="button" class="btn btn-sm active_btn">'.Yii::t('app','Completed').'</button>';      
        }else if($this->status==$this::STATUS_DELETED){
            return'<button type="button" class="btn btn-sm expired_btn">'.Yii::t('app','Deleted').'</button>'; 
        }
       
    }





    
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id'=>'user_id']);
        
    }


    


}
