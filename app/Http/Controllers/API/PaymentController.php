<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Services\AuthorizeNet\SubscriptionService;
use App\Services\AuthorizeNet\PaymentService;
use App\Services\Program\Deposit\CreditcardDepositService;
use App\Services\Program\Deposit\DepositHelper;


use App\Http\Requests\AnetCreditCardPaymentRequest;
use App\Http\Requests\AnetBankDebitPaymentRequest;
use App\Http\Requests\AnetPayPalPaymentRequest;
use App\Http\Requests\AnetGooglePaymentRequest;
use App\Http\Requests\AnetApplePaymentRequest;
use App\Http\Requests\AnetSubscribeRequest;

use App\Models\AnetSubscriptions;
use App\Models\Program;

//DELETE
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /*
                - Webhooks    
                - - Create invoice when subscription is charged webhook
                - - Create invoice when program is funded webhook
- Link subscriptions to programs instead of organizations
- Allow for bill to information to be saved when payment is done (one for subscriptions and one for fund by)
- Allow providing bill to information for subscriptions on call
- Allow providing bill to information for funding by call
                - Allow system to make journal entries with new payment types in system (Google Pay etc)
                - Change System to make journal capable of Credit Card and well as Bank Debit subscriptions        
- Create Login for fasteezy
- - Add optional request variable on register to indicate fasteezy
- Create Signup for fasteezy
- - Determine and apply default permissions, roles etc. 
                - Create invite user to fasteezy and all steps related
                - - track sign ups with affiliate code
                - Create Middleware compatable with V3 that prevents usage when it is fasteezy user and subscriptions are not paid
                - Create Policies where needed for fasteezy users
                - Create Add User to Program for fasteezy
                - - Allow users to be in multiple programs
                - Allow for user to be manager and participant for fasteeazy on different programs

                Unknowns:
                - changes that Stepan or Arvind requests 
                - unforseen changes required to make fasteezy work on the same code base (duh)

                Extra
                - Participants can log in and redeem when subscription is not active
                - Campaign can be created for programs
    */

    public function subscribe(SubscriptionService $aNet, AnetSubscribeRequest $request, $organization, $program)
    {
        $payment = [];
        $charge_now = false;
        $details = $request->validated();

        //Set monthly or yearly and charge dates
        $aNet->setSubscriptionType($details);        
        
        //Check if there is an active subscription
        $subscription = $aNet->getActiveSubscription($organization, $program);

        if ( $aNet->isActiveSubscription($subscription) )
        {
            return response([
                'subscribed' => true,
                'status' => 'Already Subscribed',
                'subscriptionId' => $subscription->subscription_id,
                'subscription_next_charge_date' => $subscription->subscription_next_charge_date
            ]);
        }
        
        $previous_subscription = $aNet->getPreviousSubscription($organization, $program);

        if ( $aNet->mustChargeNow($previous_subscription) )
        {
            $invoice = rand(10000,99999);
            $pay = new PaymentService();
            if ( $details['payment_type'] == 'creditcard' )
            {
                $payment = $pay->byCreditCard($invoice, $details, $organization, $program);

            } else {

                $payment = $pay->byBankDebit($invoice, $details, $organization, $program);
            }

            if ( !$payment['successful'] )
            {
                //Figure out what to do with invoicing or rollback
                return response($payment);
            }
            
            //Calculate when subscription should charge 
            $next_charge_date = new \DateTime('+' . $details['charge_interval_in_months']  . ' month');

            $details['subscription_first_charge_date'] = new \DateTime($next_charge_date->format('Y-m-d') . ' 10:00:00'); 
            $details['subscription_next_charge_date']  = new \DateTime($next_charge_date->format('Y-m-d') . ' 10:00:00');

            //Unsubscribe if an subscription is active
            if ( $subscription != false )
            {
                $aNet->unsubscribe($subscription->subscription_id, $organization, $program);
                $subscription->cancelled = now();
                $subscription->save();
            }
        } elseif ( $previous_subscription ) {

            // They already paid, must only charge based on previous subscription next charge date. 
            $details['subscription_first_charge_date']  = new \DateTime($previous_subscription->subscription_next_charge_date);
            $details['subscription_next_charge_date']  = new \DateTime($previous_subscription->subscription_next_charge_date);            
        }
        
        $subscription = $aNet->subscribe($details, $organization, $program);

        return response($subscription + $payment);
    }

    
    public function unsubscribe(SubscriptionService $aNet, $organization, $program)
    {
        //Check if valid subscription exists
        $activeSubscription = AnetSubscriptions::where('organization_id', $organization)
                                                ->where('program_id', $program)
                                                ->whereNull('cancelled')
                                                ->first();
        if ( is_null($activeSubscription) )
        {
            return response([
                'unsubscribed' => true,
                'status' => 'No Active Subscription',
                "subscriptionId" => null
            ]);
        }
        
        $subscription = $aNet->unsubscribe($activeSubscription->subscription_id, $organization, $program);

        if ( $subscription['unsubscribed'] )
        {
            $activeSubscription->cancelled = now();            
            $activeSubscription->save();
        }

        return response($subscription);
    }
       

    public function creditCard(DepositHelper $helper, CreditcardDepositService $desposit ,PaymentService $pay, AnetCreditCardPaymentRequest $request, $organization, Program $program)
    {
        $details = $request->validated();

        $invoice = $helper->getSetInvoice($program, $details);
        $invoiceNumber = $invoice->key.'-'.$invoice->seq;
        
        $payment = $pay->byCreditCard($invoiceNumber, $details, $organization, $program);

        if ( $payment['successful'] )
        {
            $invoiceClass = new \stdClass();
            $invoiceClass->invoice_id = $invoice->id;
            $invoiceClass->amount = $details['amount'];

            //Figure out what to do with invoicing or rollback
            $desposit->finalize($program, $invoiceClass);
        }

        return response($payment);
    }

    public function bankDebit(PaymentService $pay, AnetBankDebitPaymentRequest $request, $organization, $program)
    {
        $details = $request->validated();

        //!!!!NEED TO GET/CREATE INVOICE DETAILS AND LINE ITEMS
        $invoice = rand(10000,99999);

        $payment = $pay->byBankDebit($invoice, $details, $organization, $program);

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
        
    }
    
    public function googlePay( PaymentService $pay, AnetGooglePaymentRequest $request, $organization, $program )
    {
        $details = $request->validated();

        $payment = $pay->byGooglePay( $details, $organization, $program );

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
    }

    public function applePay( PaymentService $pay, AnetApplePaymentRequest $request, $organization, $program )
    {
        $details = $request->validated();

        $payment = $pay->byApplePay( $details, $organization, $program );

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
    }

    public function paypal( PaymentService $pay, AnetPayPalPaymentRequest $request, $organization, $program )
    {
        $details = $request->validated();

        $payment = $pay->byPayPal( $details, $organization, $program );

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
    }

    public function paypalRedirect( PaymentService $pay, AnetPayPalPaymentRequest $request, $organization, $program )
    {
        $details = $request->validated();

        $payment = $pay->processPayPalRedirect( $details, $organization, $program );

        if ( $payment['successful'] )
        {
            //Figure out what to do with invoicing or rollback
        }

        return response($payment);
    }

}
