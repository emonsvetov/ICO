<?php

namespace App\Policies;

use App\Models\PositionPermissionAssignment;
use App\Models\User;
use App\Models\Organization;
use App\Models\Program;
use App\Models\PositionLevel;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionPermissionAssignmentPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program,$positionLevel= null): bool
    {
        dd('dfgfdg');
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id ) return false;
       // if( $positionLevel && $positionLevel->program_id != $program->id ) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionPermissionAssignment  $positionPermissionAssignment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Organization $organization, Program $program, PositionPermissionAssignment $positionPermissionAssignment)
    {
        if(!$this->__preAuthCheck($user, $organization, $program, $positionPermissionAssignment)) return false;
        if($user->isAdmin()) return true;
        return $user->can('position-permission-assignment-view');
    }

    public function assign(User $authUser, Organization $organization, Program $program, PositionLevel $positionlevel)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program,$positionlevel)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('position-permission-assignment-assign');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user ,Organization $organization, Program $program, PositionLevel $PositionLevel)
    {
        if(!$this->__preAuthCheck($user, $organization, $program,$PositionLevel)) return false;
        if($user->isAdmin()) return true;
        return $user->can('position-permission-assignment-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionPermissionAssignment  $positionPermissionAssignment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, PositionPermissionAssignment $positionPermissionAssignment)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionPermissionAssignment  $positionPermissionAssignment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, PositionPermissionAssignment $positionPermissionAssignment)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionPermissionAssignment  $positionPermissionAssignment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, PositionPermissionAssignment $positionPermissionAssignment)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PositionPermissionAssignment  $positionPermissionAssignment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, PositionPermissionAssignment $positionPermissionAssignment)
    {
        //
    }

}
