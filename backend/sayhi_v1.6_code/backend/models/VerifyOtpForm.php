<?php
namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class VerifyOtpForm extends Model
{
    public $otp;
    

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['otp'], 'required'],
            
            ['otp', 'number'],
            
            
        ];
    }

    


}
