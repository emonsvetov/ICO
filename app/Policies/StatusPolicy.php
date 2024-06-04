<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatusPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization)
    {
        if ( !$this->__preAuthCheck($user, $organization) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->can('status-list');
    }
}
