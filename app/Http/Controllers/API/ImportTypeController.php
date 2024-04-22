<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CsvImportTypeRequest;
use App\Http\Controllers\Controller;
use App\Models\CsvImportType;
use App\Models\Organization;
use App\Models\Program;

class ImportTypeController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        $query = CsvImportType::query();

        $context = request()->get('context', '');
        if( $context )
        {
            $query->where('context', 'like', $context);
        }

        return response( $query->get() );
    }

    public function update(CsvImportTypeRequest $request, Organization $organization, CsvImportType $csvImportType )
    {
        $data = $request->validated();
        $csvImportType->update( $data );
        return response([ 'csvImportType' => $csvImportType ]);
    }
}
