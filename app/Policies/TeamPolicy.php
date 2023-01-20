<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use App\Models\Team;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class TeamPolicy
{
    use HandlesAuthorization;

    private function __authCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
      /**
     * Determine whether the user can create the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
	 public function create(User $authUser, Organization $organization, Program $program)
    {
        
    //return true;
        //pr($authUser); die;
       // return true;
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('team-create');
    }
     /**
     * Determine whether the user can view all records of the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        // return true;
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('team-list');
    }
        /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\Team  $team
     * @return mixed
     */
    public function view(User $authUser, Organization $organization, Program $program)
    {
        // return true;
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('team-view');
    }
       /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\Team  $team
     * @return mixed
     */
    public function update(User $authUser, Organization $organization, Program $program,Team $team)
    {
        // return true;
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('team-update');
    }
     /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\Team  $team
     * @return mixed
     */
    public function delete(User $authUser, Organization $organization, Program $program,Team $team)
    {
       
        // return true;
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('team-delete');
    }
}