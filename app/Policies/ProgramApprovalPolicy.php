<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use App\Models\ProgramApproval;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramApprovalPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, ProgramApproval $programApproval = null): bool
    {
        if ($organization->id != $authUser->organization_id) return false;
        if ($organization->id != $program->organization_id) return false;
        if ($programApproval && $programApproval->program_id != $program->id) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if (!$this->__preAuthCheck($user, $organization, $program)) return false;
        if ($user->isAdmin()) return true;
        return $user->can('program-approval-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProgramApproval  $programApproval
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        if (!$this->__preAuthCheck($user, $organization, $program, $programApproval)) return false;
        if ($user->isAdmin()) return true;
        if ($user->isManagerToProgram($program)) return true;
        return $user->can('program-approval-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Organization $organization, Program $program)
    {
        if (!$this->__preAuthCheck($user, $organization, $program)) return false;
        if ($user->isAdmin()) return true;
        return $user->can('program-approval-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProgramApproval  $programApproval
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        if (!$this->__preAuthCheck($user, $organization, $program, $programApproval)) return false;
        if ($user->isAdmin()) return true;
        return $user->can('program-approval-update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProgramApproval  $programApproval
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        if (!$this->__preAuthCheck($user, $organization, $program, $programApproval)) return false;
        if ($user->isAdmin()) return true;
        return $user->can('program-approval-delete');
    }

    public function assign(User $user, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        if(!$this->__preAuthCheck($user, $organization, $program,$programApproval)) return false;
        if($user->isAdmin()) return true;
        return $user->can('program-approval-assign');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProgramApproval  $programApproval
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ProgramApproval $programApproval)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProgramApproval  $programApproval
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ProgramApproval $programApproval)
    {
        //
    }
}
