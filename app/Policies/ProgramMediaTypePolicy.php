<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\ProgramTemplate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramMediaTypePolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program): bool
    {
        if( !$authUser->belongsToOrg( $organization ) ) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user,$organization, $program) ) return false;
        return true;
    }

    public function add(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user,$organization, $program) ) return false;
        if($user->isAdmin()) return true;
        return $user->isManagerToProgram($program) || $user->can('program-media-type-add');
    }
}
