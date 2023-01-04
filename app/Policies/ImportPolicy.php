<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user pass basic check.
     *
     * @param  \App\Models\User $authUser
     * @param  \App\Models\Organization  $organization
     * @return mixed
     */
    private function __preAuthCheck(User $authUser, Organization $organization)   {
        if( $authUser->organization_id != $organization->id) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization)
    {
        if( !$this->__preAuthCheck($user,$organization) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('import-list');
    }
}
