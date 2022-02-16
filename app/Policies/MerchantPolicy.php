<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class MerchantPolicy
{
    use HandlesAuthorization;

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

    public function before(User $user, $ability)
    {
        return true; //allowed until we have roles + permissions
    }

    public function viewAnySubmerchant(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant ; Probably more checks to determine whether a user can use a merchant's submerchants?
        return $user->permissions()->contains('view-submerchants');
    }
  
    public function createSubmerchant(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant...
        return $user->permissions()->contains('create-submerchants');
    }

    public function deleteSubmerchant(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant...
        return $user->permissions()->contains('delete-submerchants');
    }
}
