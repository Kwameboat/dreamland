<?php
namespace api\modules\v1\models;

use Stripe\Account;
use Stripe\EphemeralKey;
use Stripe\Exception;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\File;
use Yii;
use yii\base\Model;
use api\modules\v1\models\Setting;
use Braintree;

class PaypalPayment extends Model
{

    public $gateway;
  //  public $secretKey;
    //  public $customerId = 'cus_HttzODkubJFh1S';

    public function init()
    {
        parent::init();

        $modelSetting = new Setting();
        
        $settingResult = $modelSetting->find()->one();
        
        
        

        //environment : production / sandbox
        $environment =(Yii::$app->params['siteMode']==1)? 'production':'sandbox';

        $merchantId = $settingResult->paypal_merchant_id;
        $publicKey = $settingResult->paypal_public_key;
        $privateKey = $settingResult->paypal_private_key;
        
        $this->gateway = new Braintree\Gateway([
            'environment' => $environment,
            'merchantId' => $merchantId,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey
        ]);
    }
    /*

    public function getPaymentIntend($options)
    {

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $amountPaid = $options['amount'] * 100;
        $intent = PaymentIntent::create([
            'customer' => $options['stripeCustomerId'],
            'amount' => $amountPaid,
            'currency' => $options['currency'],
            'description' => $options['description'],
            "payment_method_types[]" => $options['payment_method_types'],
            "shipping[name]" => $options['shipping_name'],
            "shipping[address][line1]" => $options['shipping_address_line1'],
            "shipping[address][postal_code]" => $options['shipping_address_postal_code'],
            "shipping[address][city]" => $options['shipping_address_city'],
            "shipping[address][state]" => $options['shipping_address_state'],
            "shipping[address][country]" => $options['shipping_address_country'],

            // Verify your integration in this guide by including this parameter
            'metadata' => ['integration_check' => 'accept_a_payment'],
        ]);

        return $intent->client_secret;

    }*/

    public function getClientToken()
    {

        return  $clientToken = $this->gateway->clientToken()->generate();
    
    }
    public function getMakePayment($data)
    {


        

        $result =  $this->gateway->transaction()->sale([
            'amount' => $data['amount'],
            'paymentMethodNonce' => $data['paymentMethodNonce'],
            'deviceData' => $data['deviceData'],
            'options' => [
              'submitForSettlement' => True
            ]
          ]);
          
          if ($result->success) {
              $paymentId =$result->transaction->id;

             

               //$transaction = $this->gateway->transaction()->find($result->transaction->id);



              // print_r(@$result->transaction->paypal[0]);



              // print_r($result->transaction);
             ///die;

             //  $paymentId = $result->transaction['paypal']['paymentId'];
                $response['status']='success';
                $response['paymentId']=$paymentId;
                return $response;
                
                //print_r($result->transaction['paypal']['paymentId']);
            // See $result->transaction for details
          } else {
            $response['status']='failed';
            return $response;

            // Handle errors
          }
    
    }






}
