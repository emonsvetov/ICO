<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardLeadersPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $organization->id != $program->organization_id ) return false;
        return true;
    }

    public function before(User $authUser, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isParticipantToProgram($program)) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('program-leaderboard-leaders-list');
    }
}
