<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardGoalPlanPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, $leaderboard): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id ) return false;
        if( $organization->id != $leaderboard->organization_id ) return false;
        return true;
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\Leaderboard  $leaderboard
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $leaderboard)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('leaderboard-goal-plan-list');
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\Leaderboard  $leaderboard
     * @return mixed
     */
    public function assign(User $authUser, Organization $organization, Program $program, Leaderboard $leaderboard)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $leaderboard)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('leaderboard-goal-plan-assign');
    }
}
