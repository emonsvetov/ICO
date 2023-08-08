<?php
namespace App\Services\Program;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

define('MERCHANT_LOGIN_ID', '5KP3u95bQpv');
define('MERCHANT_TRANSACTION_KEY', '346HZ32z3fP4hTG2');

use App\Models\JournalEventType;
use App\Models\Program;
use App\Models\Invoice;

class CreditcardDepositService
{
    public function process(Program $program, $data) {
        $invoice = $this->getSetInvoice($program, $data);
        $aNetReadyInvoice = $this->preparePaymentInvoice($invoice, $data);
        return $this->getAuthorizeNetToken($aNetReadyInvoice);
    }

    private function getAuthorizeNetToken( $aNetReadyInvoice ) {

        $data = ['result' => null, 'error' => null];

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(MERCHANT_LOGIN_ID);
        $merchantAuthentication->setTransactionKey(MERCHANT_TRANSACTION_KEY);

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
            sprintf("{\"url\": \"%s/manage-account/payment-success\", \"cancelUrl\": \"%s/manage-account/payment-error\", \"showReceipt\": true}", $url, $url)
        );

        // Build transaction request
        $anetRequest = new AnetAPI\GetHostedPaymentPageRequest();
        $anetRequest->setMerchantAuthentication($merchantAuthentication);
        $anetRequest->setRefId($refId);
        $anetRequest->setTransactionRequest($transactionRequestType);

        $anetRequest->addToHostedPaymentSettings($setting1);
        $anetRequest->addToHostedPaymentSettings($setting2);
        $anetRequest->addToHostedPaymentSettings($setting3);

        //execute request
        $controller = new AnetController\GetHostedPaymentPageController($anetRequest);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            // echo $response->getToken()."\n";
            $data['status'] = "ok";
            $data['token'] = $response->getToken();
        } else {
            $errorMessages = $response->getMessages()->getMessage();
            $errorText = "RESPONSE : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
            $data['error'] = $errorText;
            $data['status'] = $errorMessages[0]->getCode();
        }
        return $data;
    }

    private function getSetInvoice(Program $program, $data) {
        if( empty($data['invoice_id'])) {
            // $invoice = Invoice::find(24);
            $createInvoiceService = resolve(CreateInvoiceService::class);
            $invoice = $createInvoiceService->createCreditcardDepositInvoice( $program, $data);
            if( !$invoice ) {
                throw new \InvalidArgumentException ( "Invoice was not created.", 400 );
            }
        }   else {
            $invoice = Invoice::find($data['invoice_id']);
        }
        $invoice->program = $program;
        return $invoice;
    }

    private function preparePaymentInvoice(Invoice $invoice, $data) {
        $amount = (float) $data['amount'];

        $readCompiledInvoiceService = resolve(ReadCompiledInvoiceService::class);
        $invoice_details = $readCompiledInvoiceService->read_creditcard_deposit_invoice_details($invoice);
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
