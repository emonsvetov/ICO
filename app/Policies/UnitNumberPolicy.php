<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\UnitNumber;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitNumberPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, UnitNumber $unitNumber = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id ) return false;
        if( $unitNumber && $unitNumber->program_id != $program->id ) return false;
        return true;
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\UnitNumber  $unitNumber
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('unit-number-list');
    }
    /**
     * Determine whether the user can create a model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function create(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('unit-number-create');
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\UnitNumber  $unitNumber
     * @return mixed
     */
    public function view(User $authUser, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $unitNumber)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('unit-number-view');
    }
    /**
     * Determine whether the user can update a model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\UnitNumber  $unitNumber
     * @return mixed
     */
    public function update(User $authUser, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $unitNumber)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('unit-number-update');
    }
    /**
     * Determine whether the user can delete a model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\UnitNumber  $unitNumber
     * @return mixed
     */
    public function delete(User $authUser, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $unitNumber)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('unit-number-delete');
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\UnitNumber  $unitNumber
     * @return mixed
     */
    public function assign(User $authUser, Organization $organization, Program $program, UnitNumber $unitNumber)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $unitNumber)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('unit-number-assign');
    }
}
