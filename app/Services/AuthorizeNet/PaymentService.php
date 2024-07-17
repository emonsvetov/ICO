<?php

namespace App\Services\AuthorizeNet;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Models\AnetApiLog;
use App\Models\Invoice;
use App\Models\BillTo;

class PaymentService
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

    private function setOrganizationAndProgram( $organization, $program )
    {
        $this->organizationId = $organization;
        $this->programId = $program;
    }

    private function aNetUrl()
    {
        if ( env('APP_ENV') == 'production' )
        {
            return \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        }            
            return \net\authorize\api\constants\ANetEnvironment::SANDBOX;
    }

    private function aNetTransaction( $transactionRequestType )
    {
        $request = new AnetAPI\CreateTransactionRequest();
		$request->setMerchantAuthentication( $this->merchantAuthentication );
        $request->setRefId( $this->referenceId );
		$request->setTransactionRequest( $transactionRequestType );
		$controller = new AnetController\CreateTransactionController( $request );
		
        //Add production URL as well
        $response = $controller->executeWithApiResponse( $this->aNetUrl() );

        //We do not want to store card data in the DB
        $request = json_decode(json_encode($request), true);
        unset( $request['transactionRequest']['payment']);

        //Save request and response to log
        $log = AnetApiLog::create([
            'organization_id' => $this->organizationId,
            'program_id' => $this->programId,
            'url' => $this->aNetUrl(),
            'request' =>  json_encode($request),
            'response' => json_encode($response),
        ]); 
        
        return $response;
    }

    private function setCustomerAddress( $details )
    {
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName($details['first_name']);
        $customerAddress->setLastName( $details['last_name'] );

        if ( isset($details['company']) )
            $customerAddress->setCompany( $details['company'] );
        if ( isset($details['address']) )
            $customerAddress->setAddress( $details['address'] );
        if ( isset($details['city']) )
            $customerAddress->setCity(  $details['city'] );
        if ( isset($details['state']) )
            $customerAddress->setState( $details['state'] );
        if ( isset($details['zip']) )
            $customerAddress->setZip( $details['zip'] );
        if ( isset($details['country']) )
            $customerAddress->setCountry( $details['country'] );

        //Update Last Bill To
        BillTo::saveLastUsed( $this->organizationId, $this->programId, $details );

        return $customerAddress;
    }

    private function buildResponse( $response )
    {
        if ( is_null( $response ) ) 
        {
            $data['successful'] = false;
            $data['errorCode'] = "Unknown";
            $data['errorMessage'] = "No response returned";

            return $data;
        }

        $tresponse = $response->getTransactionResponse();

        if ($response->getMessages()->getResultCode() == "Ok") 
        {
            if ( !is_null($tresponse->getMessages()) )
            {
                $data['successful'] = true;
                $data['transactionResponseCode'] = $tresponse->getResponseCode();
                $data['transactionId'] = $tresponse->getTransId();
                $data['authCode'] = $tresponse->getAuthCode();
                $data['messageCode'] = $tresponse->getMessages()[0]->getCode();
                $data['description'] = $tresponse->getMessages()[0]->getDescription();

                if ( !is_null($tresponse->getSecureAcceptance()) )
                    $data['redirectUrl'] = $tresponse->getSecureAcceptance()->getSecureAcceptanceUrl();

            } else {
                
                $data['successful'] = false;

                if ($tresponse->getErrors() != null) 
                {
                    $data['errorCode'] = $tresponse->getErrors()[0]->getErrorCode();
                    $data['errorMessage'] = $tresponse->getErrors()[0]->getErrorText();                    
                }
            }
        } else {
            
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

        return $data;
    }

    public function byApplePay( $details, $organization, $program )
    {
        $this->setOrganizationAndProgram( $organization, $program );
        /*/setOpaqueData
        THis is the base64 encoding of the result from apple. The contents of Payment Token: { "paymentData": {this must be base64 encoded}
        */

        $op = new AnetAPI\OpaqueDataType();
        $op->setDataDescriptor("COMMON.APPLE.INAPP.PAYMENT");
        $op->setDataValue( $details['opaqueData'] );
   
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setOpaqueData($op);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType( "authCaptureTransaction");        
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setAmount( $details['amount'] );

        if ( array_key_exists('first_name', $details) )
            $transactionRequestType->setBillTo( $this->setCustomerAddress( $details ) );

        $response = $this->aNetTransaction( $transactionRequestType );

        return $this->buildResponse( $response );
    }

    public function byGooglePay( $details, $organization, $program )
    {
        $this->setOrganizationAndProgram( $organization, $program );
        
        //opaqueData
        $opaqueData = new AnetAPI\OpaqueDataType();
        $opaqueData->setDataDescriptor("COMMON.GOOGLE.INAPP.PAYMENT");
        $opaqueData->setDataValue( $details['opaqueData'] );
        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setOpaqueData($opaqueData);

        //Ask if invoice must be build

       
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");        
        $transactionRequestType->setPayment($paymentType);
        $transactionRequestType->setAmount( $details['amount'] );

        if ( array_key_exists('first_name', $details) )
            $transactionRequestType->setBillTo( $this->setCustomerAddress( $details ) );
        

        $response = $this->aNetTransaction( $transactionRequestType );

        return $this->buildResponse( $response );
    }

    public function byPayPal( $details, $organization, $program )
    {
        $this->setOrganizationAndProgram( $organization, $program );

        $payPalType=new AnetAPI\PayPalType();
        $payPalType->setCancelUrl( $details['redirectUrl'] );
        $payPalType->setSuccessUrl( $details['redirectUrl'] );
        
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setPayPal($payPalType);

        // Create an authorize and capture transaction
		$transactionRequestType = new AnetAPI\TransactionRequestType();
		$transactionRequestType->setTransactionType( "authCaptureTransaction");
		$transactionRequestType->setPayment($paymentOne);
		$transactionRequestType->setAmount( $details['amount'] );

        $response = $this->aNetTransaction( $transactionRequestType );

        return $this->buildResponse( $response );
    }

    public function processPayPalRedirect( $details, $organization, $program )
    {
        $this->setOrganizationAndProgram( $organization, $program );

        // Set PayPal compatible merchant credentials
        $payPalType=new AnetAPI\PayPalType();
        $payPalType->setPayerID( $details['payerID'] );

        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setPayPal($payPalType);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureContinueTransaction");
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setRefTransId( $details['refTransId'] );

        $response = $this->aNetTransaction( $transactionRequestType );

        return $this->buildResponse( $response );
    }

    public function byCreditCard($invoice, $details, $organization, $program)
    {
        $this->setOrganizationAndProgram( $organization, $program );

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($invoice); //This needs to be unique $invoice
        $order->setDescription("Golf Shirts");

        $customerAddress = $this->setCustomerAddress( $details );

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
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setPayment($paymentOne);
        $transactionRequestType->setBillTo($customerAddress);
        $transactionRequestType->setAmount( $details['amount'] );

        $response = $this->aNetTransaction( $transactionRequestType );

        return $this->buildResponse( $response );
    }


    public function byBankDebit($invoice, $details, $organization, $program)
    {
        $this->setOrganizationAndProgram( $organization, $program );

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($invoice); //This needs to be unique $invoice
        $order->setDescription("Golf Shirts");

        $customerAddress = $this->setCustomerAddress( $details );

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

        $response = $this->aNetTransaction( $transactionRequestType );

        return $this->buildResponse( $response );
    }
}