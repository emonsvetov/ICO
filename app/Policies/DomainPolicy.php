<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class DomainPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        return true; //allowed until we have roles + permissions
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->permissions()->contains('view-domain');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function view(User $user)
    {
        return $user->permissions()->contains('view-domain');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->permissions()->contains('create-domain');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->permissions()->contains('update-domain');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function delete(User $user)
    {
        return $user->permissions()->contains('delete-domain');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    /*public function restore(User $user, Domain $domain)
    {
        //
    }*/

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    /*public function forceDelete(User $user, Domain $domain)
    {
        //
    }*/
}
