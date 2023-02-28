<?php

namespace App\Services\Program;

use App\Events\UserInvited;
use App\Models\Program;
use App\Models\User;
use App\Models\Role;

class ProgramUserService
{

    public function __construct(
    )
    {
        
    }

    public function create(Program $program, $validated )
    {
        $validated['organization_id'] = $program->organization_id;
        $validated['email_verified_at'] = now();

        $user = User::createAccount($validated);

        if ($user) {
            $program->users()->sync([$user->id], false);
            if (isset($validated['roles'])) {
                $user->syncProgramRoles($program->id, $validated['roles']);
            }
            if (!empty($validated['send_invite'])) {
                $participantRoleId = Role::getParticipantRoleId();
                if( in_array($participantRoleId, $validated['roles']))
                {
                    // $user = User::find( 590 );
                    $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
                    event(new UserInvited($user, $program, $token));
                }
            }
            return $user;
        }
    }
}
