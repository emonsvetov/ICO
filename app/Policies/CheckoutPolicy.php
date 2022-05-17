<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class CheckoutPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($user, $organization, $program): bool
    {
        if( $organization->id != $user->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
    
    public function checkout(User $user, Organization $organization, Program $program)
    {
        return true;
        // if ( !$this->__preAuthCheck($user, $organization, $program, $merchant) )
        // {
        //     return false;
        // }
        // return $user->isParticipantToProgram($program) || $user->can('checkout');
    }
}
