<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class MerchantPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $merchant = null)   {
        if( !$authUser->belongsToOrg($organization) ) return false;
        return true;
    }

    public function viewAny(User $user, Organization $organization)
    {
        if( !$this->__preAuthCheck($user, $organization) ) return false;
        return true;
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
