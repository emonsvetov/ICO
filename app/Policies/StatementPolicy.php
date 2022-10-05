<?php

namespace App\Policies;

use App\Policies\BaseProgramPolicy;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class StatementPolicy extends BaseProgramPolicy
{
    public function view(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('program-view');
    }
}