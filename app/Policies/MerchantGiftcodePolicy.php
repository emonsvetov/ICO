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
    public function viewAny(User $user, Merchant $merchant)
    {
        return $user->can('merchant-giftcode-list');
    }
  
    public function add(User $user, Organization $organization, Program $program, Merchant $merchant)
    {
        if( $user->organization_id !== $organization->id ) return false;
        if( $program->organization_id !== $organization->id ) return false;
        if( !$program->merchants->contains($merchant) ) return false;
        return $user->can('merchant-giftcode-add');
    }
}
