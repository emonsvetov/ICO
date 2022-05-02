<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramMerchantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if ( $organization->id != $user->organization_id || $organization->id != $program->organization_id )
        {
            return false;
        }
        return $user->isManagerToProgram($program) || $user->isParticipantToProgram($program) || $user->can('program-merchant-list');
    }
  
    public function add(User $user, Organization $organization, Program $program)
    {
        if ( $organization->id != $user->organization_id || $organization->id != $program->organization_id )
        {
            return false;
        }
        return $user->isManagerToProgram($program) || $user->isParticipantToProgram($program) || $user->can('program-merchant-add');
    }

    public function remove(User $user, Organization $organization, Program $program, Merchant $merchant)
    {
        if ( $organization->id != $user->organization_id || $organization->id != $program->organization_id )
        {
            return false;
        }
        return $user->isManagerToProgram($program) || $user->isParticipantToProgram($program) || $user->can('program-merchant-remove');
    }
}
