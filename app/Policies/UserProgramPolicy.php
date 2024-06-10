<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class UserProgramPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $user = null, $program = null): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $user && !$organization->hasUser($user)) return false;
        // if( $program && $organization->id != $program->organization_id) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization)
    {
        if(!$this->__preAuthCheck($authUser, $organization)) return false;
        if($authUser->isAdmin()) return true;
        return $user->can('user-program-list');
    }

    public function add(User $authUser, Organization $organization, User $user)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $user)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('user-program-add');
    }

    public function remove(User $authUser, Organization $organization, User $user, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $user, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('user-program-remove');
    }

    public function getRoles(User $authUser, Organization $organization, User $user,  Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $user, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('user-program-roles');
    }
}
