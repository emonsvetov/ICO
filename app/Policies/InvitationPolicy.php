<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Traits\HasProgramRoles;

class InvitationPolicy
{
    use HandlesAuthorization;

    private function __authCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
	public function invite(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        if($user->isAdmin()) return true;
        return $user->isManagerToProgram( $program ) || $user->can('can-invite');
    }
	public function resend(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        if($user->isAdmin()) return true;
        return $user->isManagerToProgram( $program ) || $user->can('can-invite-resend');
    }
	public function accept(User $user)
    {
        // if ( !$this->__authCheck($user, $organization, $program ) )
        // {
        //     return false;
        // }
        return true;
    }
}
