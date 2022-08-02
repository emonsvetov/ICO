<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\InvoiceRequest;
use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Invoice;

class InvoiceController extends Controller
{
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
}
