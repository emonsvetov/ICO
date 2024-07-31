<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AnetSubscriptions;
use App\Models\AnetApiLog;
use App\Models\Program;

class AnetWebhookController extends Controller
{
    /*
    {
        "notificationId":"8d9825d1-5cbd-4bfb-9d2c-b7feaf702622",
        "eventType":"net.authorize.customer.subscription.created",
        "eventDate":"2018-05-24T21:54:28.4996155Z",
        "webhookId":"a7369ca9-c1b4-4e30-87c7-b08d6872e480",
        "payload":{
            "name":"111",
            "amount":12.00,
            "status":"active",
            "profile":{
                "customerProfileId":1504570593,
                "customerPaymentProfileId":1503880282,
                "customerShippingAddressId":1504016383
            },
            "entityName":"subscription",
            "id":"5190021"
        }
    }

    */
    public function index()
    {
        return response(AnetApiLog::orderBy('id', 'desc')->take(10)->get());
    }

    public function store(DepositHelper $helper, CreditcardDepositService $desposit ,Request $request)
    {
        $log = AnetApiLog::create([            
            'url' => $this->aNetUrl(),
            'request' =>  json_encode($request),
            'response' => json_encode(['webhook' => true]),
        ]);
        
        if ( isset($request->payload->entityName) )
        {
            if ($request->payload->entityName == "subscription")
            {
                $subscription = AnetSubscriptions::where('subscription_id', $request->payload->id);

                $subscription->is_active = 1;
                $subscription->subscription_next_charge_date = new \DateTime('+' . $subscription->charge_interval_in_months . ' month');
                $subscription->save();

                $program = Program::find($subscription->program_id);
                //Create Invoice
                $invoice = $helper->getSetInvoice( $program, $details );                

                $invoiceClass = new \stdClass();
                $invoiceClass->invoice_id = $invoice->id;
                $invoiceClass->amount = $subscription->amount;

                //Figure out what to do with invoicing or rollback
                $desposit->finalize($program, $invoiceClass);

                $log->organization_id = $subscription->organization_id;
                $log->program_id = $subscription->program_id;
                $log->save();

            }
        }
        
    }
}
