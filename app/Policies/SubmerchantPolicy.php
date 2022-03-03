<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class SubmerchantPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }

    public function viewAny(User $user, Merchant $merchant)
    {
        return true;
        return $user->can('submerchant-list');
    }
  
    public function add(User $user, Merchant $merchant)
    {
        return $user->can('submerchant-add');
    }

    public function remove(User $user, Merchant $merchant)
    {
        return $user->can('merchant-remove');
    }
}
