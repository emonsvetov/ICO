<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExternalCallbackPolicy
{
    use HandlesAuthorization;

    private function __authCheck($authUser, $organization, $program, $user = null): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }

    public function getGoalMetProgramCallbacks(User $authUser, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }

        if( $authUser->isManagerToProgram( $program ) ) return true;
        return $__authCheck->can('external-callback-list');
    }

    public function getGoalExceededProgramCallbacks(User $authUser, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }

        if( $authUser->isManagerToProgram( $program ) ) return true;
        return $__authCheck->can('external-callback-list');
    }
}
