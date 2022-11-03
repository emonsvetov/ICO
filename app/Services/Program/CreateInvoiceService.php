<?php
namespace App\Services\Program;

use App\Models\InvoiceType;
use App\Models\Invoice;

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
}