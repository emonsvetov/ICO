<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('user-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function view(User $authenticatedUser, User $user)
    {
        return $authenticatedUser->id === $user->id ||  $authenticatedUser->can('user-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('user-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function update(User $authenticatedUser, User $user)
    {
        return $authenticatedUser->id === $user->id || $authenticatedUser->can('user-update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        return $user->can('user-delete');
    }

    public function viewAnyProgram(User $user)
    {
        return $user->can('user-program-list');
    }

    public function addProgram(User $user)
    {
        return $user->can('user-program-add');
    }

    public function removeProgram(User $user, Program $program)
    {
        if( $user->organization_id != $prgram->organization_id) return false;
        return $user->can('user-program-remove');
    }    
    
    public function getProgramPermission(User $user, Program $program)
    {
        if( $user->organization_id != $prgram->organization_id) return false;
        return $user->can('user-program-permission');
    }
}
