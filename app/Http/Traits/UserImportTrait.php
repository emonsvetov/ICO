<?php
namespace App\Http\Traits;

use App\Mail\templates\ProcessCompletionReportEmail;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateType;
use App\Models\Event;
use App\Models\User;
use App\Models\Award;
use App\Models\Program;
use App\Models\CsvImport;
use App\Models\Organization;
use App\Models\EventXmlData;
use App\Mail\templates\WelcomeEmail;
use App\Mail\templates\AwardBadgeEmail;
use App\Notifications\CSVImportNotification;
use \Illuminate\Support\Facades\DB;

use App\Services\AwardService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use DateTime;
use Exception;

trait UserImportTrait
{
    public $userRequestTypes = [
        'UserRequest', 'UserUpdateRequest', 'UserImportTypeRequest'
    ];

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

    private function getUserByData($userData){
        $currentUser = null;

        if (isset($userData['external_id']) && $userData['external_id']){
            $currentUser = User::getByExternalId((int)$userData['external_id']);
        }
        if (!$currentUser && isset($userData['email'])){

            $currentUser = User::getByEmail($userData['email']);
        }
        return $currentUser;
    }

    /**
     * @param Organization $organization
     * @param Program $program
     * @param array $userData
     * @param User|null $currentUser
     * @return array
     */
    private function changeUserData(Organization $organization, Program $program, array $userData, $currentUser): array
    {
        $userData = $this->changeUserDataLiv($organization, $program, $userData, $currentUser);
        return $userData;
    }

    /**
     * @param Organization $organization
     * @param Program $program
     * @param array $userData
     * @param User|null $currentUser
     * @return array
     */
    private function changeUserDataLiv(Organization $organization, Program $program, array $userData, $currentUser): array
    {
        return $userData; // task changed
        if ($currentUser) {

        } else {
            $emailTemplateTypeId = EmailTemplateType::getIdByType(EmailTemplateType::EMAIL_TEMPLATE_TYPE_WELCOME);
            $programId = $program->id;
            $emailTemplate = EmailTemplate::where('program_id', $programId)
                ->where('email_template_type_id', $emailTemplateTypeId)
                ->where('is_default', 1)
                ->first();

            if ($emailTemplate && $emailTemplate->name === 'Welcome Live High 5') {
                $explode = explode('@', $userData['email']);
                if (isset($explode[1]) && mb_strpos($explode[1], 'brooks.us.com') !== false){
                    $userData['user_status_id'] = User::getIdStatusPendingActivation();
                }
            }
        }

        return $userData;
    }

