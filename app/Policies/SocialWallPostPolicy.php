<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Program;
use App\Models\SocialWallPost;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class SocialWallPostPolicy
{
    use HandlesAuthorization;

    private function __preAuthCheck($authUser, $organization, $program, $socialWallPost = null): bool
    {
        if( !$authUser->belongsToOrg($organization) ) return false;
        if( $organization->id != $program->organization_id ) return false;
        if( $socialWallPost && $organization->id != $socialWallPost->organization_id ) return false;
        return true;
    }

    public function before(User $authUser, $ability)
    {
        // return true; //allowed until we have roles + permissions
    }
    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('program-social-wall-post-list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function view(User $authUser, Organization $organization, Program $program, SocialWallPost $socialWallPost)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $socialWallPost)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('program-social-wall-post-view');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function create(User $authUser, Organization $organization, Program $program)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('program-social-wall-post-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function update(User $authUser, Organization $organization, Program $program, SocialWallPost $socialWallPost)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $socialWallPost)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('program-social-wall-post-update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function delete(User $authUser, Organization $organization, Program $program, SocialWallPost $socialWallPost)
    {
        if(!$this->__preAuthCheck($authUser, $organization, $program, $socialWallPost)) return false;
        if($authUser->isAdmin()) return true;
        if($authUser->isManagerToProgram($program)) return true;
        return $authUser->can('program-social-wall-post-delete');
    }
}
