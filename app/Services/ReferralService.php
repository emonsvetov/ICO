<?php

namespace App\Services;

use App\Events\UserInvited;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use App\Notifications\ReferralNotification;
use App\Models\Referral;
use App\Models\Program;
use App\Models\ReferralNotificationRecipient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class ReferralService
{
    public function refer(Organization $organization, Program $program, $data )  {
        $referral = Referral::create($data);
        $sender = \App\Models\User::select(['first_name', 'last_name'])->find($data['sender_id']);

        $notification =  [
            'sender_first_name' => $sender ? $sender->first_name : 'firstname',
            'sender_last_name' => $sender ? $sender->last_name : 'lastname',
            'recipient_first_name' => $referral->recipient_first_name,
            'recipient_last_name' => $referral->recipient_last_name,
            'recipient_email' => $referral->recipient_email,
            'recipient_area_code' => $referral->recipient_area_code,
            'recipient_phone' => $referral->recipient_phone,
            'message' => $referral->message,
            'program' => $program
        ];

        $this->__notify($organization, $program, $notification);

        return $referral;
    }

    protected function __notify(Organization $organization, Program $program, $notification )  {

        $referralManagers = ReferralNotificationRecipient::getIndexData($organization, $program, []);

        if($referralManagers->isNotEmpty()){
            foreach( $referralManagers as $referralManager )  {
                $referralManager->sendReferralNotification((object)(
                    $notification +
                    [
                        'contactFirstName' => $referralManager->referral_notification_recipient_name
                    ]
            ));
            }
        }
        else if( $program->getManagers()->isNotEmpty() )   {
            foreach( $program->getManagers() as $manager )  {
                $manager->notify(new ReferralNotification((object)(
                    $notification + 
                    [
                        'contactFirstName' => $manager->first_name
                    ]
                )));
            }
        }
        else if($program->parent()->exists()){
            $parent = $program->parent()->first();
            if($parent -> getManagers()->isNotEmpty()){
                foreach( $parent->getManagers() as $manager )  {
                    $manager->notify(new ReferralNotification((object)(
                        $notification + 
                        [
                            'contactFirstName' => $manager->first_name
                        ]
                    )));
                }
            }
        }
    }

    public function referParticipant(Organization $organization, Program $program, $data )  {
        $userData =  [
            'first_name' => $data['sender_first_name'],
            'last_name' => $data['sender_last_name'],
            'email' => $data['sender_email'],
        ];        
        $user = User::where('email', $data['sender_email'])->first();

        if($user == null){
            $newUser = $this->__invite($organization, $program, $userData);
            $referral = Referral::create($data);
            $notification =  [
                'sender_first_name' => $referral->sender_first_name,
                'sender_last_name' => $referral->sender_last_name,
                'recipient_first_name' => $referral->recipient_first_name,
                'recipient_last_name' => $referral->recipient_last_name,
                'recipient_email' => $referral->recipient_email,
                'recipient_area_code' => '',
                'recipient_phone' => '',
                'message' => $referral->message,
                'program' => $program
            ];
            $this->__notify($organization, $program, $notification);
            return (
                [
                    'success' => true,
                    'referral' => $referral,
                    'user' => $newUser
                ]
            );
        }
        else{
            $res = $this->__addToProgram($program, $user);
            if($res['success']){
                $referral = Referral::create($data);
                $notification =  [
                    'sender_first_name' => $referral->sender_first_name,
                    'sender_last_name' => $referral->sender_last_name,
                    'recipient_first_name' => $referral->recipient_first_name,
                    'recipient_last_name' => $referral->recipient_last_name,
                    'recipient_email' => $referral->recipient_email,
                    'recipient_area_code' => '',
                    'recipient_phone' => '',
                    'message' => $referral->message,
                    'program' => $program
                ];
                $this->__notify($organization, $program, $notification);
                return (
                    [
                        'success' => true,
                        'referral' => $referral,
                        'user' => $user
                    ]
                );
            }
            else{
                return (
                    [
                        'success' => false,
                        'errors' => $res['errors'],
                    ]
                );
            }
        }
    }
    protected function __addToProgram(Program $program, User $user){
        DB::beginTransaction();

        try {
            $rols = $user->roles()->wherePivot( 'program_id', '=', $program->id )->get();
            
            $roles = collect($rols)->pluck('id')->toArray();
            
            $roles[] = Role::getIdByName(config('roles.participant'));
            $program->users()->sync([$user->id], false);
            $user->syncProgramRoles($program->id, $roles);
          
            DB::commit();
            return (['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            $error = "Error inviting user to program";
            return ([
                'success' => false,
                'errors' => $error,
            ]);
        }
    }

    protected function __invite(Organization $organization, Program $program, $userData){
        DB::beginTransaction();
        try {
            $userData['organization_id'] = $organization->id;
            $generatedPassword = rand();
            $userData['password'] = $generatedPassword;
            $user = User::createAccount($userData);
            $token = Password::broker()->createToken($user);

            $roles[] = Role::getIdByName(config('roles.participant'));

            if (!empty($roles)) {
                $program->users()->sync([$user->id], false);
                $user->syncProgramRoles($program->id, $roles);
            }
            event(new UserInvited($user, $program, $token));
            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            $error = "Error inviting user to program";
            return $error;
        }
    }
}

