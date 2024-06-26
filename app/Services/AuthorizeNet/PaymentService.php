<?php

namespace App\Services\AuthorizeNet;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Models\Invoice;

class PaymentService
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

    public function byCreditCard($invoice, $details)
    {
                
        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($invoice); //This needs to be unique $invoice
        $order->setDescription("Golf Shirts");

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName($details['first_name']);
        $customerAddress->setLastName( $details['last_name'] );

        if ( isset($details['company']) )
            $customerAddress->setCompany( $details['company'] );
        if ( isset($details['address']) )
            $customerAddress->setAddress( $details['address'] );
        if ( isset($details['city']) )
            $customerAddress->setCity( $details['city'] );
        if ( isset($details['state']) )
            $customerAddress->setState( $details['state'] );
        if ( isset($details['zip']) )
            $customerAddress->setZip( $details['zip'] );
        if ( isset($details['country']) )
            $customerAddress->setCountry( $details['country'] );


        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber(     $details['card_number'] );
        $creditCard->setExpirationDate( $details['expiration_date'] );
        $creditCard->setCardCode(       $details['card_code'] );
        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount( $details['amount'] );
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);


        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($this->referenceId);
        $request->setTransactionRequest($transactionRequestType);


        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        
        
        if ($response != null) 
        {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") 
            {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();
            
                if ($tresponse != null && $tresponse->getMessages() != null) 
                {                    
                    $data['successful'] = true;
                    $data['transactionId'] = $tresponse->getTransId();
                    $data['messageCode'] = $tresponse->getMessages()[0]->getCode();
                    $data['authCode'] = $tresponse->getAuthCode();
                    $data['description'] = $tresponse->getMessages()[0]->getDescription();

                } else {
                    
                    $data['successful'] = false;
                    if ($tresponse->getErrors() != null) {
                        
                        $data['errorCode'] = $tresponse->getErrors()[0]->getErrorCode();
                        $data['errorMessage'] = $tresponse->getErrors()[0]->getErrorText();
                    }
                }

            } else {
                //Transaction Failed                
                $tresponse = $response->getTransactionResponse();
                $data['successful'] = false;
            
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $data['errorCode'] = $tresponse->getErrors()[0]->getErrorCode();
                    $data['errorMessage'] = $tresponse->getErrors()[0]->getErrorText();
                } else {
                    $data['errorCode'] = $response->getMessages()->getMessage()[0]->getCode();
                    $data['errorMessage'] = $response->getMessages()->getMessage()[0]->getText();
                }
            }
        } else {
            $data['successful'] = false;
            $data['errorCode'] = "Unknown";
            $data['errorMessage'] = "No response returned";
        }
        
        return $data;
    }


    public function byBankDebit($invoice, $details)
    {
        
        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($invoice); //This needs to be unique $invoice
        $order->setDescription("Golf Shirts");

        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName($details['first_name']);
        $customerAddress->setLastName( $details['last_name'] );

        if ( isset($details['company']) )
            $customerAddress->setCompany( $details['company'] );
        if ( isset($details['address']) )
            $customerAddress->setAddress( $details['address'] );
        if ( isset($details['city']) )
            $customerAddress->setCity( $details['city'] );
        if ( isset($details['state']) )
            $customerAddress->setState( $details['state'] );
        if ( isset($details['zip']) )
            $customerAddress->setZip( $details['zip'] );
        if ( isset($details['country']) )
            $customerAddress->setCountry( $details['country'] );


        $bankAccount = new AnetAPI\BankAccountType();
        $bankAccount->setEcheckType('WEB');

        $bankAccount->setAccountType(   $details['account_type'] );  //Either checking, savings, or businessChecking.            
        $bankAccount->setRoutingNumber( $details['routing_number'] ); 
        $bankAccount->setAccountNumber( $details['account_number'] ); 
        $bankAccount->setNameOnAccount( $details['name_on_account'] ); 
        $bankAccount->setBankName(      $details['bank_name'] ); 

        $paymentBank= new AnetAPI\PaymentType();
        $paymentBank->setBankAccount($bankAccount);

        //create a bank debit transaction    
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");        
        $transactionRequestType->setPayment($paymentBank);
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setAmount( $details['amount'] );

        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($this->merchantAuthentication);
        $request->setRefId($this->referenceId);
        $request->setTransactionRequest($transactionRequestType);

        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if ($response != null) {
        if ($response->getMessages()->getResultCode() == "Ok") 
        {
            $tresponse = $response->getTransactionResponse();
        
            if ($tresponse != null && $tresponse->getMessages() != null) 
            {                
                $data['successful'] = true;
                $data['transactionResponseCode'] = $tresponse->getResponseCode();
                $data['transactionId'] = $tresponse->getTransId();
                $data['authCode'] = $tresponse->getAuthCode();
                $data['messageCode'] = $tresponse->getMessages()[0]->getCode();
                $data['description'] = $tresponse->getMessages()[0]->getDescription();                    

            } else {
                
                $data['successful'] = false;

                if ($tresponse->getErrors() != null) 
                {
                    $data['errorCode'] = $tresponse->getErrors()[0]->getErrorCode();
                    $data['errorMessage'] = $tresponse->getErrors()[0]->getErrorText();                    
                }
            }
        } else {
            
            $tresponse = $response->getTransactionResponse();
            $data['successful'] = false;
            
            if ($tresponse != null && $tresponse->getErrors() != null) 
            {
                $data['errorCode'] = $tresponse->getErrors()[0]->getErrorCode();
                $data['errorMessage'] = $tresponse->getErrors()[0]->getErrorText();
            } else {
                
                $data['errorCode'] = $response->getMessages()->getMessage()[0]->getCode();
                $data['errorMessage'] = $response->getMessages()->getMessage()[0]->getText();
            }
        }
    } else {
        $data['successful'] = false;
        $data['errorCode'] = "Unknown";
        $data['errorMessage'] = "No response returned";
    }

    return $data;
    }

}