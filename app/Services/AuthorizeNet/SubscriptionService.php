<?php

namespace App\Services\AuthorizeNet;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Models\AnetSubscriptions;
use App\Models\AnetApiLog;
use App\Models\Invoice;
use App\Models\BillTo;


class SubscriptionService
{
    private $merchantAuthentication;
    private $referenceId;
    private $organizationId = null;
    private $programId = null;

    public function __construct() 
    {
        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName( env('AUTHORIZENET_API_LOGIN_ID') );
        $this->merchantAuthentication->setTransactionKey( env('AUTHORIZENET_TRANSACTION_KEY') );

        $this->referenceId = 'ref' . time();
    }

    private function aNetUrl()
    {
        if ( env('APP_ENV') == 'production' )
        {
            return \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        }            
            return \net\authorize\api\constants\ANetEnvironment::SANDBOX;    
    }

    public function subscribe($details, $organizationId, $programId)
    {

        $this->organizationId = $organizationId;
        $this->programId = $programId;
        // Optional Create order information
        //$order = new AnetAPI\OrderType();
        //$order->setInvoiceNumber("101017"); //This needs to be unique
        //$order->setDescription("Golf Shirts");

        $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
        $interval->setLength( $details['charge_interval_in_months'] ); //For a unit of days, use an integer between 7 and 365, inclusive. For a unit of months, use an integer between 1 and 12, inclusive.
        $interval->setUnit("months"); //Either days or months

        $paymentSchedule = new AnetAPI\PaymentScheduleType();
        $paymentSchedule->setInterval($interval);
        $paymentSchedule->setStartDate( $details['subscription_first_charge_date'] );
        $paymentSchedule->setTotalOccurrences("9999"); //to create an ongoing subscription without an end date, set totalOccurrences to "9999".
        //$paymentSchedule->setTrialOccurrences("1");

        if ( $details['payment_type'] == 'creditcard' )
        {
            // When paying by card
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber(     $details['card_number'] );
            $creditCard->setExpirationDate( $details['expiration_date'] );
            $creditCard->setCardCode(       $details['card_code'] );

            $payment = new AnetAPI\PaymentType();
            $payment->setCreditCard($creditCard);
        } else {
            //When paying by bank debit
            $bankAccount = new AnetAPI\BankAccountType();
            $bankAccount->setEcheckType('WEB');

            $bankAccount->setAccountType(   $details['account_type'] );  //Either checking, savings, or businessChecking.            
            $bankAccount->setRoutingNumber( $details['routing_number'] ); 
            $bankAccount->setAccountNumber( $details['account_number'] ); 
            $bankAccount->setNameOnAccount( $details['name_on_account'] ); 
            $bankAccount->setBankName(      $details['bank_name'] ); 

            $payment= new AnetAPI\PaymentType();
            $payment->setBankAccount($bankAccount);
        }

        $billTo = new AnetAPI\NameAndAddressType();
        $billTo->setFirstName($details['first_name'] );
        $billTo->setLastName( $details['last_name'] );

        if ( isset($details['company']) )
            $billTo->setCompany( $details['company'] );
        if ( isset($details['address']) )
            $billTo->setAddress( $details['address'] );
        if ( isset($details['city']) )
            $billTo->setCity( $details['city'] );
        if ( isset($details['state']) )
            $billTo->setState( $details['state'] );
        if ( isset($details['zip']) )
            $billTo->setZip( $details['zip'] );
        if ( isset($details['country']) )
            $billTo->setCountry( $details['country'] );

        //Update Last Bill To
        BillTo::saveLastUsed( $organizationId, $programId, $details );

        // Subscription Type Info
        $subscription = new AnetAPI\ARBSubscriptionType();
        $subscription->setName("Fasteezy Subscription");
        $subscription->setPaymentSchedule($paymentSchedule);
        $subscription->setAmount( $details['amount'] );        
        $subscription->setPayment($payment);
        $subscription->setBillTo($billTo);

        //$subscription->setTrialAmount("0.00");

        //TRY WITHOUT
        //$subscription->setOrder($order);

        $request = new AnetAPI\ARBCreateSubscriptionRequest();
        $request->setmerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($this->referenceId);
        $request->setSubscription($subscription);
        $controller = new AnetController\ARBCreateSubscriptionController($request);

        $response = $controller->executeWithApiResponse( $this->aNetUrl() );

        //We do not want to store card data in the DB
        $request = json_decode(json_encode($request), true);
        unset( $request['subscription']['payment']);

        //Save request and response to log
        $log = AnetApiLog::create([
            'organization_id' => $this->organizationId,
            'program_id' => $this->programId,
            'url' => $this->aNetUrl(),
            'request' =>  json_encode($request),
            'response' => json_encode($response),
        ]);

        
        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) 
        {            
            $data['subscribed'] = true;
            $data['status'] = "ok";
            $data['subscriptionId'] = $response->getSubscriptionId();

            $aNetSubscription = AnetSubscriptions::create([
                'organization_id' => $this->organizationId,
                'program_id' => $this->programId,
                'subscription_id' => $data['subscriptionId'],
                'amount' =>         $details['amount'],
                'charge_interval_in_months' =>      $details['charge_interval_in_months'],
                'subscription_first_charge_date' => $details['subscription_first_charge_date'],
                'subscription_next_charge_date' => $details['subscription_next_charge_date'],
                'is_active' => 1
            ]);

        } else {
            $errorMessages = $response->getMessages()->getMessage();
            
            $data['subscribed'] = false;
            $data['status'] = 'error';
            $data['error'] = $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText();
        }

