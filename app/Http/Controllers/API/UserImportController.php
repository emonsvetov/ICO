<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CsvImportSettings;
use App\Services\UserImportService;
use Aws\S3\S3Client;
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
            new \App\Http\Requests\UserRequest,
            new \App\Http\Requests\AwardRequest
        );

        return $csvHeaders;
    }


    public function addAwardUserHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization, Program $program = null)
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
            new \App\Http\Requests\AwardRequest,
            // new \App\Http\Requests\EventXmlDataRequest
        );

        return $csvHeaders;
    }


    public function awardUserHeaderIndex(CSVImportRequest $request, CSVimportHeaderService $csvService, Organization $organization, Program $program = null)
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
            new \App\Http\Requests\AwardRequest,
            // new \App\Http\Requests\EventXmlDataRequest
        );

        return $csvHeaders;
    }


    public function userFileAutoImport(CSVImportRequest $request, Organization $organization, Program $program = null)
    {
        $fileUpload = $request->validated();
        $validated = $request->validate([
            'fieldsToMap' => 'required|json',
            'setups' => 'required|json'
        ]);

        try {
            $setups = json_decode($validated['setups'], true);
            $fieldsToMap = json_decode($validated['fieldsToMap'], true);
            $requestType = $setups['UserRequest']['type'] ?? $setups['UserUpdateRequest']['type'];
            $type = CsvImportType::getIdByName($requestType);

            if (!$type){
                throw new \RuntimeException("'{$requestType}' does not exist");
            }

            $сsvImport = (new CsvImport)->createCsvAutoImport($fileUpload + [
                    'organization_id' => $organization->id,
                    'csv_import_type_id' => $type,
                    'requestType' => $requestType,
                    'program_key' => $fieldsToMap['CSVProgramRequest']['program_id']
                ]
            );

            return response(['csvImport' => $сsvImport]);
        } catch (\Exception $e) {
            return response(["errors" => [$e->getMessage()]]);
        }
    }

    public function userFileImport(CSVImportRequest $request, Organization $organization, Program $program = null)
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
        $type = CsvImportType::where( function($query) use ($requestType)    {
            $query->orWhere('type', 'LIKE', $requestType);
            $query->orWhere('name', 'LIKE', $requestType);
        })->first();

        if (empty($type))
        {
            return response(["errors" => [
                'Setups' => [
                    $userModel => [
                        'type' => "'" . $requestType . "' does not exist"
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
            'csv_import_type_id'    => $type->id
        ]);

        // ImportUserForProgramValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants, $validated['setups']);

        // remove after test
        $csvService = new CSVimportService;
        $csvService->setImportType( $type );
        $importData =  $csvService->importFile($newCsvImport, $request->fieldsToMap, $supplied_constants, $request->setups);
        // return $importData;

        if ( empty($importData['errors']) )
        {
            switch ($type->type)
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
                'csv_import_id' => $newCsvImport->id,
                'errors' => $importData['errors']
            ];

            $newCsvImport->notify(new CSVImportNotification($notifData));
        }

        //$file->getRealPath();
    }

    public function userSaveSettings(Request $request, Organization $organization, Program $program = null, CSVimportService $csvImportService)
    {
        $validated = $request->validate([
            'fieldsToMap' => 'required|json',
            'setups' => 'required|json'
        ]);

        $csvImportSetting = $csvImportService->saveSettings($validated, $organization);
        return response(['success' => (bool)$csvImportSetting, 'csvImportSetting' => $csvImportSetting]);

    }
}
