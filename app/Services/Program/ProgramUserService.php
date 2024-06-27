<?php

namespace App\Services\Program;

use App\Services\UserService;
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
        //dd($user);
        if ($user) {
            $program->users()->sync([$user->id], false);
            if (isset($validated['roles'])) {
                $user->syncProgramRoles($program->id, $validated['roles']);
            }
            if ( ! empty($validated['unit_number']) ) {
                $userService = new UserService;
                $userService->updateUnitNumber($user, $validated['unit_number']);
            }
            if (!empty($validated['position_level'])) {
                $userService = new UserService;
                $userService->updatePositionLevel($user, $validated['position_level'],$program->id);
            }
            if ( isset($validated['is_organization_admin']) ) {
                $user->syncOrgAdminRoleByProgram($program, $validated['is_organization_admin']);
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

    public function update(Program $program, User $user, $validated )
    {
        $userService = new UserService;
        if( !empty($validated['roles']) )   {
            $validated['program_roles'] = $validated['roles'];
            unset($validated['roles']);
        }

        $userService->update($user, $validated,$program->id);

        if ( ! empty($validated['program_roles'])) {
            $user->syncProgramRoles($program->id, $validated['program_roles']);
        }

        if ( isset($validated['is_organization_admin']) ) {
            $user->syncOrgAdminRoleByProgram($program, $validated['is_organization_admin']);
        }
        return $user;
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
