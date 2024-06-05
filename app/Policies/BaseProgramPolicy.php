<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class BaseProgramPolicy
{
    use HandlesAuthorization;

    protected function __preAuthCheck($authUser, $organization, $program = null)   {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $program && $program->organization_id != $organization->id) return false;
        return true;
    }
}
