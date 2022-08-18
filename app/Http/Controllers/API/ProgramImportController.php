<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\CSVImportRequest;
use App\Services\CSVimportHeaderService;
use App\Services\CSVimportService;

use App\Models\Organization;
use App\Models\CsvImport;
use App\Models\CsvImportType;

use App\Jobs\ImportProgramValidationJob;

class ProgramImportController extends Controller
{

    public function index(Organization $organization)
    {
        $query = CsvImport::withOrganization($organization);

        $csvImports = $query->whereRelation('csv_import_type', 'context', '=', 'Programs')->get();
        
        return response($csvImports);
    }


    public function show(Organization $organization, CsvImport $csvImport)
    {
        $csvImport['notifications'] = $csvImport->notifications;

        return response($csvImport);
    }

    
    public function programHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization)
    {
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        
        $csvHeaders = $csvService->getFieldsToMap( $validated['upload-file'], $supplied_constants, new \App\Http\Requests\CSVProgramRequest, new \App\Http\Requests\ProgramRequest );

        return $csvHeaders;
    }

    public function programFileImport(CSVImportRequest $request, Organization $organization)
    {  
        $validated = $request->validated();

        $validated = $request->validate([
            'fieldsToMap' => 'required|json'
        ]);

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );

        $csvImport = new CsvImport;
        $newCsvImport = $csvImport->createCsvImport($fileUpload + [
            'organization_id'       => $organization->id,
            'csv_import_type_id'    => CsvImportType::getIdByType('add_programs')
        ]);

        ImportProgramValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants);
    }
}
