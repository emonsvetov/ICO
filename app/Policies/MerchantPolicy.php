<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class MerchantPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }
    public function viewAny(User $user)
    {
        return $user->can('merchant-list');
    }
  
    public function create(User $user)
    {
        return $user->can('merchant-create');
    }

    public function view(User $user, Merchant $merchant)
    {
        return $user->can('merchant-view');
    }

    public function update(User $user, Merchant $merchant)
    {
        return $user->can('merchant-update');
    }

    public function delete(User $user, Merchant $merchant)
    {
        return $user->can('merchant-delete');
    }

    public function viewAnySubmerchant(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant ; Probably more checks to determine whether a user can use a merchant's submerchants?
        return $user->can('view-submerchants');
    }
  
    public function createSubmerchant(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant...
        return $user->can('create-submerchants');
    }

    public function deleteSubmerchant(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant...
        return $user->can('delete-submerchants');
    }
}
