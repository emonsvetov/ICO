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
use App\Jobs\ImportProgramJob;

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
        $context = CsvImportType::find($csvImport['csv_import_type'])[0]['context'];

        if ($context === 'Programs')
        {
            $csvImport['notifications'] = $csvImport->notifications;

            return response($csvImport);
        }

        return response([]);
    }

    
    public function programHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization)
    {
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        
        $csvHeaders = $csvService->getFieldsToMap( $validated['upload-file'], $supplied_constants, new \App\Http\Requests\ProgramRequest );

        return $csvHeaders;
    }

    public function programFileImport(CSVImportRequest $request, Organization $organization)
    {  
        $fileUpload = $request->validated();

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

        // ImportProgramValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants);

        //Remove after testing!
        //May need to know validation errors? may be not! If supplied to ImportProgramValidationJob is the import task done for frontend even if there will be validation errors. With thousands of records waiting for validation errors is not logical I think so I will go with Udo on this. So lets remove it after testing.

        try{

            $csvService = new CSVimportService;
    
            $importData = $csvService->importFile( $newCsvImport, $validated['fieldsToMap'], $supplied_constants );
                    
            if ( !empty($importData['errors']) )
            {
                return response(['message'=>'Errors while validating import data', 'errors' => $importData['errors']], 422);
            }

            ImportProgramJob::dispatch($newCsvImport, $importData, $supplied_constants);
            
            return response(['csvImport'=> $newCsvImport]);
        }
        catch (\Throwable $e) 
        {
            $errors = 'ProgramImportController error: ' . $e->getMessage() . ' in line ' . $e->getLine();
            return response(['errors'=> 'Error Importing Programs' ,'e' => $errors], 422);
        }
    }
}
