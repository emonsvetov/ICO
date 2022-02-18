<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class DomainPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        return true; //allowed until we have roles + permissions
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->permissions()->contains('view-programs');
    }

    public function view(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->permissions()->contains('view-program');
    }

    public function create(User $user)
    {
        return $user->permissions()->contains('create-program');
    }

    public function update(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->permissions()->contains('update-program');
    }

    public function delete(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->permissions()->contains('delete-program');
    }

    public function viewAnyMerchant(User $user, Program $program)
    {
        // !!Program $program ; Probably more checks to determine whether a user can use a merchant's submerchants?
        return $user->permissions()->contains('view-program-merchants');
    }
  
    public function addMerchant(User $user, Program $program)
    {
        // !!Program $program...
        return $user->permissions()->contains('add-program-merchant');
    }

    public function removeMerchant(User $user, Program $program)
    {
        // !!Program $program...
        return $user->permissions()->contains('remove-program-merchant');
    }
}
