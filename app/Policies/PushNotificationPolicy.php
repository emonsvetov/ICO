<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class PushNotificationPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program = null)   {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $program && $program->organization_id != $organization->id) return false;
        return true;
    }

    public function create(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram($program) ) return true;
        if( $user->isParticipantToProgram($program) ) return true;
        return $user->can('push-notification-create');
    }
}
