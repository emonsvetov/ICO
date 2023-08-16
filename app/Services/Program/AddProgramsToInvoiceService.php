<?php
namespace App\Services\Program;

use App\Models\CronInvoice;

class AddProgramsToInvoiceService
{
    private GetProgramsToInvoiceService $getProgramsToInvoiceService;

    public function __construct(
        GetProgramsToInvoiceService $getProgramsToInvoiceService
    ) {
        $this->getProgramsToInvoiceService = $getProgramsToInvoiceService;
    }

    public function add()
    {
        $programs = $this->getProgramsToInvoiceService->get();

        $programs_to_invoice = [];
        foreach( $programs as $program )
        {
            $programs_to_invoice[] = [
                'program_id' => $program->id,
                'name' => $program->name,
                'created_at' => now()
            ];
        }
        if( $programs_to_invoice )
        {
            CronInvoice::insert( $programs_to_invoice );
        }
    }
}
