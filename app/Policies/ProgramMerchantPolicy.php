<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramMerchantPolicy
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
        return $user->can('program-merchant-list');
    }
  
    public function add(User $user, Program $program)
    {
        // !!Program $program...
        return $user->can('program-merchant-add');
    }

    public function remove(User $user, Program $program, Merchant $merchant)
    {
        // !!Program $program... !!Merchant $merchant
        return $user->can('program-merchant-remove');
    }
}
