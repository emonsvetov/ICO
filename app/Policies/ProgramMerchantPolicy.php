<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Merchant;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramMerchantPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($user, $organization, $program = null, $merchant = null): bool
    {
        if( $organization->id != $user->organization_id ) return false;
        if( $program && $organization->id != $program->organization_id) return false;
        // if( $program && $merchant && !$program->merchants->contains( $merchant )) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__preAuthCheck($user, $organization, $program) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->isManagerToProgram($program) || $user->isParticipantToProgram($program) || $user->can('program-merchant-list');
    }

    public function view(User $user, Organization $organization, Program $program, Merchant $merchant)
    {
        if ( $organization->id != $user->organization_id || $organization->id != $program->organization_id )
        {
            return false;
        }
        return $user->canReadProgram($program, 'program-merchant-view');
    }

    public function viewGiftcodes(User $user, Organization $organization, Program $program, Merchant $merchant)
    {
        if ( $organization->id != $user->organization_id || $organization->id != $program->organization_id )
        {
            return false;
        }
        return $user->canReadProgram($program, 'program-merchant-view-giftcodes');
    }
    public function viewRedeemable(User $user, Organization $organization, Program $program, Merchant $merchant)
    {
        if ( !$this->__preAuthCheck($user, $organization, $program, $merchant) )
        {
            return false;
        }
        return true;
    }

    public function add(User $user, Organization $organization, Program $program)
    {
        if ( !$this->__preAuthCheck($user, $organization, $program) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->isManagerToProgram($program) || $user->isParticipantToProgram($program) || $user->can('program-merchant-add');
    }

    public function remove(User $user, Organization $organization, Program $program, Merchant $merchant)
    {
        if ( !$this->__preAuthCheck($user, $organization, $program, $merchant) )
        {
            return false;
        }
        if( $user->isAdmin() ) return true;
        return $user->isManagerToProgram($program) || $user->isParticipantToProgram($program) || $user->can('program-merchant-remove');
    }
}
