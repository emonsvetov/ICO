<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\ProgramTemplate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProgramMediaTypePolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, $programTemplate = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        if($programTemplate && $programTemplate->program_id != $program->id) return false;
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
        if( !$this->__preAuthCheck($user,$organization, $program) ) return false;
        return true;
    }
}
