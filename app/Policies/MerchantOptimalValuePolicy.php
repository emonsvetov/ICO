<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\OptimalValue;
use App\Models\Merchant;
use App\Models\User;

class MerchantOptimalValuePolicy
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
        return $user->can('merchant-optimalvalue-list');
    }
  
    public function add(User $user, Merchant $merchant)
    {
        return $user->can('merchant-optimalvalue-add');
    }

    public function update(User $user, Merchant $merchant, OptimalValue $optimalValue, )
    {
        if( $merchant->id != $optimalValue->merchant_id) return false;
        return $user->can('merchant-optimalvalue-edit');
    }

    public function delete(User $user, Merchant $merchant, OptimalValue $optimalValue, )
    {
        if( $merchant->id != $optimalValue->merchant_id) return false;
        return $user->can('merchant-optimalvalue-delete');
    }
}
