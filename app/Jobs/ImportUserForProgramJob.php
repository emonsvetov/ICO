<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use App\Mail\templates\WelcomeEmail;
use DB;
use Mail;
use DateTime;

use App\Notifications\CSVImportNotification;

class ImportUserForProgramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $csvImport;
    public $importData;
    public $supplied_constants;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($csvImport, $importData, $supplied_constants)
    {
        $this->csvImport = $csvImport;
        $this->importData = $importData;
        $this->supplied_constants = $supplied_constants;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //start transactions
        //create account holder id
        //create user
        //check if email should be sent to user
        $data = $this->importData;
        $supplied_constants = $this->supplied_constants;

        try
        {
            $userIds = DB::transaction(function() use ($data, $supplied_constants) {
                
                $createdUserIds = [];
                $user = new User;

                $mail = $data['setups']['UserRequest']['mail'] ?? 0;

                foreach ($data['UserRequest'] as $key => $userData) 
                {    
                    $employee_number = $userData['employee_number'] ?? null;
                    $updated = 0;

                    $dob = $userData['dob'] ?? null;
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
                        // do we need to update user roles?
                    }
                    
                    if (!$updated)
                    {
                        $newUser = $user->createAccount($userData + [
                            'organization_id' => $supplied_constants['organization_id'],
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
                            $message = new WelcomeEmail($newUser->first_name, $newUser->email, "");
                            Mail::to($newUser->email)->send($message);
                        }
                    }
                }
            // WHAT MUST WE DO WITH USER ROLES
            });

            $this->csvImport->update(['is_imported' => 1]);
        }
        catch (\Throwable $e)
        {
            $this->csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
        }

    }
}
