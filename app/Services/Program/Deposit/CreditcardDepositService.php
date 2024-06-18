<?php
namespace App\Services\Program\Deposit;

use Illuminate\Support\Facades\DB;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use App\Models\JournalEventType;
use App\Models\Program;
use App\Models\Invoice;

class CreditcardDepositService extends DepositServiceAbstract
{
    public function init(Program $program, $data) {
        // DB::beginTransaction();
        try{
            $invoice = $this->depositHelper->getSetInvoice($program, $data);
            // $aNetReadyInvoice = $this->preparePaymentInvoice($invoice, $data);
            $resp = $this->getAuthorizeNetToken($invoice, $data);
            if( !empty($resp['token']) )    {
                // DB::commit();
                return $resp;
            }else{
                return $resp;
            }
        } catch (\RuntimeException $e)  {
            // DB::rollBack();
            return [
                'status' => 'error',
                'txt' => $e->getMessage()
            ];
        }
    }

    public function finalize(Program $program, $data) {
        DB::beginTransaction();
        try{
            $invoice = $this->depositHelper->getSetInvoice($program, (array) $data);
            $payment_amount = $data->amount;
            if ($invoice->program->is_invoice_for_awards ()) {
                // points program
                throw new \RuntimeException ( "Program is set to Invoice for Awards, cannot process creditcard purchase of points.", 500 );
            }
            $invoice_data = $this->depositHelper->parseInvoiceForPayment($invoice);
            $invoice_id = $invoice_data['invoice_id'] = ( int ) $invoice->id;
            $fees = 0;
            $total_amount_due = $invoice_data ['total_amount_due'];
            $notes = sprintf("Payment of invoice %d for program: %s", $invoice->id, $invoice->program->name);
            switch ($invoice_data ['status']) {
                case self::PAID :
                    // already paid
                    return [
                        'status' => 'already_paid',
                        'txt' => "The invoice has been paid already."
                    ];
                case self::DECLINED :
                case self::REFUNDED :
                    return;
                // case self::UNPAID:
                // case self::UNKNOWN:
            }
            $programPaymentService = resolve(\App\Services\ProgramPaymentService::class);
            $user = auth()->user();
            $program_account_holder_id = $invoice->program->account_holder_id;
            if ($invoice_data ['convenience_fee_due'] > 0) {
                $fees += $invoice_data ['convenience_fee_due'];
                $total_amount_due -= $invoice_data ['convenience_fee_due'];
                $programPaymentService->program_pays_for_convenience_fee ( $user->account_holder_id, $program_account_holder_id, $invoice_data ['convenience_fee_due'], $notes, $invoice_id );
            }
            if ($invoice_data ['deposit_fee_due'] > 0) {
                $fees += $invoice_data ['deposit_fee_due'];
                $total_amount_due -= $invoice_data ['deposit_fee_due'];
                $programPaymentService->program_pays_for_deposit_fee ( $user->account_holder_id, $program_account_holder_id, $invoice_data ['deposit_fee_due'], $notes, $invoice_id );
            }
            $payment_amount -= $fees; // calculate remaining $$ that can be applied to the invoice
            if ($payment_amount > 0) {
                if ($payment_amount > $invoice_data ['deposit_amount_due']) {
                    $extra = $payment_amount - $invoice_data ['deposit_amount_due'];
                    $payment_amount = $invoice_data ['deposit_amount_due'];
                    // somehow we ended up with extra money,
                    throw new \RuntimeException ( "Accounting error, payment amount is more than expected. ($extra)", 500 );
                }
                // pay for monies pending
                $programPaymentService->program_pays_for_monies_pending ( $user->account_holder_id, $program_account_holder_id, $payment_amount, $notes, $invoice_id );
                DB::commit(); //commit changes
                return [
                    'status' => 'processed',
                    'txt' => "The payment has been processed."
                ];
            }
        } catch (\RuntimeException $e)  {
            DB::rollBack();
            return [
                'status' => 'error',
                'txt' => $e->getMessage()
            ];
        }
    }

    private function get_encrypted_hash($aInvoice)   {
        $toBeEncrypted = extract_fields_from_obj($aInvoice, ['invoice_id', 'invoice_number', 'amount']);
        $toBeEncrypted->timestamp = now();
        return \Illuminate\Support\Facades\Crypt::encryptString(json_encode($toBeEncrypted));
    }

