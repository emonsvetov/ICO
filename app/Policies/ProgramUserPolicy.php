<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ProgramUserPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Program $program)
    {
        return $user->isManagerToProgram( $program ) || $user->can('program-user-list');
    }
  
    public function add(User $user, Program $program)
    {
        return $user->can('program-user-add');
    }

    public function update(User $user, Program $program)
    {
        return $user->can('program-user-update');
    }

    public function remove(User $authUser, Program $program, User $user)
    {
        return $authUser->can('program-user-remove');
    }

    public function readBalance(User $authUser, Organization $organization, Program $program, User $user)
    {
        if( $authUser->organization_id !== $organization->id ) return false;
        if( $program->organization_id !== $organization->id ) return false;
        if( $authUser->organization_id !== $user->organization_id ) return false;
        if($authUser->isManagerToProgram( $program ) || $authUser->isParticipantToProgram( $program )) return true;
        return $user->can('program-user-readbalance');
    }
}
