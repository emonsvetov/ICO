<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeelingSurveyPolicy
{
    use HandlesAuthorization;

    private function __authCheck($authUser, $organization, $program): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
	public function create(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        if($user->isAdmin()) return true;
        return $user->isManagerToProgram( $program ) || $user->isParticipantToProgram( $program ) || $user->can('can-feeling-survey');
    }
}
