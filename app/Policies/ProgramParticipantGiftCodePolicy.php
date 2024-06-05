<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramParticipantGiftCodePolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if( !$user->belongsToOrg( $organization ) ) return false;
        if( $program->organization_id !== $organization->id ) return false;
        return true;
    }

}
