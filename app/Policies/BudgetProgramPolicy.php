<?php

namespace App\Policies;

use App\Models\BudgetProgram;
use App\Models\User;
use App\Models\Program;
use App\Models\Organization;

use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetProgramPolicy
{
    use HandlesAuthorization;
	
	private function __preAuthCheck($user, $organization, $program, BudgetProgram $budgetProgram = null): bool
    {
        if( $organization->id != $user->organization_id ) return false;
        if( $organization->id != $program->organization_id ) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user,Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($user, $organization, $program)) return false;
        if($user->isAdmin()) return true;
        if($user->isManagerToProgram($program)) return true;
        return $user->can('budget-program-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetProgram  $budgetProgram
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Organization $organization, Program $program,BudgetProgram $budgetProgram)
    {
        if(!$this->__preAuthCheck($user, $organization, $program, $budgetProgram)) return false;
        if($user->isAdmin()) return true;
        if($user->isManagerToProgram($program)) return true;
        return $user->can('budget-program-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user,Organization $organization, Program $program)
    {
       if(!$this->__preAuthCheck($user, $organization, $program)) return false;
        if($user->isAdmin()) return true;
        if($user->isManagerToProgram($program)) return true;
        return $user->can('budget-program-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetProgram  $budgetProgram
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user,Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        if(!$this->__preAuthCheck($user, $organization, $program, $budgetProgram)) return false;
        if($user->isAdmin()) return true;
        if($user->isManagerToProgram($program)) return true;
        return $user->can('budget-program-update');
    }

    public function updateCascadingApprovals(User $user,Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($user, $organization, $program)) return false;
        if($user->isAdmin()) return true;
        if($user->isManagerToProgram($program)) return true;
        return $user->can('budget-program-updateCascadingApprovals');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetProgram  $budgetProgram
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Organization $organization, Program $program,BudgetProgram $budgetProgram)
    {
       if(!$this->__preAuthCheck($user, $organization, $program, $budgetProgram)) return false;
       if($user->isAdmin()) return true;
       if($user->isManagerToProgram($program)) return true;
       return $user->can('budget-program-delete');
    }

    public function close(User $user, Organization $organization, Program $program,BudgetProgram $budgetProgram)
    {
       if(!$this->__preAuthCheck($user, $organization, $program, $budgetProgram)) return false;
       if($user->isAdmin()) return true;
       if($user->isManagerToProgram($program)) return true;
       return $user->can('budget-program-close');
    }

    public function assign(User $user, Organization $organization, Program $program,BudgetProgram $budgetProgram)
    { 
       if(!$this->__preAuthCheck($user, $organization, $program, $budgetProgram)) return false;
       if($user->isAdmin()) return true;
       if($user->isManagerToProgram($program)) return true;
       return $user->can('budget-program-assign');
    }


    public function revokeApproval(User $user, Organization $organization, Program $program)
    {
       if(!$this->__preAuthCheck($user, $organization, $program)) return false;
       if($user->isAdmin()) return true;
       if($user->isManagerToProgram($program)) return true;
       return $user->can('budget-program-revokeApproval');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetProgram  $budgetProgram
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function add(User $user,Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        if(!$this->__preAuthCheck($user, $organization, $program)) return false;
        if($user->isAdmin()) return true;
        if($user->isManagerToProgram($program)) return true;
        return $user->can('budget-program-upload');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetProgram  $budgetProgram
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, BudgetProgram $budgetProgram)
    {
        //
    }
}
