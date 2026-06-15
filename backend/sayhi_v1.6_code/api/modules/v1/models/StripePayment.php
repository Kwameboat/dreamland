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

class StripePayment extends Model
{

    public $publishableKey;
    public $secretKey;
    //  public $customerId = 'cus_HttzODkubJFh1S';

    public function init()
    {
        parent::init();

        $modelSetting = new Setting();
        
        $settingResult = $modelSetting->find()->one();
        
        $this->publishableKey = $settingResult->stripe_publishable_key;//  Yii::$app->params['stripePublishableKey'];
        $this->secretKey = $settingResult->stripe_secret_key;
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

    public function getPaymentIntend($options)
    {

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $amountPaid = $options['amount'] * 100;
        $intent = PaymentIntent::create([
            'amount' => $amountPaid,
            'currency' =>  $options['currency'],
            'automatic_payment_methods' => [
                'enabled' => true,
            ]
        ]);

        return $intent->client_secret;

    }

    /*public function getPaymentIntend($amount,$currency,$stripeCustomerId){

    $stripe = new Stripe();
    $stripe->setApiKey($this->secretKey);
    $intent = PaymentIntent::create([
    'customer'=>$stripeCustomerId,
    'amount' => $amount,
    'currency' => $currency,
    'description'=>"payment for ads",
    "payment_method_types[]"=>"card",
    "shipping[name]"=>"Jenny Rosen",
    "shipping[address][line1]"=>"510 Townsend St",
    "shipping[address][postal_code]"=>"98140",
    "shipping[address][city]"=>"San Francisco",
    "shipping[address][state]"=>"CA",
    "shipping[address][country]"=>"US",

    // Verify your integration in this guide by including this parameter
    'metadata' => ['integration_check' => 'accept_a_payment'],
    ]);

    return $intent->client_secret;

    }*/

    public function createCustomer($data)
    {

        $email = $data['email'];
        $name = $data['name'];
        $stripeClient = new StripeClient($this->secretKey);
        $result = $stripeClient->customers->create([
            'description' => $name,
            'email' => $email,
        ]);
        return $result->id;

    }

    public function getEphemeralKey($data)
    {

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $customerId = $data['stripe_customer_id'];
        $stripeVersion = $data['stripe_version'];

        $key = EphemeralKey::create(
            ['customer' => $customerId],
            ['stripe_version' => $stripeVersion]
        );

        return $key;

    }

    /*
    public function withdraw($options){

    $stripe = new Stripe();
    //  $stripe->setApiKey($this->secretKey);

    $stripeClient= new StripeClient($this->secretKey);

    $stripeClient->transfers->create([
    'amount' => 50,
    'currency' => 'inr',
    'destination' => 'acct_1Hwq3oQqX2Zz03K2',
    'transfer_group' => 'ORDER_95',
    ]);

    return  $result->id;

    }*/

    public function createAccount($options)
    {
        $country = $options['country'];
        $email = $options['email'];

        $type = (isset($options['type'])) ? $options['type'] : 'custom';

        // $type    = $options['type'];

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $result = Account::create([
            'country' => $country,
            'type' => $type, //express|custom
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
        ]);

        if (isset($result["id"])) {
            return $result["id"];
        } else {
            return false;
        }

    }

    /**
     * update account
     */

    
     
    public function updateAccountNew($options)
    {
        $accountId = $options["accountId"];

        $dataInput  = $options["data"];
        
      
      
        $dataInput['tos_acceptance']    =   ['date' => time() ,'ip' => $_SERVER['REMOTE_ADDR']]; // Assumes you're not using a proxy
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        try {

            
            $result = Account::update($accountId, $dataInput
            );
                        

            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
           // print_r($e);

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }
    
    /*
     
    public function updateAccount($options)
    {
        $accountId = $options["accountId"];
        //$accountId = 'acct_1I2WHSPC6A8XZFrI';
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $first_name         = $options['first_name'];
        $last_name          = $options['last_name'];
        $phone              = $options['phone'];

        $id_number          = $options['id_number'];
        $ssn_last_4         = $options['ssn_last_4'];
        $email              = $options['email'];

        $dob_day            = $options['dob_day'];
        $dob_month          = $options['dob_month'];
        $dob_year           = $options['dob_year'];
        $address_line1      = $options['address_line1'];
        $address_city       = $options['address_city'];
        $address_state      = $options['address_state'];
        $address_country    = $options['address_country'];
        $address_postal_code = $options['address_postal_code'];


        $business_type ="company"; // company , individual

        try {

            $dataInput=[];
            $dataInputIndividual=[];
            $dataInputCompany=[];

            //$addressInput=[];
            
            $dataInput['business_type']     =   $business_type;
            $dataInput['business_profile']  =   ['mcc' => '1520','product_description' => 'selling products'];
            $dataInput['tos_acceptance']    =   ['date' => time() ,'ip' => $_SERVER['REMOTE_ADDR']]; // Assumes you're not using a proxy
            
            $addressInput = ['line1' => $address_line1, 'city' => $address_city, 'state' => $address_state, 'country' => $address_country, 'postal_code' => $address_postal_code];
            
            if($business_type=='individual'){
                $dataInputIndividual['first_name'] = $first_name;
                $dataInputIndividual['last_name'] = $last_name;
                
                $dataInputIndividual['id_number'] = $id_number;
                if($address_country=='US'){
                    $dataInputIndividual['ssn_last_4'] = $ssn_last_4;
                }
                $dataInputIndividual['phone'] = $phone;
                $dataInputIndividual['email'] = $email;
                $dataInputIndividual['dob'] = ['day' => $dob_day, 'month' => $dob_month, 'year' => $dob_year];
                $dataInputIndividual['address'] =  $addressInput;  
                
                $dataInput['individual'] = $dataInputIndividual;
              

            }else{ // company

                $dataInputCompany['name']='company name';
                $dataInputCompany['phone']=$phone;
                $dataInputCompany['tax_id']='000000000';
                
                $dataInputCompany['address'] =  $addressInput;
                
                $dataInput['company'] = $dataInputCompany;

            }

//            print_r($dataInput);
 //          die;
            
            $result = Account::update($accountId, $dataInput
            );
                        

            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
            print_r($e);

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }*/
    
    public function updateAccount($options)
    {
        $accountId = $options["accountId"];

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $first_name         = $options['first_name'];
        $last_name          = $options['last_name'];
        $phone              = $options['phone'];

        $id_number          = $options['id_number'];
        $ssn_last_4         = $options['ssn_last_4'];
        $email              = $options['email'];

        $dob_day            = $options['dob_day'];
        $dob_month          = $options['dob_month'];
        $dob_year           = $options['dob_year'];
        $address_line1      = $options['address_line1'];
        $address_city       = $options['address_city'];
        $address_state      = $options['address_state'];
        $address_country    = $options['address_country'];
        $address_postal_code = $options['address_postal_code'];

        try {

            $individual=[];
            $individual['first_name'] = $first_name;
            $individual['last_name'] = $last_name;
            $individual['phone'] = $phone;
            $individual['id_number'] = $id_number;
            if($address_country=='US'){
                $individual['ssn_last_4'] = $ssn_last_4;
            }
            
            $individual['email'] = $email;
            $individual['dob'] = ['day' => $dob_day, 'month' => $dob_month, 'year' => $dob_year];
            $individual['address'] =  ['line1' => $address_line1, 'city' => $address_city, 'state' => $address_state, 'country' => $address_country, 'postal_code' => $address_postal_code];
            
            $result = Account::update($accountId,
            [
                'individual' => $individual,
                'business_profile' => ['mcc' => '1520','name'=>'My Store','product_description' => 'selling products'],
                'tos_acceptance' => [
                    'date' => time(),
                    'ip' => $_SERVER['REMOTE_ADDR'], // Assumes you're not using a proxy
                ],
                //'metadata' => ['order_id' => '6735','individual.first_name'=>'bal']]
                ['business_type' => 'individual'],
            ]
            );
                        

            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }


    /**
     *  account external account / bank
     */

    public function createExternalAccount($options)
    {
        $accountId = $options["accountId"];
        $token = $options['token'];
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        try {

            $result = Account::createExternalAccount($accountId,
                [
                    'external_account' => $token,
                ]
            );
            // print_r($result);
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }
    /*
    public function createExternalAccount($options){
    $accountId                  = $options["accountId"];
    $account_holder_name        =   $options['account_holder_name'];
    $account_holder_type        =   $options['account_holder_type'];
    $country                    =   $options['country'];
    $currency                    =   $options['currency'];
    $account_number             =   $options['account_number'];
    $routing_number             =   $options['routing_number'];

    $stripe = new Stripe();
    $stripe->setApiKey($this->secretKey);

    try {

    $result = Account::createExternalAccount($accountId,
    [
    'external_account'=>[
    'object'=>'bank_account',
    'account_holder_name'=>$account_holder_name,
    'account_holder_type'=>$account_holder_type,//individual|company,
    'country'=>$country,
    'currency'=>$currency,
    'account_number'=>$account_number,
    'routing_number'=>$routing_number
    ]
    ]
    );
    // print_r($result);
    $response=[];
    $response['success']=true;
    $response['responseData']=$result;
    return $response;

    } catch (\Exception $e) {

    $response['success']=false;
    $response['message']=$e->getError()->message;
    return $response;
    }

    }*/

    /**
     * update bank account make as defailt
     */

    public function updateExternalAccount($options)
    {
        $accountId = $options["accountId"];
        $id = $options['id'];

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        try {

            $result = Account::updateExternalAccount($accountId, $id,
                [
                    'default_for_currency' => true,
                ]
            );
            // print_r($result);
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }

     /**
     * update bank account make as defailt
     */

    public function retrieveExternalAccount($options)
    {
        $accountId = $options["accountId"];
        $externalAcountId = $options['externalAcountId'];

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        try {

            $result = Account::retrieveExternalAccount($accountId, $externalAcountId,
                [
                ]
            );
            // print_r($result);
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }



    public function accountLink($options)
    {

        $accountId = $options["accountId"];

        //  $accountId='acct_1Hyv6WPK5sivrmuh';

        /* Legacy Stripe client example removed — set STRIPE_SECRET_KEY in env. */
        $stripe = new StripeClient($this->secretKey);
        $result = $stripe->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => 'https://example.com/reauth',
            'return_url' => 'https://example.com/return',
            'type' => 'account_onboarding',
        ]);
        //  print_r($result);
        return $result;

    }

    public function accountRetrieve($options)
    {

        $accountId = $options["accountId"];
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        $result = Account::retrieve($accountId,
            []
        );
        return $result;

    }

    public function uploadFile($options)
    {
        $accountId        = $options["accountId"];
        $purpose          = $options['purpose'];
        $file             = $options['file'];

        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);

        try {
            $result = File::create([
                'purpose' => $purpose,
                'file' => fopen($file, 'r'),
              ], [
                'stripe_account' => $accountId,
              ]
            );

            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
          
          $response['success'] = false;
            $response['message'] = isset($e->getError) ? $e->getError()->message : "uploading error";
            return $response;
        }

    }
    /**
     * attach document file with user account for verification
     */

    public function attachVerificationFile($options)
    {
        $accountId       = $options["accountId"];
       // $side            = $options['side'];
        $front          = $options['front'];
        $back          = $options['back'];


        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);
        try {
            $documents= [];
            $documents["front"] = $front;
            $documents["back"] = $back;
            /*
            if($side=='front'){
              $documents["front"] = $fileId;
            }else if($side=='back'){ 
              
            }*/

            $result = $this->accountRetrieve(["accountId"=>$accountId]);
           // $resultProfile  = $resultProfile[] 
            
            if($result['business_type']=='individual'){ 
              
              $personId = $result['individual']["id"];

              $result = Account::updatePerson($accountId, $personId,
              [
                'verification' => [
                  'document' =>  $documents
                ],
              ]
            );
             
              
            
            }else{ /// for company



            }
           
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }


    /**
     * create person
     */

    public function createPerson($options)
    {
        $accountId = $options["accountId"];
        $dataInput  = $options["data"];
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);
        try {
            $result = Account::createPerson($accountId, $dataInput
            );
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
           // print_r($e);

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }
    }
    
    /**
     * all person
     */

    public function allPersons($options)
    {
        $accountId = $options["accountId"];
        
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);
        try {
            $result = Account::allPersons($accountId,[]
            );
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
           // print_r($e);

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }
    }


    /**
     * update person
     */

    public function updatePerson($options)
    {
        $accountId = $options["accountId"];
        $dataInput  = $options["data"];
        $personeId = $dataInput["person_id"];
        unset($dataInput["person_id"]);
        $stripe = new Stripe();
        $stripe->setApiKey($this->secretKey);
        try {
            $result = Account::updatePerson($accountId,$personeId, $dataInput
            );
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
           // print_r($e);

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }
    }
    

    

    public function transfer($options)
    {
        $amount = $options['amount'];
        $currency = $options['currency'];
        $destination = $options['destination'];

        $stripe = new StripeClient($this->secretKey);
        $amountPay = $amount * 100;

        try {

            $result = $stripe->transfers->create([
                'amount' => $amountPay,
                'currency' => $currency,
                'destination' => $destination,
                'transfer_group' => 'ORDER_95',
            ]);

            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
            /*echo 'Status is:' . $e->getHttpStatus() . '\n';
            echo 'Type is:' . $e->getError()->type . '\n';
            echo 'Code is:' . $e->getError()->code . '\n';
            // param is '' in this case
            echo 'Param is:' . $e->getError()->param . '\n';
            echo 'Message is:' . $e->getError()->message . '\n';*/

            $response['success'] = false;
            $response['message'] = $e->getError()->message;
            return $response;
        }

    }



    public function payoutList($options)
    {
        $accountId = $options['accountId'];
        $stripe = new StripeClient($this->secretKey);
        try {
            $result = $stripe->payouts->all([
                //'limit'=>50
            ],
            [
                'stripe_account' => $accountId
            ]);
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
            /*echo 'Status is:' . $e->getHttpStatus() . '\n';
            echo 'Type is:' . $e->getError()->type . '\n';
            echo 'Code is:' . $e->getError()->code . '\n';
            // param is '' in this case
            echo 'Param is:' . $e->getError()->param . '\n';
            echo 'Message is:' . $e->getError()->message . '\n';*/
            
            $response['success'] = false;
            $response['message'] = isset($e->getError) ? $e->getError()->message : "process failed";
            return $response;
        }

    }



    public function payoutRetrieve($options)
    {
        $payoutId = $options['payoutId'];
        $accountId='acct_1HywNHPQsSsui6Us';
        $stripe = new StripeClient($this->secretKey);
        try {
            $result = $stripe->payouts->retrieve($payoutId,
                [],
                [
                    'stripe_account' => $accountId
                ]
            );
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
            /*echo 'Status is:' . $e->getHttpStatus() . '\n';
            echo 'Type is:' . $e->getError()->type . '\n';
            echo 'Code is:' . $e->getError()->code . '\n';
            // param is '' in this case
            echo 'Param is:' . $e->getError()->param . '\n';
            echo 'Message is:' . $e->getError()->message . '\n';*/
            print_r($e);
            die;
            
            $response['success'] = false;
            $response['message'] = isset($e->getError) ? $e->getError()->message : "process failed";
            return $response;
        }

    }


    
    public function paymentIntendRetrieve($options)
    {
        $paymentId = $options['paymentId'];
        $stripe = new StripeClient($this->secretKey);
        try {
            $result = $stripe->paymentIntents->retrieve($paymentId,
                []
            );
            $response = [];
            $response['success'] = true;
            $response['responseData'] = $result;
            return $response;

        } catch (\Exception $e) {
            /*echo 'Status is:' . $e->getHttpStatus() . '\n';
            echo 'Type is:' . $e->getError()->type . '\n';
            echo 'Code is:' . $e->getError()->code . '\n';
            // param is '' in this case
            echo 'Param is:' . $e->getError()->param . '\n';
            echo 'Message is:' . $e->getError()->message . '\n';*/
            print_r($e);
            die;
            
            $response['success'] = false;
            $response['message'] = isset($e->getError) ? $e->getError()->message : "process failed";
            return $response;
        }

    }





}
