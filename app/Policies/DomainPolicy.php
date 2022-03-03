<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class DomainPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->can('domain-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function view(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->can('domain-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function update(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-edit');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function delete(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-delete');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Domain  $domain
     * @return mixed
     */
    public function generateSecretKey(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-generate-secretkey');
    }

    public function addIp(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-add-ip');
    }

    public function deleteIp(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-delete-ip');
    }

    public function addProgram(User $user, Domain $domain)
    {
        return true;
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-add-program');
    }

    public function deleteProgram(User $user, Domain $domain)
    {
        if( $user->organization_id !== $domain->organization_id ) return false;
        return $user->can('domain-delete-program');
    }
}
