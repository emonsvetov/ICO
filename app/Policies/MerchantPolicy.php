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

    public function viewSubs(User $user, Merchant $merchant)
    {
        // !!Merchant $merchant ; Probably more checks to determine whether a user can use a merchant's submerchants?
        return $user->permissions()->contains('view-submerchants');
    }
}
