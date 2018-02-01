<?php 

namespace App;

use DB;
use App\Libraries\AfricasTalkingGateway as Gateway;

class SmsHandler{

    /*
    * @var api key
    */
    protected $apiKey;

    /*
    * @var username
    */
    protected $username;

    /*
    * @var source
    */
    protected $source;


    /**
     * Constructor for smshandler
     *
     * @param api key     $apiKey
     * @param username      $username
     * @param source      $source
     * @return $gateway object for sending sms
     */
    public function __construct($username = null, $apiKey = null, $source = null)
    {
        if($username == null && $apiKey == null) {
            $settings = DB::table('bulk_sms_settings')->first();
            if($username == null ) {
                $this->username = $settings->username;
            }
            if($apiKey == null ) {
                $this->apiKey = $settings->api_key;
            }
            if($source == null ) {
                $this->source = $settings->code;
            }
        //sms settings
        }
        else {
            $this->username   = $username;
            $this->apiKey     = $apiKey;
            $this->source     = $source;
        }
    }

    public function sendMessage($phone, $message)
    { 
        if($phone != null && strlen($phone) >= 9){
            $phone = ltrim($phone, '0');
            $recepient = "+254".$phone;
            $gateway = new Gateway($this->username, $this->apiKey);
            $result = $gateway->sendMessage($recepient, $message, $this->source);
        }
    }
}
