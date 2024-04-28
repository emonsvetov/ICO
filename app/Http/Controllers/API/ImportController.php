<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Notifications\CSVImportNotification;
use App\Http\Requests\CSVImportRequest;
use App\Services\CSVimportHeaderService;
use App\Services\CSVimportService;

use App\Models\Organization;
use App\Models\Program;
use App\Models\CsvImport;
use App\Models\CsvImportType;

class ImportController extends Controller
{
    public function index(Organization $organization)
    {
        $query = CsvImport::withOrganization($organization);

        $csv_import_type = request()->get('csv_import_type', '');
        if( $csv_import_type )
        {
            $query->whereRelation('csv_import_type', 'context', '=', 'Users');
        }

        $sortby = request()->get('sortby', 'created_at');
        $direction = request()->get('direction', 'desc');
        $orderByRaw = "{$sortby} {$direction}";
        $limit = request()->get('limit', config('global.paginate_limit'));

        $csvImports = $query
        ->with('csv_import_type')
        ->orderByRaw($orderByRaw)
        ->paginate($limit);

        return response($csvImports);
    }
    public function downloadTemplate( Organization $organization, Program $program = null, CsvImportType $csvImportType)
    {
        if( $csvImportType->fields )    {
            $columns = $csvImportType->fields->pluck('csv_column_name');
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename={$csvImportType->type}-template.csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );
            $callback = function() use($columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns->toArray());
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
        // return response()->json(['error' => 'Template not found'], 404);
    }

    public function headerIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization, Program $program, CsvImportType $csvImportType)
    {
        //Use policies to determine if has rights and correct organization
        //Setup Request file
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );

        $requestName = "CSVImport" . ucfirst(camel_case($csvImportType->type)) . 'Request';
        $requestClassPath = "App\Http\Requests\\" . $requestName;
        $importRequestClass = new $requestClassPath;
        $csvHeaders = $csvService->getFieldsToMap(
            $validated['upload-file'], $supplied_constants,
            $importRequestClass
        );

        return $csvHeaders;
    }
    public function fileImport(CSVImportRequest $request, Organization $organization, Program $program, CsvImportType $csvImportType)
    {

        if( !$csvImportType )   {
            return response(['errors' => ['Invalid or no import type provided']], 422);
        }

        $fileUpload = $request->validated();

        $validated = $request->validate([
            'fieldsToMap' => 'required|json',
            'setups' => 'required|json'
        ]);

        // // Check type
        // $setups = json_decode($validated['setups'], true);
        // $userModel = isset($setups['UserRequest']) ? 'UserRequest' : 'UserUpdateRequest';
        // $requestType = isset($setups['UserRequest']) ? $setups['UserRequest']['type'] : $setups['UserUpdateRequest']['type'];
        // $type = CsvImportType::getIdByType($requestType);

        // if (empty($type))
        // {
        //     return response(["errors" => [
        //         'Setups' => [
        //             $userModel => [
        //                 'type' => "'" . $requestType . "' does not exist"
        //             ]
        //         ]
        //     ]]);
        // }

        $supplied_constants = collect(
            [
                'organization_id' => $organization->id
            ]
        );

        $csvImport = new CsvImport;
        $newCsvImport = $csvImport->createCsvImport($fileUpload + [
            'organization_id'       => $organization->id,
            'csv_import_type_id'    => $csvImportType->id
        ]);

        // ImportUserForProgramValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants, $validated['setups']);

        // remove after test
        $csvService = new CSVimportService;
        $importData =  $csvService->importFile($newCsvImport, $request->fieldsToMap, $supplied_constants, $request->setups);
        // return $importData;

        if ( empty($importData['errors']) )
        {
            switch ( $csvImportType->type )
            {
                case 'add_participants':
                    $this->addUser($newCsvImport, $importData, $supplied_constants);
                    break;

                case 'add_managers':
                    $this->addUser($newCsvImport, $importData, $supplied_constants);
                    break;

                case 'add_and_award_users':
                    $results = $this->addAndAwardUser($newCsvImport, $importData, $supplied_constants);
                    break;

                case 'award_users':
                    $results = $this->awardUser($newCsvImport, $importData, $supplied_constants);
                    break;
            }

            return response(['csvImport' => $newCsvImport, 'importData' => $importData, 'results' => $results]);
        }
        else
        {
            // return $importData;
            return response(['message'=>'Errors while validating import data', 'errors' => $importData['errors']], 422);

            $notifData = [
                'csv_import_id' => $csvImportType->id,
                'errors' => $importData['errors']
            ];

            $newCsvImport->notify(new CSVImportNotification($notifData));
        }
    }
}
