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

use App\Jobs\ImportEventValidationJob;

use App\Models\Event;
use DB;
use Log;

class EventImportController extends Controller
{

    public function index(Organization $organization)
    {
        $query = CsvImport::withOrganization($organization);

        $csvImports = $query->whereRelation('csv_import_type', 'context', '=', 'Events')->get();
        
        return response($csvImports);
    }


    public function show(Organization $organization, CsvImport $csvImport)
    {
        $context = CsvImportType::find($csvImport['csv_import_type'])[0]['context'];

        if ($context === 'Events')
        {
            $csvImport['notifications'] = $csvImport->notifications;

            return response($csvImport);
        }

        return response([]);
    }

    
    public function eventHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization)
    {
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        
        $csvHeaders = $csvService->getFieldsToMap( $validated['upload-file'], $supplied_constants, new \App\Http\Requests\CSVProgramRequest, new \App\Http\Requests\EventRequest );

        return $csvHeaders;
    }

    public function eventFileImport(CSVImportRequest $request, Organization $organization)
    {    
        $fileUpload = $request->validated();

        $validated = $request->validate([
            'fieldsToMap' => 'required|json'
        ]);

        $supplied_constants = collect(
            [
                'organization_id' => $organization->id
            ]
        );

        $csvImport = new CsvImport;
        $newCsvImport = $csvImport->createCsvImport($fileUpload + [
            'organization_id'       => $organization->id,
            'csv_import_type_id'    => CsvImportType::getIdByType('add_events')
        ]);

        ImportEventValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants);

        // $csvService = new CSVimportService;
        // $importData =  $csvService->importFile($request->file('upload-file'), $request->fieldsToMap, $supplied_constants);
        // return $importData;

        /*
        if ( empty($importData['errors']) )
        {
            //import data
            try
            {
                $eventIds = DB::transaction(function() use ($importData, $supplied_constants) {
                
                    $createdEventIds = [];
                    $event = new Event;

                    foreach ($importData['EventRequest'] as $key => $eventData) 
                    {    
                        $newEvent = $event->create($eventData + [
                            'organization_id' => $supplied_constants['organization_id'],
                            'program_id' => $importData['CSVProgramRequest'][$key]['program_id']
                        ]);
                        $createdEventIds[] = $newEvent->id;
                    }
                });  
            }
            catch (\Throwable $e)
            {
                $errors = ['errors' => 'ImportEventForProgramJob with error: ' . $e->getMessage() . ' in line ' . $e->getLine()];
                Log::error($errors);
                return $errors;
            } 
            // return $createdUserIds;
              
        }
        else {
            Log::error(json_encode($importData));
            return $importData;
            //return errors via notifications
        }
        */

    }

}
