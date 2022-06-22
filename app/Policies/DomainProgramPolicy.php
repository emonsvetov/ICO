<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Domain;
use App\Models\User;

class DomainProgramPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }

    private function __preAuthCheck($authUser, $organization, $domain = null, $program = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if($domain && $organization->id != $domain->organization_id) return false;
        if($program && $organization->id != $program->organization_id) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization, Domain $domain)
    {
        if(!$this->__preAuthCheck($user, $organization, $domain)) return false;
        if($user->isAdmin()) return true;
        return $user->can('view-domain-programs');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function create(User $user, Organization $organization, Domain $domain)
    {
        if(!$this->__preAuthCheck($user, $organization, $domain)) return false;
        if($user->isAdmin()) return true;
        return $user->can('add-domain-program');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Domain  $domain
     * @param  \App\Models\Program  $program
     * 
     * @return mixed
     */
    public function delete(User $user, Organization $organization, Domain $domain, Program $program)
    {
        if(!$this->__preAuthCheck($user, $organization, $domain, $program)) return false;
        if($user->isAdmin()) return true;
        return $user->can('delete-domain-program');
    }
}
