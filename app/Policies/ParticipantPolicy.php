<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ParticipantPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program = null, $user = null)   {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $program && $program->organization_id != $organization->id) return false;
        if( $user && !$organization->hasUser($user)) return false;
        return true;
    }

    public function readPoints(User $authUser, Organization $organization, Program $program, User $user)
    {
        if( !$this->__preAuthCheck($authUser, $organization, $program, $user) ) return false;
        if( $authUser->isAdmin() ) return true;
        if( $authUser->isParticipantToProgram( $program ) && ($authUser->id == $user->id ) ) return true;
        return $authUser->can('user-read-points');
    }

    public function markNotificationRead(User $authUser, Organization $organization, Program $program, User $user)
    {
        if( !$this->__preAuthCheck($authUser, $organization, $program, $user) ) return false;
        if( $authUser->isAdmin() ) return true;
        if( $authUser->isManagerToProgram( $program ) ) return true;
        if( $authUser->isParticipantToProgram( $program ) && ($authUser->id == $user->id ) ) return true;
        return $authUser->can('mark-notifications-read');
    }
}
