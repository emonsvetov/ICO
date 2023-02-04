<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Program;
use App\Models\Organization;


class ProgramEmailTemplatePolicy
{
    use HandlesAuthorization;
    private function __authCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('program-email-template-list');
    }
   
}
