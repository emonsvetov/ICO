<?php

namespace App\Policies;

use App\Models\CsvImportType;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user pass basic check.
     *
     * @param  \App\Models\User $authUser
     * @param  \App\Models\Organization  $organization
     * @return mixed
     */
    private function __preAuthCheck(User $authUser, Organization $organization, Program $program = null)   {
        if( $authUser->organization_id != $organization->id) return false;
        if( $program && $authUser->organization_id != $program->organization_id) return false;
        return true;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Organization  $organization
     * @return mixed
     */
    public function viewAny(User $user, Organization $organization)
    {
        if( !$this->__preAuthCheck($user, $organization) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('import-list');
    }

    public function downloadTemplate(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program ) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('import-download-template');
    }

    public function import(User $user, Organization $organization, Program $program, CsvImportType $csvImportType)
    {
        if( !$this->__preAuthCheck($user, $organization, $program ) ) return false;
        if( $user->isAdmin() ) return true;
        if( $user->isManagerToProgram( $program )) return true;
        return $user->can('import-csv-upload');
    }
}
