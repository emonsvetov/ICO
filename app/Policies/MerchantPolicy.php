<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MerchantPolicy
{
    use HandlesAuthorization;

    //public function before(User $user, $ability)
    //{
    //return true;
    //}

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->permissions()->contains('view-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Merchant $merchant)
    {
        return $user->permissions()->contains('view-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->permissions()->contains('create-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Merchant $merchant)
    {
        return $user->permissions()->contains('update-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Merchant $merchant)
    {
        return $user->permissions()->contains('delete-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Merchant $merchant)
    {
        return $user->permissions()->contains('create-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Merchant $merchant)
    {
        return $user->permissions()->contains('delete-merchant')
                    ? Response::allow()
                    : Response::deny('You do not own this merchant.');
    }
}
