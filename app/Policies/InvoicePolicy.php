<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, $invoice = null): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $organization->id != $program->organization_id ) return false;
        if( $invoice && $program->id != $invoice->program_id ) return false;
        return true;
    }

    public function before(User $authUser, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }
    public function viewAny(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('invoice-list');
    }

    public function createOnDemand(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('invoice-create-on-demand');
    }

    public function view(User $user, Organization $organization, Program $program, Invoice $invoice)
    {
        if( !$this->__preAuthCheck($user, $organization, $program, $invoice) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('invoice-view');
    }

    public function pay(User $user, Organization $organization, Program $program, Invoice $invoice)
    {
        if( !$this->__preAuthCheck($user, $organization, $program, $invoice) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('invoice-pay');
    }

    public function download(User $user, Organization $organization, Program $program, Invoice $invoice)
    {
        if( !$this->__preAuthCheck($user, $organization, $program, $invoice) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('invoice-download');
    }
}
