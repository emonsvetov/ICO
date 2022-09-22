<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program = null)   {
        if( $authUser->organization_id != $organization->id) return false;
        if( $program && $program->organization_id != $organization->id) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization)
    {
        if( !$this->__preAuthCheck($user,$organization) ) return false;
        if( $user->isAdmin() )
        {
            return true;
        }
        return $user->can('program-list');
    }

    public function view(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('program-view');
    }

    public function create(User $user, Organization $organization)
    {
        if( !$this->__preAuthCheck($user, $organization) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-create');
    }

    public function update(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() )
        {
            return true;
        }
        return $user->can('program-update');
    }

    public function delete(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-delete');
    }

    public function move(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-move');
    }

    public function restore(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-restore');
    }

    public function listPayments(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-list-payments');
    }
}
