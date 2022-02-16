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
