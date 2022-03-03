<?php

namespace App\Policies;

use App\Models\Merchant;
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
        return $user->can('merchant-edit');
    }

    public function delete(User $user, Merchant $merchant)
    {
        return $user->can('merchant-delete');
    }
}
