<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Program;
use App\Models\Domain;
use App\Models\User;

class DomainProgramPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        return true; //allowed until we have roles + permissions
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->permissions()->contains('view-domain-programs');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->permissions()->contains('add-domain-program');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function delete(User $user, Domain $domain, Program $program)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        if( $domain->organization_id !== $program->organization_id ) return false;
        return $user->permissions()->contains('delete-domain-program');
    }
}
