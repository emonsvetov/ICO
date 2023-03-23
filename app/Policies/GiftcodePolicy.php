<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Merchant;
use App\Models\User;

class GiftcodePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->can('giftcode-list');
    }

    public function purchaseFromV2(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->can('giftcode-purchase-from-v2');
    }
}
