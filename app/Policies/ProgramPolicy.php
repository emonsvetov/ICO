<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('program-list');
    }

    public function view(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-view');
    }

    public function create(User $user)
    {
        return $user->can('program-create');
    }

    public function update(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-update');
    }

    public function delete(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-delete');
    }

    public function move(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-move');
    }

    public function viewAnyMerchant(User $user, Program $program)
    {
        // !!Program $program ; Probably more checks to determine whether a user can use a merchant's submerchants?
        return $user->can('program-merchant-list');
    }
  
    public function addMerchant(User $user, Program $program)
    {
        // !!Program $program...
        return $user->can('program-merchant-add');
    }

    public function removeMerchant(User $user, Program $program, Merchant $merchant)
    {
        // !!Program $program... !!Merchant $merchant
        return $user->can('program-merchant-remove');
    }

    public function viewAnyUser(Program $program)
    {
        return $user->can('program-user-list');
    }

    public function addUser(Program $program)
    {
        return $user->can('program-user-add');
    }

    public function removeUser(Program $program, User $user)
    {
        if( $user->organization_id != $prgram->organization_id) return false;
        return $user->can('program-user-remove');
    }
}
