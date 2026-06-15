<?php
namespace api\modules\v1\models;
use Yii;
use yii\helpers\ArrayHelper;

//use api\modules\v1\models\Package;
//use api\modules\v1\models\Ad;

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
            /*
            'id' => 'ID',
            'user_id' => Yii::t('app', 'User'),
            'package_id' => Yii::t('app', 'Package'),
            'transaction_type' => Yii::t('app', 'Transaction Type'),
            'payment_type' => Yii::t('app', 'Payment Type'),
            'payment_mode' => Yii::t('app', 'Payment Mode'),
            'created_at'=> Yii::t('app', 'Created At'),*/
            
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

    


}
