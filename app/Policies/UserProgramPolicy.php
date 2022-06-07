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

    private function __preAuthCheck($user, $organization, $model = null): bool
    {
        if( $organization->id != $user->organization_id ) return false;
        if( $model && $organization->id != $model->organization_id) return false;
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
        if ( !$this->__preAuthCheck($user, $organization) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->can('user-program-list');
    }
  
    public function add(User $authUser, User $user)
    {
        return $authUser->can('user-program-add');
    }

    public function remove(User $authUser, Program $program, User $user)
    {
        return $authUser->can('user-program-remove');
    }

    public function getRoles(User $authUser, User $user,  Program $program)
    {
        return $authUser->can('user-program-roles');
    }
}
