<?php
namespace App\Services\Program;

use App\Models\Program;
use App\Models\Domain;

class GetProgramsToInvoiceService
{
    private ReadListAllProgramsService $readListAllProgramsService;

    public function __construct(
        ReadListAllProgramsService $readListAllProgramsService
    ) {
        $this->readListAllProgramsService = $readListAllProgramsService;
    }

    public function get()
    {
        //To get either the core programs or programs with Create Invoices enabled.
        $extraArgs['create_invoices'] = true;
        // If a program works in “Demo“ mode it should be excluded from invoicing process
        // $extraArgs['program_is_demo'] = 0; //Right now field not set in table
        return $this->readListAllProgramsService->get( $extraArgs );
    }
}