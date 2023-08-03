<?php
namespace App\Services\Program;

use App\Models\PaymentMethod;
use App\Models\InvoiceType;
use App\Models\Invoice;
use App\Models\Program;

class CreateInvoiceService
{
    public function process($program, $invoice_type, $date_begin, $date_end, $days_to_pay, $payment_method_id)
    {
        $program_id = $program->id;
        $date_due_strtotime = strtotime($date_end . " +{$days_to_pay} days");
        $invoice_key = $program->account_holder_id . date('ym', strtotime($date_end));
        $invoice_type_id = InvoiceType::getIdByName($invoice_type);

        $type_on_demand = InvoiceType::getIdByTypeOnDemand(true);
        // $type_monthly = InvoiceType::getIdByTypeMonthly(true);
        $type_creditcard = InvoiceType::getIdByTypeCreditCard(true);

        $invoice = null;
        $seq = 1;

        if( $invoice_type_id == $type_on_demand || $invoice_type_id == $type_creditcard )
        {
            $query = Invoice::where('program_id', $program_id)
            ->where('created_at', '<=', now());

            if( $query->count() > 0)
            {
                $seq = $query->count() + 1;
            }
            $invoice = Invoice::create([
                'program_id' => $program_id,
                'key' => $invoice_key,
                'seq' => $seq,
                'invoice_type_id' => $invoice_type_id,
                'payment_method_id' => $payment_method_id,
                'date_begin' => $date_begin,
                'date_end' => $date_end,
                'date_due' => date ( 'Y-m-d', $date_due_strtotime )
            ]);
        }
        else
        {
            $query = Invoice::where(['program_id' => $program_id, 'date_end' => $date_end, 'invoice_type_id' => $invoice_type_id]);
            if( $query->count() > 0)
            {
                $invoice = $query->first();
            }
            else
            {
                $count = Invoice::where(['program_id' => $program_id])
                ->where('created_at', '<=', now())
                ->count();
                $seq = $count + 1;

                $invoice = Invoice::create([
                    'program_id' => $program_id,
                    'key' => $invoice_key,
                    'seq' => $seq,
                    'invoice_type_id' => $invoice_type_id,
                    'payment_method_id' => $payment_method_id,
                    'date_begin' => $date_begin,
                    'date_end' => $date_end,
                    'date_due' => date ( 'Y-m-d', $date_due_strtotime )
                ]);
            }
        }
        return $invoice;
    }
    public function createCreditcardDepositInvoice(Program $program, $data) {
        if( empty( $data['amount']) || (float) $data['amount'] <= 0) return;
        $amount = (float) $data['amount'];
        // return $deposit_fee = compute_program_fee_by_type ('deposit_fee', $program, $amount );
        $date_begin = $date_end = date ( 'Y-m-d' );
        $days_to_pay = 3;
        $payment_method_id = PaymentMethod::getPaymentMethodCreditcard();
        $invoice = $this->process($program, InvoiceType::INVOICE_TYPE_CREDITCARD, $date_begin, $date_end, $days_to_pay, $payment_method_id);
        $invoice = (new ChargeInvoiceForMoniesPending())->process($invoice, auth()->user(), $program, $data['amount'] );
        $deposit_fee = compute_program_fee_by_type ('deposit_fee', $program, $amount );
		if ($deposit_fee > 0) {
            $invoice = (new ChargeInvoiceForMoniesPending())->process($invoice, auth()->user(), $program, $data['amount'] );
			// convenience fee
			$updated_convenience_fee = $amount + $deposit_fee;
			$convenience_fee =  compute_program_fee_by_type ('convenience_fee', $program, $updated_convenience_fee );
		} else {
			// convenience fee
			$convenience_fee = compute_program_fee_by_type ('convenience_fee', $program, $amount );
		}
		if ($convenience_fee > 0) {
            $invoice = (new ChargeInvoiceForConvenienceFee())->process($invoice, auth()->user(), $program, $convenience_fee );
		}
        return $invoice;
    }
}
