<?php
namespace App\Models\Traits;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

trait HasOrganizationRoles
{
    public static $withoutAppends = false;
    private $orgRoles = null;
    public \App\Models\Organization $organization;
    // protected function getArrayableAppends()
    // {
    //     if( self::$withoutAppends ){
    //         return [];
    //     }
    //     $this->appends = array_unique(array_merge($this->appends, ['isAdmin']));
    //     return parent::getArrayableAppends();
    // }

    protected function getIsAdminAttribute()
    {
        $organization = request()->route('organization');
        if( $organization && $organization?->id ) {
            return $this->isAdminInOrganization($organization);
        }
        return false;
    }

    public function hasRolesInOrganization( $roles, $organization) {
        if( !$roles  || !$organization ) return false;
        $result = [];
        foreach( $roles as $roleName)   {
            if( $this->hasRoleInOrganization( $roleName, $organization) )    {
                array_push($result, $roleName);
            }
        }
        return $result;
    }
    public function hasAnyRoleInOrganization( $roles, $organization) {
        if( !$roles  || !$organization ) return false;
        foreach( $roles as $roleName)   {
            if( $this->hasRoleInOrganization( $roleName, $organization) )    {
                return true;
            }
        }
        return false;
    }

    public function hasRoleInOrganization( $roleName, $organization): bool
    {
        if( !$organization )   return false;
        $roles = $this->roles()
        ->where('roles.name', 'LIKE', $roleName )
        ->wherePivot( 'organization_id', '=', $organization->id)
        ->withPivot('organization_id')
        ->count();
        return $roles > 0 ? true : false;
    }

    public function isAdminInOrganization( $organization ): bool
    {
        return $this->hasRoleInOrganization(config('roles.admin'), $organization);
    }

    public function syncOrgAdminRoleByProgram(\App\Models\Program $program, bool $attach) {

        $query = $this->roles()
        ->where('roles.name', 'LIKE', config('roles.admin'))
        ->wherePivot( 'organization_id', '=', $program->organization_id )
        ->withPivot('organization_id');

        if( $query->first() ) {
            if( !$attach ) {
                $query->detach();
            }
        }   else {
            $newRoles = [];
            $columns = ['organization_id' => $program->organization_id];
            $roleId = \App\Models\Role::getIdByName(\App\Models\Role::ROLE_ADMIN);
            $newRoles[$roleId] = $columns;
            $this->roles()->attach($newRoles);
        }
    }

    public function isAdmin( $organization = null )
    {
        if( $organization ) {
            return $this->isAdminInOrganization( $organization );
        }
        return $this->hasAdminRole();
    }

    public function hasAdminRole() {
        $roleCount = $this->getAllOrgAdminRoles(true);
        return $roleCount > 0;
    }

    public function getAllOrgAdminRoles( $count = false ) {
        $query = $this->roles()
        ->where('roles.name', 'LIKE', config('roles.admin'))
        ->wherePivot( 'organization_id', '!=', NULL )
        ->withPivot('organization_id');
        if( $count ) return $query->count();
        return $query->get();
    }

    public function getOrganizationFilter() {
        if( $this->isAdmin() )    {
            $roles = $this->getAllOrgAdminRoles();
            if( $roles->isNotEmpty() ) {
                return $roles->pluck('pivot.organization_id');
            }
        }
    }

    public function setFirstOrganization() {
        $query = $this->roles()
        ->where('roles.name', 'LIKE', config('roles.admin'))
        ->wherePivot( 'organization_id', '!=', NULL )
        ->withPivot('organization_id');
        $first = $query->first();
        if( $first ) {
            // pr($first->toArray());
            $organization_id = $first->pivot->organization_id;
            $organization = Organization::find($organization_id);
            $this->setRelation('organization', $organization);
        }
    }
}
