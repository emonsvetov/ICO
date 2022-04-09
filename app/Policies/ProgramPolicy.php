<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('program-list');
    }

    public function view(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        // if( $user->hasRole( config('global.program_manager_role_name') ))   {
        //     return true; //is global "Program Manager" role possible ?? If it is this check can be useful!
        // }
        if( $user->isManagerToProgram( $program->id ) )
        {
            return true;
        }
        return $user->can('program-view');
    }

    public function create(User $user)
    {
        return $user->can('program-create');
    }

    public function update(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-update');
    }

    public function delete(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-delete');
    }

    public function move(User $user, Program $program)
    {
        if( $user->organization_id !== $program->organization_id ) return false;
        return $user->can('program-move');
    }
}
