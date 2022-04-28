<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class AwardPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }

    /**
     * Determine whether the manager can award.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, Organization $organization, Program $program)
    {
        if( $organization->id != $user->organization_id ) return false;
        if( $organization->id != $program->organization_id ) return false;
        return $user->isManagerToProgram( $program ) || $user->can('award-create');
    }
}
