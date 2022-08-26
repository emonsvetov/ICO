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
// use App\Models\User;
// use App\Models\Program;

// use DB;
// use Mail;
// use DateTime;
// use Illuminate\Support\Facades\Notification;
// use App\Notifications\CSVImportNotification;
// use App\Mail\templates\WelcomeEmail;

class UserImportController extends Controller
{

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
        
        $csvHeaders = $csvService->getFieldsToMap( $validated['upload-file'], $supplied_constants,  new \App\Http\Requests\CSVProgramRequest, new \App\Http\Requests\UserRequest );

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
        $type = CsvImportType::getIdByType($setups['UserRequest']['type']);

        if (empty($type)) 
        {   
            return response(["errors" => [
                'Setups' => [
                    'UserRequest' => [
                        'type' => "'" . $setups['UserRequest']['type'] . "' does not exist"
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

        ImportUserForProgramValidationJob::dispatch($newCsvImport, $validated['fieldsToMap'], $supplied_constants, $validated['setups']);

        
        // remove after test
        // $csvService = new CSVimportService;
        // $importData =  $csvService->importFile($newCsvImport, $request->fieldsToMap, $supplied_constants, $request->setups);
        // return $importData;

        /*
        if ( empty($importData['errors']) )
        {
            //import data
            try
            {
                $userIds = DB::transaction(function() use ($importData, $supplied_constants) {
                
                    $createdUserIds = [];
                    $user = new User;

                    $mail = $importData['setups']['UserRequest']['mail'] ?? 0;

                    foreach ($importData['UserRequest'] as $key => $userData) 
                    {    
                        $employee_number = $userData['employee_number'] ?? '';
                        $updated = 0;

                        $dob = $userData['dob'] ?? null;
                        // Split DOB
                        if ($dob)
                        {
                            $dob = new DateTime($dob);
                            $day = $dob->format('d');
                            $month = $dob->format('m');
                            $year = $dob->format('Y');
                        }

                        if (!empty($employee_number))
                        {
                            $updated = $user->where([
                                    ['organization_id', $supplied_constants['organization_id']],
                                    ['employee_number', $employee_number]
                                ])
                                ->update($userData);
                        }
                        
                        if (!$updated)
                        {
                            // $userData['roles'] = !empty($userData['roles']) ? $userData['roles'] : $importData['setups']['UserRequest']['roles'];
                            // print_r($userData);exit;
                            
                            $newUser = $user->createAccount($userData + [
                                'organization_id' => $supplied_constants['organization_id'],
                                'password' => str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(60/strlen($x)) ))
                            ]);
                            $createdUserIds[] = $newUser->id;

                            // ADD NEW USER TO PROGRAM ?
                            $program = Program::find($importData['CSVProgramRequest'][$key]['program_id']);
                            $program->users()->sync( [ $newUser->id ], false );

                            // A NEW USER HAS ROLE AS A PARTICIPANT ?
                            $roles = !empty($userData['roles']) ? $userData['roles'] : $importData['setups']['UserRequest']['roles'];
                            if( !empty($roles) )
                            {
                                $newUser->syncProgramRoles($program->id, $roles);
                            }
                            

                            if ($mail)
                            {
                                // What is contact program host?
                                $message = new WelcomeEmail($newUser->first_name, $newUser->email, "");
                                Mail::to($newUser->email)->send($message);
                            }
                        }
                    }
                // WHAT MUST WE DO WITH USER ROLES
                });  
            }
            catch (\Throwable $e)
            {
                $errors = ['errors' => 'ImportUserForProgramJob with error: ' . $e->getMessage() . ' in line ' . $e->getLine()];
                return $e;
            } 
            // return $createdUserIds;
              
        }
        else {
            // return $importData;

            $notifData = [
                'csv_import_id' => $csvImportId,
                'errors' => $importData['errors']
            ];

            // Who will get the notification?
            // $organization = Organization::find($supplied_constants['organization_id']);
            $organization->notify(new CSVImportNotification($notifData));

            //return errors via notifications
        }
        */
        
        //$file->getRealPath();
    }

}
