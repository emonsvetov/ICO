<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserStatusPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $user = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $user && $organization->id != $user->organization_id) return false;
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
        return $user->can('user-status-list');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function update(User $user, Organization $organization, User $model)
    {
        if ( !$this->__preAuthCheck($user, $organization, $model) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->can('user-status-update');
    }
}
