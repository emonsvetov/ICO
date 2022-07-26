<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class SubprogramPolicy
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
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user,$organization,$program) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram($program) ) return true;
        return $user->can('subprogram-list');
    }

    public function unlink(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('subprogram-unlink');
    }
}
