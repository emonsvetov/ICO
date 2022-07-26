<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
        return $user->can('user-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function view(User $authUser, Organization $organization, User $user)
    {
        if ( !$this->__preAuthCheck($authUser, $organization, $user) )
        {
            return false;
        }
        if( $authUser->isAdmin() ) return true;
        return $authUser->can('user-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, Organization $organization)
    {
        if ( !$this->__preAuthCheck($user, $organization) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->can('user-create');
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
        return $user->id === $model->id || $user->can('user-update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function delete(User $user, Organization $organization, User $model)
    {
        if ( !$this->__preAuthCheck($user, $organization, $model) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->can('user-delete');
    }
}
