<?php
namespace App\Http\Traits;

use App\Models\User;
use App\Models\Award;
use App\Models\Program;
use App\Models\CsvImport;
use App\Models\Organization;
use App\Models\EventXmlData;
use App\Mail\templates\WelcomeEmail;
use App\Mail\templates\AwardBadgeEmail;
use App\Notifications\CSVImportNotification;

use DB;
use Mail;
use DateTime;

trait UserImportTrait 
{

    public function createUserPassword()
    {
        return str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(60/strlen($x)) ));
    }

    public function addUser($csvImport, $data, $suppliedConstants)
    {
        try
        {
            $userIds = DB::transaction(function() use ($data, $suppliedConstants) {
                
                $createdUserIds = [];
                $user = new User;

                $mail = $data['setups']['UserRequest']['mail'] ?? 0;

                foreach ($data['UserRequest'] as $key => $userData) 
                {    
                    $employeeNumber = $userData['employee_number'] ?? null;
                    $updated = 0;

                    $dob = $userData['dob'] ?? null;
                    if ($dob)
                    {
                        $dob = new DateTime($dob);
                        $day = $dob->format('d');
                        $month = $dob->format('m');
                        $year = $dob->format('Y');
                    }

                    if (!empty($employeeNumber))
                    {
                        $updated = $user->where([
                                ['organization_id', $suppliedConstants['organization_id']],
                                ['employee_number', $employeeNumber]
                            ])
                            ->update($userData);
                        // do we need to update user roles?
                    }
                    
                    if (!$updated)
                    {
                        $newUser = $user->createAccount($userData + [
                            'organization_id' => $suppliedConstants['organization_id'],
                            'password' => $this->createUserPassword()
                            ]);
                        $createdUserIds[] = $newUser->id;

                        // ADD NEW USER TO PROGRAM ?
                        $program = Program::find($data['CSVProgramRequest'][$key]['program_id']);
                        $program->users()->sync( [ $newUser->id ], false );

                        // A NEW USER HAS ROLE AS A PARTICIPANT ?
                        $roles = !empty($userData['roles']) ? $userData['roles'] : $data['setups']['UserRequest']['roles'];
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
            });

            $csvImport->update(['is_imported' => 1]);
        }
        catch (\Throwable $e)
        {
            $csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
        }
    }


    public function updateUser($csvImport, $data, $suppliedConstants)
    {

    }


    public function deactivateUser($csvImport, $data, $suppliedConstants)
    {

    }


    public function awardUser($csvImport, $data, $suppliedConstants)
    {
        try
        {
            $eventXmlDataIds = DB::transaction(function() use ($data, $suppliedConstants) {
                
                // AWARD USER
                $createEventXmlDataIds = [];
                $eventXmlData = new EventXmlData;

                $mail = $data['setups']['UserUpdateRequest']['mail'] ?? 0;

                foreach ($data['EventXmlDataRequest'] as $key => $eventXml) 
                {

                    $user = User::where('email', $data['UserUpdateRequest'][$key]['email'])->first();

                    //$eventXml = $data['EventXmlDataRequest'][$key];

                    $newEventXmlData = $eventXmlData->create($eventXml + 
                        [
                            'awarder_account_holder_id' => $user->account_holder_id
                        ]);

                    $createEventXmlDataIds[] = $newEventXmlData->id;
                    
                    if ($mail)
                    {
                        // What is contact program host?
                        $email = $data['EventXmlDataRequest'][$key]['email'];
                        $message = new AwardBadgeEmail($user->first_name, $email, "");
                        Mail::to($email)->send($message);
                    }
                }

            });

            $csvImport->update(['is_imported' => 1]);
        }
        catch (\Throwable $e)
        {
            $csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
        }
    }


    public function moveUser($csvImport, $data, $suppliedConstants)
    {

    }


    public function addAndAwardUser($csvImport, $data, $suppliedConstants)
    {
        try
        {
            $userIds = DB::transaction(function() use ($data, $suppliedConstants) {
                
                $createdUserIds = [];
                $user = new User;

                $mail = $data['setups']['UserRequest']['mail'] ?? 0;

                foreach ($data['UserRequest'] as $key => $userData) 
                {    
                    // CREATE A NEW USER
                    $dob = $userData['dob'] ?? null;
                    if ($dob)
                    {
                        $dob = new DateTime($dob);
                        $day = $dob->format('d');
                        $month = $dob->format('m');
                        $year = $dob->format('Y');
                    }

                    $newUser = $user->createAccount($userData + [
                        'organization_id' => $suppliedConstants['organization_id'],
                        'password' => $this->createUserPassword()
                        ]);
                    $createdUserIds[] = $newUser->id;

                    // ADD NEW USER TO PROGRAM ?
                    $program = Program::find($data['CSVProgramRequest'][$key]['program_id']);
                    $program->users()->sync( [ $newUser->id ], false );

                    // A NEW USER HAS ROLE AS A PARTICIPANT ?
                    $roles = !empty($userData['roles']) ? $userData['roles'] : $data['setups']['UserRequest']['roles'];
                    if( !empty($roles) )
                    {
                        $newUser->syncProgramRoles($program->id, $roles);
                    }

                    // AWARD NEW USER
                    $createEventXmlDataIds = [];
                    $eventXmlData = new EventXmlData;

                    $eventXml = $data['EventXmlDataRequest'][$key];

                    $newEventXmlData = $eventXmlData->create($eventXml + 
                        [
                            'awarder_account_holder_id' => $newUser->account_holder_id
                        ]);

                    $createEventXmlDataIds[] = $newEventXmlData->id;
                    
                    if ($mail)
                    {
                        // What is contact program host?
                        $message = new WelcomeEmail($newUser->first_name, $newUser->email, "");
                        Mail::to($newUser->email)->send($message);
                    }
                    
                }
            });

            $csvImport->update(['is_imported' => 1]);

            return $userIds;
        }
        catch (\Throwable $e)
        {
            return $e->getMessage();
            $csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
        }
        

        // TO DO: Send import report to user
        $mailImportStatus = $mail = $data['setups']['CSVProgramRequest']['mail'] ?? 0;

        if ($mailImportStatus)
        {
            // $this->emailImportReport($csvImport->id);
            $this->emailImportReport(25);
        }
    }


    public function emailImportReport($csvImportId)
    {
        $csvImport = CsvImport::find($csvImportId);
        $notification = $csvImport->notifications;

        // print_r($notification->toArray());
        // exit;

        if (!empty($notification))
        {
            $errorArr = $notification->toArray()[0]['data']['errors'];
            // $errorArr = json_decode($notification->data, true)['errors'];

            // print_r($errorArr);
            // exit;

            $headers = array(
                'Line', 'Field'
            );

            $csv = array();

            foreach ($errorArr as $line => $errors)
            {
                foreach ($errors as $modelError)
                {
                    foreach ($modelError as $model => $fieldErrors)
                    {
                        foreach ($fieldErrors as $fieldname => $error)
                        {
                            $csv[] = [$line, implode('. ', $error)];
                        }
                    }
                }
            }

            // print_r($csv);
            // exit;
        }
    }

}