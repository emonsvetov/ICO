<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Services\AuthorizeNet\SubscriptionService;
use App\Services\AuthorizeNet\PaymentService;

use App\Http\Requests\AnetCreditCardPaymentRequest;
use App\Http\Requests\AnetBankDebitPaymentRequest;
use App\Http\Requests\AnetSubscribeRequest;

class PaymentController extends Controller
{
    /*
    Subscription Vra
    As subscription payment fail. Do we cancel access to system?
    If subscription Details is renewed after failing to pay. Money is only deducted the next day at 2am. Do they have access until then?

    */
    public function subscribe(SubscriptionService $aNet, AnetSubscribeRequest $request, $organization)
    {
        
        $details = $request->validated();

        //Check if valid subscription exists

        
        //if trial_end_date == NULL
        $details['subscription_first_charge_date'] = new \DateTime('+30 days');
        //else
        $details['subscription_first_charge_date']= new \DateTime();

        //if monthly
        if ($details['subscription_type'] == 'monthly')
        {
            $details['charge_interval_in_months'] = 1;
            $details['charge_amount'] = 9.99;
        } else {
            $details['charge_interval_in_months'] = 12;
            $details['charge_amount'] = 99.00;
        }

        
        $subscription = $aNet->subscribe($details);

        if ( $subscription['subscribed'] )
        {
            //Save to database. 
        }

        return response($subscription);
    }

    
    public function unsubscribe(SubscriptionService $aNet, $organization)
    {
        //get subscription ID
        $subscriptionId = 9263730; //9263713

        //If no valid subsription
        //return unsubscribed
        
        $subscription = $aNet->unsubscribe($subscriptionId);

        if ( $subscription['unsubscribed'] )
        {
            //update database. 
        }

        return response($subscription);
    }
    
    
    //Route::post('/v1/organization/{organization}/program/{program}/payment',[App\Http\Controllers\API\ProgramController::class, 'deposit'])->middleware('can:updatePayments,App\Program,organization,program');
   

    public function creditCard(PaymentService $pay, AnetCreditCardPaymentRequest $request, $organization, $program)
    {

        $details = $request->validated();

        //!!!!NEED TO GET/CREATE INVOICE DETAILS AND LINE ITEMS
        $invoice = rand(10000,99999);

        $payment = $pay->byCreditCard($invoice, $details);

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
    }

    public function bankDebit(PaymentService $pay, AnetBankDebitPaymentRequest $request, $organization, $program)
    {
        $details = $request->validated();

        //!!!!NEED TO GET/CREATE INVOICE DETAILS AND LINE ITEMS
        $invoice = rand(10000,99999);

        $payment = $pay->byBankDebit($invoice, $details);

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
        
    }
    
    public function googlePay()
    {

    }

    public function applePay()
    {

    }

    public function paypal()
    {
        
    }

    public function paypalRedirect()
    {
        
    }

}