    public function addAndAwardParticipant($csvImport, $data, $suppliedConstants, AwardService $awardService)
    {
        try
        {
            DB::beginTransaction();
            $userIds = [];

            foreach ($data['UserRequest'] as $key => $userData)
            {
                $program = Program::find($data['CSVProgramRequest'][$key]['program_id']);
                $organization = Organization::find($suppliedConstants['organization_id']);
                $currentUser = $this->getUserByData($userData);
                $userData = $this->changeUserData($organization, $program, $userData, $currentUser);

                $userStatusId = isset($userData['user_status_id']) ? $userData['user_status_id'] : 0;
                if ($userStatusId && $userStatusId == User::TERMINATED){
                    $userStatusId = User::getIdStatusPendingDeactivation();
                } else {
                    $userStatusId = isset($data['setups']['UserRequest']['status']) ? (int)$data['setups']['UserRequest']['status'] : null;
                }
                if (!$userStatusId){
                    throw new \Exception('User Status is Required');
                }
                $userData['user_status_id'] = $userStatusId;

                if($currentUser){
                    $filteredData = array_merge(array(), $userData);
                    unset($filteredData['email']);
                    $updated = $currentUser->update($filteredData);

                    $currentUser->changeStatus([$currentUser->id], $userStatusId);
                    $currentUser = User::find($currentUser->id);
                    $newUser = $currentUser;
                } else {
                    $newUser = (new User)->createAccount($userData + [
                            'organization_id' => $suppliedConstants['organization_id'],
                            'password' => $this->createUserPassword(),
                            'user_status_id' => $data['setups']['UserRequest']['status'] ?? null,
                        ]);
                }

                $needAward = ! User::getByIdAndProgram((int)$newUser->id, (int)$program->id);

                $program->users()->sync( [ $newUser->id ], false );
                $roles = !empty($userData['roles']) ? $userData['roles'] : $data['setups']['UserRequest']['roles'];
                if( !empty($roles) ) {
                    $newUser->syncProgramRoles($program->id, $roles);
                }

                // AWARD NEW USER
                if ($needAward) {
                    $event = null;
                    if ( ! isset($data['AwardRequest'][$key]['event_id'])) {
                        $data['AwardRequest'][$key]['event_id'] = $data['setups']['AwardRequest']['event'];
                    }
                    $event = Event::find($data['AwardRequest'][$key]['event_id']);

                    $awardData = $userData + $data['AwardRequest'][$key] + [
                            'message' => $event->message,
                            'user_id' => [$newUser->id],
                            'organization_id' => $organization->id,
                        ];
                    $requestClassPath = "App\Http\Requests\\AwardRequest";
                    $formRequestClass = new $requestClassPath;
                    $formRequestRules = $formRequestClass->rules();
                    $validator = Validator::make($awardData, $formRequestRules);

                    if ($validator->fails()) {
                        throw new \Exception(print_r($data, true) . $validator->errors()->first());
                    }
                    $managers = $program->getManagers();
                    if (!isset($managers[0])) {
                        throw new \Exception("No managers in program {$program->name}");
                    }

                    $awardService->awardUser($event, $newUser, $managers[0], (object)$awardData, true);

                    $message = new WelcomeEmail($newUser->first_name, $newUser->email, $program);
                    Mail::to($newUser->email)->send($message);
                }

                $userIds[] = $newUser->id;
            }
            $csvImport->update(['is_imported' => 1]);
            CsvImport::deleteFileAutoImportS3($csvImport);

            DB::commit();

            return $userIds;
        } catch (\Exception $e)
        {
            DB::rollBack();
            Log::debug($e->getMessage());
            Log::debug($e->getTraceAsString());
//            print_r($e->getMessage());
//            print_r($e->getTrace());
//            die;
            $csvImport->update(['is_processed' => 0]);
            return 0;
//            $csvImport->notify(new CSVImportNotification(['errors' => $e->getMessage()]));
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

    public function getImportUserRequestType( $validated )
    {
        $setups = json_decode($validated['setups'], true);
        if( $setups )   {
            foreach($this->userRequestTypes as $requestType)   {
                if( isset($setups[$requestType]) &&  isset($setups[$requestType]['type']))  {
                    return $setups[$requestType]['type'];
                }
            }
        }
    }

    public function getImportUserRequestKey( $validated )
    {
        $setups = json_decode($validated['setups'], true);
        if( $setups )   {
            foreach($this->userRequestTypes as $requestType)   {
                if( isset($setups[$requestType]) )  {
                    return $requestType;
                }
            }
        }
    }

    public function csvImportAwardUsers(\App\Models\CsvImport $newCsvImport, $importData, $supplied_constants)
    {
        if( !isset($importData[$newCsvImport->csvImportRequestName]))   {
            return;
        }
        $result = [];
        $data = $importData[$newCsvImport->csvImportRequestName];
        foreach( $data as $row )    {
            $program = Program::find($row['program_id']);
            $user = User::where('email', $row['email'])->first();
            $isParticipant = $user->isParticipantToProgram($program);
            if( !$isParticipant ) continue;
            $data = [
                'event_id' => $row['event_id'],
                'override_cash_value' => $row['override_cash_value'],
                'notes' => $row['notes'],
                'message' => $row['message'],
                'referrer' => $row['referrer'],
                'event_id' => $row['event_id'],
            ];
            $award = (object)($data +
            [
                'organization_id' => $supplied_constants['organization_id'],
                'program_id' => $program->id
            ]);
            $event = Event::findOrFail($data['event_id']);
            $awarder = auth()->user();
            $result[] = (new \App\Services\AwardService)->awardUser($event, $user, $awarder, $award);
        }
        return $result;
    }

    public function csvImportAddAndAwardUsers(\App\Models\CsvImport $newCsvImport, $importData, $supplied_constants)
    {
        if( !isset($importData[$newCsvImport->csvImportRequestName]))   {
            return;
        }
        $data = $importData[$newCsvImport->csvImportRequestName];
        $result = [];
        $errors = [];
        foreach( $data as $row )    {
            $program = Program::find($row['program_id']);
            $user = User::where('email', $row['email'])->first();
            if( !$user ) {
                $registerFields = [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'user_status_id' => User::getIdStatusActive(),
                    'organization_id' => $supplied_constants['organization_id'],
                ];
                $participantRoleId = \App\Models\Role::where('name', config('roles.participant'))->first()->id;
                $registerFields['roles'] = [$participantRoleId];
                $user = (new \App\Services\Program\ProgramUserService)->create($program, $registerFields);
                if( !$user ) {
                    return ['errors' => ['User cannot be created']];
                }
            }   else {
                $isParticipant = $user->isParticipantToProgram($program);
                $participantRoleId = \App\Models\Role::where('name', config('roles.participant'))->first()->id;
                if( !$isParticipant ) {
                    $user->syncProgramRoles($program->id, [$participantRoleId]);
                    // throw new Exception(sprintf("%s %s is not a participant to program", $user->first_name, $user->last_name));
                    // continue;
                }
            }

            $data = [
                'event_id' => $row['event_id'],
                'override_cash_value' => $row['amount'],
                'notes' => $row['notes'],
                'message' => $row['message'],
                'event_id' => $row['event_id'],
            ];
            $award = (object)($data +
            [
                'organization_id' => $supplied_constants['organization_id'],
                'program_id' => $program->id
            ]);
            $event = Event::findOrFail($data['event_id']);
            $awarder = auth()->user();
            $result[] = (new \App\Services\AwardService)->awardUser($event, $user, $awarder, $award);
        }
        return $result;
    }
}
