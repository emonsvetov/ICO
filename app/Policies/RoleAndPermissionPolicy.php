<?php

namespace App\Policies;

//use App\Models\Permission;
//use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoleAndPermissionPolicy
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
        return $user->permissions()->contains('view-permission-role');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->permissions()->contains('view-permission-role');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->permissions()->contains('create-permission-role');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->permissions()->contains('update-permission-role');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->permissions()->contains('delete-permission-role');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
    /*public function restore(User $user, Permission $permission)
    {
        //
    }*/

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
   /* public function forceDelete(User $user, Permission $permission)
    {
        //
    }*/
    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
    public function assign(User $user)
    {
        return $user->permissions()->contains('update-permission-role');
    }
     /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Permission  $permission
     * @return mixed
     */
    public function revoke(User $user)
    {
        return $user->permissions()->contains('delete-permission-role');
    }
}
