<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Models\User;

class ReportPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        return true; //allowed until we have roles + permissions
    }

    public function viewAny(User $user, Organization $organization)
    {
        if( $user->organization_id !== $organization->id ) return false;
        return $user->permissions()->contains('view-reports');
    }
}
