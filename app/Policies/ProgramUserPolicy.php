<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ProgramUserPolicy
{
    use HandlesAuthorization;

    private function __authCheck($authUser, $organization, $program, $user = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        if( $user && $program->organization_id != $user->organization_id) return false;
        return true;
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        if($user->isAdmin()) return true;

        return $user->isManagerToProgram( $program ) || $user->can('program-user-list');
    }

    public function view(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }
        return $authUser->isManagerToProgram($program) || $authUser->isParticipantToProgram($program) || $authUser->can('program-user-view');
    }

    public function add(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        if($user->isAdmin()) return true;
        return $user->isManagerToProgram($program) || $user->can('program-user-add');
    }

    public function update(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }
        if($authUser->isAdmin()) return true;
        return $authUser->isManagerToProgram($program) || $authUser->id == $user->id || $authUser->can('program-user-update');
    }

    public function remove(User $authUser, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }

        return $authUser->isManagerToProgram($program) || $authUser->can('program-user-remove');
    }

    public function readBalance(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }

        if($authUser->isManagerToProgram( $program ) || $authUser->isParticipantToProgram( $program )) return true;
        return $user->can('program-user-readbalance');
    }

    public function readEventHistory(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }

        if($authUser->isManagerToProgram( $program ) || $authUser->isParticipantToProgram( $program )) return true;
        return $user->can('program-user-read-event-history');
    }

    public function assignRole(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }

        if($authUser->isAdmin()) return true;
        return $user->can('program-user-assign-role');
    }

    public function readListReclaimablePeerPoints(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }

        if($authUser->isManagerToProgram( $program ) || $authUser->isParticipantToProgram( $program )) return true;
        return $user->can('program-user-read-reclaimable-peer-points');
    }
}
