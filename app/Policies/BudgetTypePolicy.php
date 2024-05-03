<?php

namespace App\Policies;

use App\Models\BudgetType;
use App\Models\User;
use App\Models\Program;
use App\Models\Organization;

use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetTypePolicy
{
    use HandlesAuthorization;
	
	private function __preAuthCheck($user, $organization, $program, PositionLevel $budgetType = null): bool
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
        if($authUser->isAdmin()) return true;
        return $authUser->can('budget-type-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetType  $budgetType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Organization $organization, Program $program,BudgetType $budgetType)
    {
        if(!$this->__preAuthCheck($user, $organization, $program, $budgetType)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('budget-type-view');
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
        return $user->can('budget-type-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetType  $budgetType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user,Organization $organization, Program $program, BudgetType $budgetType)
    {
        if(!$this->__preAuthCheck($user, $organization, $program, $budgetType)) return false;
        if($user->isAdmin()) return true;
        return $user->can('budget-type-update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetType  $budgetType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Organization $organization, Program $program,BudgetType $budgetType)
    {
       if(!$this->__preAuthCheck($user, $organization, $program, $budgetType)) return false;
       if($authUser->isAdmin()) return true;
       return $authUser->can('budget-type-delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetType  $budgetType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, BudgetType $budgetType)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\BudgetType  $budgetType
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, BudgetType $budgetType)
    {
        //
    }
}
