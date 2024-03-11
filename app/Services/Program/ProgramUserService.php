<?php

namespace App\Services\Program;

use App\Events\UserInvited;
use App\Models\Program;
use App\Models\User;
use App\Models\Role;

class ProgramUserService
{
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
                // $participantRoleId = Role::getParticipantRoleId();
                // if( in_array($participantRoleId, $validated['roles']))
                {
                    $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
                    event(new UserInvited($user, $program, $token));
                }
            }
            return $user;
        }
    }

    public function attachBalanceToUser(User $user, Program $program ) {
        $amount_balance = $user->readAvailableBalance($program, $user);
        $factor_valuation = $program->factor_valuation;
        $points_balance = $amount_balance * $program->factor_valuation;

        $user->balance = $amount_balance;
        $user->points_balance = $points_balance;
        $user->factor_valuation = $factor_valuation;
        return $user;
    }
}
