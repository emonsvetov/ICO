<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use App\Models\UserGoal;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class UserGoalPolicy
{
    use HandlesAuthorization;
    private function __authCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
    
	 public function createUserGoalPlans(User $authUser, Organization $organization, Program $program)
    {
       // return true;
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('user-goal-create');
    }
    
}