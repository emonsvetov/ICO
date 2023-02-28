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

use App\Jobs\ImportUserForProgramValidationJob;
use Illuminate\Support\Collection;
// use Illuminate\Support\Facades\Storage;

// use Aws\S3\S3Client;

// Remove after test
use App\Models\User;
use App\Models\Program;
use App\Http\Traits\UserImportTrait;

use DB;
use Mail;
use DateTime;
// use Illuminate\Support\Facades\Notification;
use App\Notifications\CSVImportNotification;
use App\Mail\templates\WelcomeEmail;

class UserImportController extends Controller
{

    use UserImportTrait;
    
    public function index(Organization $organization)
    {
        $query = CsvImport::withOrganization($organization);

        $csvImports = $query->whereRelation('csv_import_type', 'context', '=', 'Users')->get();
        
        return response($csvImports);
    }


    public function show(Organization $organization, CsvImport $csvImport)
    {
        $context = CsvImportType::find($csvImport['csv_import_type'])[0]['context'];

        if ($context === 'Users')
        {
            $csvImport['notifications'] = $csvImport->notifications;

            return response($csvImport);
        }
        return response([]);
    }

    
    public function userHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization)
    {
        //Use policies to determine if has rights and correct organization
        //Setup Request file
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        
        $csvHeaders = $csvService->getFieldsToMap( 
            $validated['upload-file'], $supplied_constants,  
            new \App\Http\Requests\CSVProgramRequest, 
            new \App\Http\Requests\UserRequest
        );

        return $csvHeaders;
    }


    public function addAwardUserHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization)
    {
        //Use policies to determine if has rights and correct organization
        //Setup Request file
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        
        $csvHeaders = $csvService->getFieldsToMap( 
            $validated['upload-file'], $supplied_constants,  
            new \App\Http\Requests\CSVProgramRequest, 
            new \App\Http\Requests\UserRequest, 
            new \App\Http\Requests\EventXmlDataRequest 
        );

        return $csvHeaders;
    }


    public function awardUserHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization)
    {
        //Use policies to determine if has rights and correct organization
        //Setup Request file
        $validated = $request->validated();

        $supplied_constants = collect(
            ['organization_id' => $organization->id]
        );
        
        $csvHeaders = $csvService->getFieldsToMap( 
            $validated['upload-file'], $supplied_constants,  
            new \App\Http\Requests\CSVProgramRequest, 
            new \App\Http\Requests\UserUpdateRequest, 
            new \App\Http\Requests\EventXmlDataRequest 
        );

        return $csvHeaders;
    }


    public function userFileImport(CSVImportRequest $request, Organization $organization)
    {    
        $fileUpload = $request->validated();

        $validated = $request->validate([
            'fieldsToMap' => 'required|json',
            'setups' => 'required|json'
        ]);

        // Check type
        $setups = json_decode($validated['setups'], true);
        $userModel = isset($setups['UserRequest']) ? 'UserRequest' : 'UserUpdateRequest';
        $requestType = isset($setups['UserRequest']) ? $setups['UserRequest']['type'] : $setups['UserUpdateRequest']['type'];
        $type = CsvImportType::getIdByType($requestType);

        if (empty($type)) 
        {   
            return response(["errors" => [
                'Setups' => [
                    $userModel => [
                        'type' => "'" . $srequestType . "' does not exist"
                    ]
                ]
            ]]);
        }

        $supplied_constants = collect(
            [
                'organization_id' => $organization->id
            ]
        );

        $csvImport = new CsvImport;
        $newCsvImport = $csvImport->createCsvImport($fileUpload + [
            'organization_id'       => $organization->id,
            'csv_import_type_id'    => $type
        ]);

        // ImportUserForProgramValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants, $validated['setups']);
        
        // remove after test
        $csvService = new CSVimportService;
        $importData =  $csvService->importFile($newCsvImport, $request->fieldsToMap, $supplied_constants, $request->setups);
        // return $importData;

        if ( empty($importData['errors']) )
        {
            $type = CsvImportType::find( $newCsvImport->csv_import_type_id)->type;

            switch ($type)
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
                'csv_import_id' => $csvImportId,
                'errors' => $importData['errors']
            ];

            $newCsvImport->notify(new CSVImportNotification($notifData));
        }
        
        //$file->getRealPath();
    }

}
