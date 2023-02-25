<?php
namespace App\Http\Traits;

use App\Models\User;
use App\Models\Award;
use App\Models\Program;
use App\Models\Organization;
use App\Mail\templates\WelcomeEmail;
use App\Notifications\CSVImportNotification;

use DB;
use Mail;
use DateTime;

trait UserImportTrait 
{

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
                            'password' => str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(60/strlen($x)) ))
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
                            $message = new WelcomeEmail($newUser->first_name, $newUser->email, $program);
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
            $awardIds = DB::transaction(function() use ($data, $suppliedConstants) {
                
                $createdAwardIds = [];
                $award = new Award;

                foreach ($data['AwardRequest'] as $key => $awardData) 
                {    
                    $program = Program::find($data['CSVProgramRequest'][$key]['program_id']);

                    // Who is the awarder? Organization Super Admin?
                    $user = User::find($awardData['user_id']);

                    $newAward = $award->create(
                        (object) ($awardData + 
                        [
                            'organization_id' => $suppliedConstants['organization_id'],
                            'program_id' => $program->id
                        ]),
                        $program,
                        $user
                    );

                    $createdAwardIds[] = $newAward->id;
                    
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

    }

}