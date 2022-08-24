<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InvoiceRequest;
use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function index( Organization $organization, Program $program, InvoiceService $invoiceService)
    {
        $invoices = $invoiceService->index( $program );

        if ( $invoices->isNotEmpty() )
        {
            return response( $invoices );
        }

        return response( [] );
    }

    public function createOnDemand(InvoiceRequest $request, InvoiceService $invoiceService, Organization $organization, Program $program )
    {
        // return $request->validated();
        return response( $invoiceService->createOnDemand($request->validated(), $program));
    }

    public function store(InvoiceRequest $request, Organization $organization, Program $program )
    {
        $newAward = Invoice::create(
            (object) ($request->validated() + 
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]),
            $program,
            auth()->user()
        );

        return $newAward;

        if ( !$newAward )
        {
            return response(['errors' => 'Award creation failed'], 422);
        }

        return $newAward;

        return response([ 'award' => $newAward ]);
    }

    public function show( Organization $organization, Program $program, Invoice $invoice, InvoiceService $invoiceService )
    {
        $invoice = $invoiceService->getInvoice($invoice);
        return response( $invoice );
    }

    public function download( Organization $organization, Program $program, Invoice $invoice, InvoiceService $invoiceService )
    {

        $invoice = $invoiceService->getInvoice($invoice);
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice->toArray()]);
        return $pdf->stream();
    }
}
