<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramUserPolicy
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
        return $user->can('program-user-list');
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
}
