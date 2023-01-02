<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if($organization->id != $program->organization_id) return false;
        return true;
    }

    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__preAuthCheck($user, $organization, $program ) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program ) ) return true;
        return $user->can('emailtemplate-list');
    }
}
