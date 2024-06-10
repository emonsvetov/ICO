<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class AwardPolicy
{
    use HandlesAuthorization;

    // public function before(User $user, $ability)
    // {
    //     // return true; //allowed until we have roles + permissions
    // }

    private function __authCheck($authUser, $organization, $program, $user = null): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }

    /**
     * Determine whether the manager can award.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user, Organization $organization, Program $program)
    {
        if( !$organization->hasUser($user) ) return false;
        if( $organization->id != $program->organization_id ) return false;
        return true;
    }

    public function readListReclaimablePeerPoints(User $authUser, Organization $organization, Program $program, User $user)
    {
        //return true;
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }

        if($authUser->isManagerToProgram( $program ) || $authUser->isParticipantToProgram( $program )) return true;
        return $user->can('award-read-reclaimable-peer-points');
    }

    public function reclaimPeerPoints(User $authUser, Organization $organization, Program $program, User $user)
    {
        if ( !$this->__authCheck($authUser, $organization, $program, $user ) )
        {
            return false;
        }

        if( $authUser->isManagerToProgram( $program ) ) return true;
        return $user->can('award-reclaim-peer-points');
    }
}
