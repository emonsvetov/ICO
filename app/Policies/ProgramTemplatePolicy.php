<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\ProgramTemplate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ProgramTemplatePolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, $programTemplate = null): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        if($programTemplate && $programTemplate->program_id != $program->id) return false;
        return true;
    }

    public function create(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('program-template-create');
    }

    public function update(User $authUser, Organization $organization, Program $program, ProgramTemplate $programTemplate)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $programTemplate)) return false;
        if($authUser->isAdmin()) return true;
        return $authUser->can('program-template-update');
    }

    public function view(User $user, Organization $organization, Program $program, ProgramTemplate $programTemplate)
    {
        if( !$this->__preAuthCheck($user, $organization, $program, $programTemplate) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-template-view');
    }

    public function delete(User $user, Organization $organization, Program $program, ProgramTemplate $programTemplate)
    {
        if( !$this->__preAuthCheck($user, $organization, $program, $programTemplate) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-template-delete');
    }

    public function getTemplate(User $user, Organization $organization, Program $program)
    {
        if( !$this->__preAuthCheck($user, $organization, $program) ) return false;
        if( $user->isAdmin() ) return true;
        return $user->can('program-template-view');
    }
}
