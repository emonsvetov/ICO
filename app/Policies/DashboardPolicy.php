<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program): bool
    {
        if ($organization->id != $authUser->organization_id) {
            return false;
        }
        if ($organization->id != $program->organization_id) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if ( ! $this->__preAuthCheck($user, $organization, $program)) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isManagerToProgram($program)) {
            return true;
        }
        return $user->can('dashboard-index');
    }
}
