<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class GoalPlanPolicy
{
    use HandlesAuthorization;
    private function __authCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
	 public function create(User $user, Organization $organization, Program $program)
    {
       // return true;
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        
        if($user->isAdmin()) return true;

        return $user->isManagerToProgram( $program ) || $user->can('goal-plan-create');
    }
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        // return true;
        if ( !$this->__authCheck($user, $organization, $program ) )
        {
            return false;
        }
        
        if($user->isAdmin()) return true;

        return $user->isManagerToProgram( $program ) || $user->can('goal-plan-list');
        //This action to get EventTypes needs to be public?!
    }
}

/*'goal-plan-list',
'goal-plan-view',
'goal-plan-update',
'goal-plan-delete',*/