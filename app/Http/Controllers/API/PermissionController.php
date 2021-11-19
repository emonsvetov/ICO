<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;

class PermissionController extends Controller
{
    public function index( Organization $organization )
    {
        $permissions = Permission::orderBy('name')->get();

        if ( $permissions->isNotEmpty() ) 
        { 
            return response( $permissions );
        }

        return response( [] );
    }

    public function assign( Organization $organization, Role $role, Permission $permission )
    {
        if ( ! ( $organization->id == $role->organization_id ) ) 
        { 
            return response(['errors' => 'No Role Found'], 404);
        }

        
        if (! $role->permissionNames()->contains( $permission->name ))
        {
            $role->allowTo($permission);
        }
        
        $role->refresh();

        $role->permissions;

        return response( $role );
    }

    public function revoke( Organization $organization, Role $role, Permission $permission )
    {
        if ( ! ( $organization->id == $role->organization_id ) ) 
        { 
            return response(['errors' => 'No Role Found'], 404);
        }

        $role->revoke($permission);
        
        $role->permissions;
        
        return response( $role );
    }


}
