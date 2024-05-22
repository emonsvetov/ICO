<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Notifications\CSVImportNotification;
use App\Http\Requests\CSVImportRequest;
use App\Services\CSVimportHeaderService;
use App\Services\CSVimportService;
use App\Http\Traits\UserImportTrait;

use App\Models\Organization;
use App\Models\Program;
use App\Models\CsvImport;
use App\Models\CsvImportType;

class ImportController extends Controller
{
    use UserImportTrait;
    public $notifyOnError = false;
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
        try{
            $csvHeaders = $csvService->getFieldsToMap(
                $validated['upload-file'], $supplied_constants,
                $importRequestClass
            );

            return $csvHeaders;
        } catch (\Exception $e) {
            return response(['errors' => sprintf('Could not process due to error:%s in line:%d of file:%s.', $e->getMessage(), $e->getLine(), $e->getFile())], 422);
        }

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

        $method = "csvImport" . ucfirst(camel_case($csvImportType->type));
        if( !method_exists($this, $method))  {
            return response(['message'=>'Errors while importing data', 'errors' => sprintf('Import method:"%s" not implemented', $method)], 422);
        }

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

        if ( empty($importData['errors']) )
        {
            $results = $this->{$method}($newCsvImport, $importData, $supplied_constants);
            if( !isset($results['errors'])) {
                return response(['csvImport' => $newCsvImport, 'importData' => $importData, 'results' => $results]);
            }   else {
                if( $this->notifyOnError )
                {
                    $notifData = [
                        'csv_import_id' => $csvImportType->id,
                        'errors' => $results['errors']
                    ];
                    $newCsvImport->notify(new CSVImportNotification($notifData));
                }
                return response(['message'=>'Errors while validating import data', 'errors' => $results['errors']], 422);
            }
        }
        else
        {
            if( $this->notifyOnError )
            {
                $notifData = [
                    'csv_import_id' => $csvImportType->id,
                    'errors' => $importData['errors']
                ];
                $newCsvImport->notify(new CSVImportNotification($notifData));
            }
            return response(['message'=>'Errors while validating import data', 'errors' => $importData['errors']], 422);
        }
    }
}
