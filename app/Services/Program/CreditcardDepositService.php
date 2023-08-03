<?php
namespace App\Services\Program;

use App\Models\JournalEventType;
use App\Models\Program;
use App\Models\Invoice;

class CreditcardDepositService
{
    public function process(Program $program, $data) {
        $amount = (float) $data['amount'];
        if( empty($data['invoice_id'])) {
            $invoice = Invoice::find(24);
            // $createInvoiceService = resolve(CreateInvoiceService::class);
            // $invoice = $createInvoiceService->createCreditcardDepositInvoice( $program, $data);
            // if( !$invoice ) {
            //     throw new \InvalidArgumentException ( "Invoice was not created.", 400 );
            // }
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
            }
            $total_amount = round ( $amount + $convenience_fee + $deposit_fee, 2 );
            $invoice_id = ( int ) $invoice->invoice_id;
            $timestamp = time();
            $manager = auth()->user();
            $invoice_sequence = $invoice->seq;
            $invoice_number = $invoice->invoice_number;
            $uri = '/manager/manage-account';
            $anet_form = $this->authorize_net_model->new_sim_form ( $program, $manager->email, "PAYMENT_FORM", $timestamp, $invoice_number, $invoice_id, $invoice_sequence, $total_amount, $uri, $line_items );
            $anet_form->description = $invoice_description;
            return $anet_form;
            // return (array) $invoice_details;
        }
    }
}
