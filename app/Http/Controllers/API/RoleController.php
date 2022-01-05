<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\RoleRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;


class RoleController extends Controller
{
    public function index( Organization $organization )
    {
        $roles = Role::where('organization_id', $organization->id)                       
                     ->orderBy('name')
                     ->get();

        if ( $roles->isNotEmpty() ) 
        { 
            foreach ($roles as $role) 
            {
                $role->permissions;
            }
            
            return response( $roles );
        }

        return response( [] );
    }

    public function store(RoleRequest $request, Organization $organization)
    {
        if ( $organization )
        {
            $newRole = Role::create( 
                                    $request->validated() + 
                                    ['organization_id' => $organization->id] 
                                    );
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        

        if ( !$newRole )
        {
            return response(['errors' => 'Role Creation failed'], 422);
        }
        
        return response( $newRole );
    }

    public function show( Organization $organization, Role $role )
    {
        if ( $organization->id == $role->organization_id ) 
        { 
            $role->permissions;
            return response( ['role' => $role] );
        }

        return response( [] );
    }

    public function update(RoleRequest $request, Organization $organization, Role $role )
    {
        if ( ! ( $organization->id == $role->organization_id ) ) 
        { 
            return response(['errors' => 'No Role Found'], 404);
        }

        $role->update( $request->validated() );

        return response( $role );
    }

    public function destroy( Organization $organization, Role $role )
    {
        if ( ! ( $organization->id == $role->organization_id ) ) 
        { 
            return response(['errors' => 'No Role Found'], 404);
        }

        $role->delete();

        return response( ['deleted' => true] );
    }

    
    public function userRoleIndex( Organization $organization, User $user )
    {
        if ( ! ( $organization->id == $user->organization_id ) ) 
        { 
            return response(['errors' => 'Invalid'], 404);
        }
        
        $user->roles->map->permissions->flatten()->pluck('name')->unique();

        return response( $user->roles );
    }

    public function assign( Organization $organization, User $user, Role $role )
    {
        if ( ! ( $organization->id == $role->organization_id && $organization->id == $user->organization_id ) ) 
        { 
            return response(['errors' => 'Invalid Assignment'], 404);
        }

        
        if (! $user->roleNames()->contains( $role->name ))
        {
            $user->assignRole($role);
        }
        
        $user->refresh();
        $user->roles;

        return response( $user );
    }

    public function revoke( Organization $organization, User $user, Role $role )
    {
        if ( ! ( $organization->id == $role->organization_id && $organization->id == $user->organization_id ) ) 
        { 
            return response(['errors' => 'Invalid Revoke'], 404);
        }

        $user->revokeRole($role);
        
        $user->roles;
        
        return response( $user );
    }

}
