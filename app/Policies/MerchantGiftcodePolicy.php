<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Merchant;
use App\Models\User;

class MerchantGiftcodePolicy
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
        return $user->can('merchant-giftcode-list');
    }
  
    public function add(User $authUser, User $user)
    {
        return $authUser->can('merchant-giftcode-add');
    }

    public function remove(User $authUser, Program $program, User $user)
    {
        return $authUser->can('merchant-giftcode-remove');
    }
}
