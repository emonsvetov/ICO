<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\ProgramTemplate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramMediaPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }

    public function add(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user,$organization, $program) ) return false;
        if($user->isAdmin()) return true;
        return $user->isManagerToProgram($program) || $user->can('program-media-add');
    }

    public function create(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('program-media-create');
    }

    public function update(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('program-media-update');
    }

    public function view(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return true; //$user->can('program-media-view');
    }

    public function delete(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-media-delete');
    }

    public function getTemplate(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-media-view');
    }
}