    private function getAuthorizeNetToken( Invoice $invoice, $data ) {
        $aNetReadyInvoice = $this->preparePaymentInvoice($invoice, $data['amount']);
        $data = [];

        $hash = $this->get_encrypted_hash($aNetReadyInvoice);

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('ANET.MERCHANT_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('ANET.MERCHANT_TRANSACTION_KEY'));

        // Set the transaction's refId
        $refId = 'ref' . time();
        $url = app()->call('App\Services\DomainService@makeUrl');

        // Order info
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($aNetReadyInvoice->invoice_number);
        $order->setDescription("Requested Funding Deposit");

        $calculated_total = 0;
        // LineItems
        $lineItems = [];

        foreach( $aNetReadyInvoice->line_items as $i=>$lineItem)    {
            $lineItem1 = new AnetAPI\LineItemType();
            $lineItem1->setItemId(++$i);
            $lineItem1->setName($lineItem->friendly_journal_event_type);
            $lineItem1->setDescription($aNetReadyInvoice->program_name);
            $lineItem1->setQuantity($lineItem->qty);
            $lineItem1->setUnitPrice($lineItem->ea);
            $lineItems[] = $lineItem1;
            $calculated_total += round ( $lineItem->ea * $lineItem->qty, 2 );
            $calculated_total = preg_replace("/[^0-9.]/", "", $calculated_total);
        }
        $total_amount = preg_replace("/[^0-9.]/", "", $aNetReadyInvoice->amount);

        if ($calculated_total != $total_amount) {
			throw new \RuntimeException ( "Calculated total does not equal total amount specified. Check line items and quantities. ($total_amount != $calculated_total)" );
		}

        //create a transaction
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($aNetReadyInvoice->amount);

        if( $lineItems ) {
            $transactionRequestType->setLineItems( $lineItems );
        }

        $transactionRequestType->setOrder($order);

        // Set Hosted Form options
        $setting1 = new AnetAPI\SettingType();
        $setting1->setSettingName("hostedPaymentButtonOptions");
        $setting1->setSettingValue("{\"text\": \"Pay Now\"}");

        $setting2 = new AnetAPI\SettingType();
        $setting2->setSettingName("hostedPaymentOrderOptions");
        $setting2->setSettingValue(
            "{\"show\": true, \"merchantName\": \"Program Deposit Form\"}"
        );

        $setting3 = new AnetAPI\SettingType();
        $setting3->setSettingName("hostedPaymentReturnOptions");
        $setting3->setSettingValue(
            sprintf("{\"url\": \"%s/manager/manage-account?ccdepositStatus=1\", \"cancelUrl\": \"%s/manager/manage-account?ccdepositStatus=5\", \"showReceipt\": true}", $url, $url)
        );

        $setting4 = new AnetAPI\SettingType();
        $setting4->setSettingName("hostedPaymentPaymentOptions");
        $setting4->setSettingValue(
            "{\"showBankAccount\": false}"
        );

        // Build transaction request
        $anetRequest = new AnetAPI\GetHostedPaymentPageRequest();
        $anetRequest->setMerchantAuthentication($merchantAuthentication);
        $anetRequest->setRefId($refId);
        $anetRequest->setTransactionRequest($transactionRequestType);

        $anetRequest->addToHostedPaymentSettings($setting1);
        $anetRequest->addToHostedPaymentSettings($setting2);
        $anetRequest->addToHostedPaymentSettings($setting3);
        $anetRequest->addToHostedPaymentSettings($setting4);

        //execute request
        $controller = new AnetController\GetHostedPaymentPageController($anetRequest);
        $requestUrl = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
        if( env('APP_ENV') == 'production' ){
            $requestUrl = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        }
        $response = $controller->executeWithApiResponse($requestUrl);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            // echo $response->getToken()."\n";
            $data['status'] = "ok";
            $data['token'] = $response->getToken();
            $data['hash'] = $hash;
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            $errorText = "RESPONSE : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
            $data['txt'] = $errorText;
            $data['status'] = 'error';
        }
        return $data;
    }

    private function preparePaymentInvoice(Invoice $invoice, $amount) {
        // $amount = (float) $data['amount'];

        $readCompiledInvoiceService = resolve(\App\Services\Program\ReadCompiledInvoiceService::class);
        $invoice_details = $readCompiledInvoiceService->read_creditcard_deposit_invoice_details($invoice);
        // pr($invoice_details);exit;
        $debits = $invoice_details->debits;
        $deposit_fee = 0;
        $convenience_fee = 0;
        $invoice_description = "Deposit"; // this is just a safe default, invoice_description gets set later in the loop throug the statement
        $line_items = array ();
        if (is_array ( $debits ) && count ( $debits ) > 0) {
            foreach ( $debits as &$statement_item ) {
                $item_total = $statement_item->amount; // total amount for line item
                $item_ea = $statement_item->ea; // amount per each
                $item_qty = $statement_item->qty; // quantity of items in line
                switch ($statement_item->journal_event_type) {
                    case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_CONVENIENCE_FEE :
                        $convenience_fee += round ( $item_ea * $item_qty, 2 );
                        break;
                    case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE :
                        $deposit_fee += round ( $item_ea * $item_qty, 2 );
                        break;
                    case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING :
                        $invoice_description = $statement_item->friendly_journal_event_type;
                    default :
                }
                $line_items [] = $statement_item;
            }
            // Prepare data for Authorize.net
            return (object) [
                'amount' => round ( $amount + $convenience_fee + $deposit_fee, 2 ),
                'invoice_id' => ( int ) $invoice->id,
                'email' => auth()->user()->email,
                'invoice_description' => $invoice_description,
                'line_items' => $line_items,
                'invoice_sequence' => $invoice->seq,
                'invoice_number' => $invoice->invoice_number,
                'program_name' => $invoice->program->name,
                'program_id' => $invoice->program->id,
            ];
        }
    }
}
