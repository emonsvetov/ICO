<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramParticipantPolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if( $user->organization_id !== $organization->id ) return false;
        if( $program->organization_id !== $organization->id ) return false;
        return true;
    }

    public function changeStatus(User $user, Organization $organization, Program $program)
    {
        if( $user->organization_id !== $organization->id ) return false;
        if( $program->organization_id !== $organization->id ) return false;
        if( $user->isAdmin() ) return true;
        return $user->isManagerToProgram( $program ) || $user->can('program-participant-change-status');
    }
}
