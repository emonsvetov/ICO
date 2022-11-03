<?php
namespace App\Services\Program;

use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\CronInvoice;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Invoice;
use App\Models\Owner;

use App\Services\ProgramService;

class GenerateMonthlyInvoicesService
{
    public function __construct(
        ProgramService $programService,
        CreateInvoiceService $createInvoiceService,
        SendMonthlyInvoiceService $sendMonthlyInvoiceService,
        ReadCompiledInvoiceService $readCompiledInvoiceService,
    ) {
        $this->programService = $programService;
        $this->createInvoiceService = $createInvoiceService;
        $this->sendMonthlyInvoiceService = $sendMonthlyInvoiceService;
        $this->readCompiledInvoiceService = $readCompiledInvoiceService;
    }

    public function generate()
    {
        $allResponses = null;
        $cronInvoices = CronInvoice::getProgramsToInvoice();
        // dd($cronInvoices->toArray());
        $last_month = strtotime(date('Y-m')  . " -1 month" );
        $date_start = date('Y-m-01', $last_month); 
        $date_end = date('Y-m-t', $last_month);
        foreach ($cronInvoices as $cronInvoice) {
            $response = ['cronInvoice' => $cronInvoice];
            $program = $cronInvoice->program;
            // dump($program->toArray());

            if($program->is_demo)
            {
                continue;
            }

            $is_root = $program->isRoot();

            if (!$program->bill_parent_program || $is_root) 
            {
                $exists = Invoice::getProgramMonthlyInvoice4Date($program, $date_start);
                // dump($mios->toArray());
                \Log::info( "checking invoice for ProgramID: " . $program->id ); 
                if (false && $exists) 
                {
                    \Log::info( "skipping " . $program->id . " already invoiced" ); 
                } 
                else 
                { 
                    \Log::info( "need to generate invoice for " . $program->id ); 

                    $days_to_pay = 15; // default to 15 days after end_date
                    $invoice_type = 'Monthly'; // default to monthly invoicing
                    $payment_method_id = 0;
                    $invoice = $this->createInvoiceService->process ( $program, $invoice_type, $date_start, $date_end, $days_to_pay, $payment_method_id );

                    $invoice->load('program');
                    $compiledInvoice = $this->readCompiledInvoiceService->get ($invoice );
                    $sendResult = $this->sendMonthlyInvoiceService->send($program, $compiledInvoice);

                    $response['msg'] = $sendResult['msg'];

                    if( isset($sendResult['success']) )    
                    {
                        \Log::info( "Monthly invoice sent for program: " . $program->id ); 
                        $updatable['invoice_id'] = $invoice->id;
                        $updatable['msg'] = 'Sent monthly invoice for program: '. $program->id;

                        $response['success'] = true;

                    }   elseif (isset($sendResult['error']))
                    {
                        $response['error'] = true;
                    }
                }
            } 
            else
            {
                \Log::info( "Not an invoicable program: " . $program->id ); 
                $updatable['msg'] = "Not an invoicable program: " . $program->id; 
                $updatable['invoice_id'] = 0; 
            }

            if( isset($updatable) ) 
            {
                $cronInvoice->update([
                    'response' => $updatable['msg'],
                    'invoice_id' => $updatable['invoice_id'],
                    'invoice_date' => now(),
                ]);
                // Ref. $this->crons_model->add_invoice_cron_info($program, $response); 
            }   
            else 
            {
                $cronInvoice->update([
                    'invoice_date' => now()
                ]);
                // Ref. $this->crons_model->mark_program_processed($program, 'invoice_date'); 
            }
            $allResponses[$cronInvoice->id] = $response;
        }
        return $allResponses;
    }
}