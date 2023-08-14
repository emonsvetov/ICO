<?php

namespace App\Services\Program\Deposit;

use App\Models\JournalEventType;
use App\Models\Program;
use App\Models\Invoice;

class DepositHelper
{
	const UNKNOWN = 'UNKOWN';
	const UNPAID = 'UNPAID';
	const PAID = 'PAID';
	const DECLINED = 'DECLINED';
	const REFUNDED = 'REFUNDED';

    public function parseInvoiceForPayment(Invoice $invoice)  {
        $invoice_details = resolve(\App\Services\Program\ReadCompiledInvoiceService::class)->read_creditcard_deposit_invoice_details($invoice);
        $debits = $invoice_details->debits;
		$credits = $invoice_details->credits;
		$invoice_data ['status'] = self::UNKNOWN;
		$invoice_data ['deposit_fee'] = 0;
		$invoice_data ['convenience_fee'] = 0;
		$invoice_data ['deposit_amount'] = 0; // the loop through the line items on the invoice will compute the remaining deposit amount left to credit
		$invoice_data ['total_amount'] = 0;
		$invoice_data ['deposit_fee_due'] = 0;
		$invoice_data ['convenience_fee_due'] = 0;
		$invoice_data ['deposit_amount_due'] = 0;
		$invoice_data ['total_amount_due'] = 0;
		$invoice_data ['deposit_fee_paid'] = 0;
		$invoice_data ['convenience_fee_paid'] = 0;
		$invoice_data ['deposit_amount_paid'] = 0;
		$invoice_data ['total_amount_paid'] = 0;
		if (is_array ( $debits ) && count ( $debits ) > 0) {
			foreach ( $debits as &$statement_item ) {
				$item_total = $statement_item->amount; // total amount for line item
				$item_ea = $statement_item->ea; // amount per each
				$item_qty = $statement_item->qty; // quantity of items in line
				switch ($statement_item->journal_event_type) {
					case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_CONVENIENCE_FEE :
						$invoice_data ['convenience_fee'] += round ( $item_ea * $item_qty, 2 );
						$invoice_data ['convenience_fee_due'] += round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE :
						$invoice_data ['deposit_fee'] += round ( $item_ea * $item_qty, 2 );
						$invoice_data ['deposit_fee_due'] += round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING :
						$invoice_data ['deposit_amount'] += round ( $item_ea * $item_qty, 2 );
						$invoice_data ['deposit_amount_due'] += round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE :
						$invoice_data ['deposit_fee_due'] -= round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_CONVENIENCE_FEE :
						$invoice_data ['convenience_fee_due'] -= round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING :
						$invoice_data ['deposit_amount_due'] -= round ( $item_ea * $item_qty, 2 );
						$invoice_data ['status'] = self::REFUNDED;
						break;
					default :
				}
			}
		}// loop through line items
		  // set the due values and we'll subtract from them if there are any payments already processed
		  // $invoice_data['convenience_fee_due']+= $invoice_data['convenience_fee'];
		  // $invoice_data['deposit_fee_due']+= $invoice_data['deposit_fee'];
		  // $invoice_data['deposit_amount_due']+= $invoice_data['deposit_amount'];
		  // subtract anything that has been posted as payments for the fees and deposit amount
		if (is_array ( $credits ) && count ( $credits ) > 0) {
			foreach ( $credits as &$statement_item ) {
				$item_total = $statement_item->amount; // total amount for line item
				$item_ea = $statement_item->ea; // amount per each
				$item_qty = $statement_item->qty; // quantity of items in line
				switch ($statement_item->journal_event_type) {
					case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE :
						$invoice_data ['convenience_fee_due'] -= round ( $item_ea * $item_qty, 2 );
						$invoice_data ['convenience_fee_paid'] += round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE :
						$invoice_data ['deposit_fee_due'] -= round ( $item_ea * $item_qty, 2 );
						$invoice_data ['deposit_fee_paid'] += round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING :
						// set invoice status to paid
						if ($invoice_data ['status'] != self::REFUNDED) {
							$invoice_data ['status'] = self::PAID;
						}
						$invoice_data ['deposit_amount_due'] -= round ( $item_ea * $item_qty, 2 );
						$invoice_data ['deposit_amount_paid'] += round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_VOID_CHARGE_PROGRAM_FOR_CONVENIENCE_FEE :
						$invoice_data ['convenience_fee_due'] -= round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_VOID_CHARGE_PROGRAM_FOR_DEPOSIT_FEE :
						$invoice_data ['deposit_fee_due'] -= round ( $item_ea * $item_qty, 2 );
						break;
					case JournalEventType::JOURNAL_EVENT_TYPES_VOID_CHARGE_PROGRAM_FOR_MONIES_PENDING :
						if ($invoice_data ['status'] != self::REFUNDED) {
							$invoice_data ['status'] = self::DECLINED;
						}
						$invoice_data ['deposit_amount_due'] -= round ( $item_ea * $item_qty, 2 );
						break;
					default :
				}
			}
		} // loop through line items
		$invoice_data ['total_amount'] = $invoice_data ['deposit_amount'] + $invoice_data ['deposit_fee'] + $invoice_data ['convenience_fee'];
		$invoice_data ['total_amount_due'] = $invoice_data ['deposit_amount_due'] + $invoice_data ['deposit_fee_due'] + $invoice_data ['convenience_fee_due'];
		$invoice_data ['total_amount_paid'] = $invoice_data ['deposit_amount_paid'] + $invoice_data ['deposit_fee_paid'] + $invoice_data ['convenience_fee_paid'];
		if ($invoice_data ['status'] != self::DECLINED && $invoice_data ['status'] != self::REFUNDED) {
			if ($invoice_data ['total_amount_due'] > 0) {
				$invoice_data ['status'] = self::UNPAID;
			}
		}
		// return the calculated data
		return $invoice_data;
    }

    public function getSetInvoice(Program $program, $data) {
        // $data['invoice_id'] = null;
        if( empty($data['invoice_id'])) {
            // $invoice = Invoice::find(24); //To be removed!!
            $createInvoiceService = resolve(\App\Services\Program\CreateInvoiceService::class);
            $invoice = $createInvoiceService->createCreditcardDepositInvoice( $program, $data);
            if( !$invoice ) {
                throw new \InvalidArgumentException ( "Invoice was not created.", 400 );
            }
        }   else {
            $invoice = Invoice::find($data['invoice_id']);
        }

        if( empty($invoice->program))   {
            $invoice->program = $program;
        }

        return $invoice;
    }
}
