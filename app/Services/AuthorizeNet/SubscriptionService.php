<?php

namespace App\Services\AuthorizeNet;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Models\Invoice;

class SubscriptionService
{
    private $merchantAuthentication;
    private $referenceId;

    public function __construct() 
    {
        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName( env('AUTHORIZENET_API_LOGIN_ID') );
        $this->merchantAuthentication->setTransactionKey( env('AUTHORIZENET_TRANSACTION_KEY') );

        $this->referenceId = 'ref' . time();
    }

    public function subscribe($details)
    {
       
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
        

        // Subscription Type Info
        $subscription = new AnetAPI\ARBSubscriptionType();
        $subscription->setName("Sample Subscription");
        $subscription->setPaymentSchedule($paymentSchedule);
        $subscription->setAmount( $details['charge_amount'] );        
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

        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) 
        {            
            $data['subscribed'] = true;
            $data['status'] = "ok";
            $data['subscriptionId'] = $response->getSubscriptionId();

        } else {
            $errorMessages = $response->getMessages()->getMessage();
            
            $data['subscribed'] = false;
            $data['status'] = 'error';
            $data['error'] = $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText();
        }

        return $data;
    }

    public function unsubscribe($subscriptionId)
    {
        $request = new AnetAPI\ARBCancelSubscriptionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($this->referenceId);
        $request->setSubscriptionId($subscriptionId);

        $controller = new AnetController\ARBCancelSubscriptionController($request);

        $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX );

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
}