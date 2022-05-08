<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class UserProgramPolicy
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
        return $user->can('user-program-list');
    }
  
    public function add(User $authUser, User $user)
    {
        return $authUser->can('user-program-add');
    }

    public function remove(User $authUser, Program $program, User $user)
    {
        return $authUser->can('user-program-remove');
    }

    public function getRoles(User $authUser, User $user,  Program $program)
    {
        return $authUser->can('user-program-roles');
    }
}
