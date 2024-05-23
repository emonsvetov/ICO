<?php

namespace App\Policies;

use App\Models\PositionLevel; 
use App\Models\User;
use App\Models\Organization;
use App\Models\Program;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionLevelPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, PositionLevel $positionLevel = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id ) return false;
        if( $positionLevel && $positionLevel->program_id != $program->id ) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('position-level-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $authUser
     * @param  \App\Models\PositionLevel  $positionLevel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $authUser, Organization $organization, Program $program,PositionLevel $positionLevel)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $positionLevel)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('position-level-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $authUser
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $authUser ,Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('position-level-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $authUser
     * @param  \App\Models\PositionLevel  $levelNumber
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $authUser, Organization $organization, Program $program,PositionLevel $positionLevel)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $positionLevel)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('position-level-update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $authUser
     * @param  \App\Models\PositionLevel  $positionLevel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $authUser, Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $positionLevel)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('position-level-delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionLevel  $positionLevel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, PositionLevel $positionLevel)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionLevel  $positionLevel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, PositionLevel $positionLevel)
    {
        //
    }
}