        //Save subscription data to DB
        // $details['charge_amount'],  $details['charge_interval_in_months'],  $details['subscription_first_charge_date'], $data['subscriptionId']
        return $data;
    }

    public function unsubscribe($subscriptionId, $organizationId, $programId)
    {
        $this->organizationId = $organizationId;
        $this->programId = $programId;
        
        $request = new AnetAPI\ARBCancelSubscriptionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($this->referenceId);
        $request->setSubscriptionId($subscriptionId);

        $controller = new AnetController\ARBCancelSubscriptionController($request);

        $response = $controller->executeWithApiResponse( $this->aNetUrl() );

        //Save request and response to log
        $log = AnetApiLog::create([
            'organization_id' => $this->organizationId,
            'program_id' => $this->programId,
            'url' => $this->aNetUrl(),
            'request' =>  json_encode($request),
            'response' => json_encode($response),
        ]);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok"))
        {
            $data['unsubscribed'] = true;
            $data['status'] = "ok";            
        }
        else
        {
            $errorMessages = $response->getMessages()->getMessage();

            $data['unsubscribed'] = false;
            $data['status'] = "error";
            $data['error'] = $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText();
        }

        return $data;
        
    }


    public function setSubscriptionType(&$details)
    {
        $date_start = new \DateTime('+1 month');
        $details['subscription_first_charge_date'] = new \DateTime($date_start->format('Y-m-d') . ' 10:00:00');
        $details['subscription_next_charge_date']  = new \DateTime($date_start->format('Y-m-d') . ' 10:00:00'); 


        if ($details['subscription_type'] == 'monthly')
        {
            $details['charge_interval_in_months'] = 1;
            $details['amount'] = 9.99;
        } else {
            $details['charge_interval_in_months'] = 12;
            $details['amount'] = 99.00;
        }
    }

    public function mustChargeNow($previous_subscription)
    {
        if ( $previous_subscription )
        {
            $today = new \DateTime();                        
            $subscription_next_charge_date  = new \DateTime($previous_subscription->subscription_next_charge_date);
            
            return $today > $subscription_next_charge_date ? true : false;            
        }

        return false;
    }

    //Check if there is an active subscription that has a next bill date in the future
    public function isActiveSubscription($subscription)
    {
        if ( $subscription != false )
        {
            $today = new \DateTime();
            $subscription_next_charge_date = new \DateTime($subscription->subscription_next_charge_date); 

            return $today < $subscription_next_charge_date ? true : false;            
        }

        return false;
    }

    public function getActiveSubscription($organizationId, $programId)
    {
        //Check if valid subscription exists
        $activeSubscription = AnetSubscriptions::where('organization_id', $organizationId)
                                                ->where('program_id', $programId)
                                                ->whereNull('cancelled')
                                                ->orderBy('subscription_next_charge_date', 'desc')
                                                ->first();
        if ( !is_null($activeSubscription) )
        {
            return $activeSubscription;
        }

        return false;
    }

    public function getPreviousSubscription($organizationId, $programId)
    {
        //Check if valid subscription exists
        $previousSubscription = AnetSubscriptions::where('organization_id', $organizationId)
                                                ->where('program_id', $programId)
                                                ->orderBy('subscription_next_charge_date', 'desc')                                             
                                                ->first();
        if ( !is_null($previousSubscription) )
        {
            return $previousSubscription;
        }

        return false;
    }


}