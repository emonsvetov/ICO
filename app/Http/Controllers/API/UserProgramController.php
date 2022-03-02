<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserProgramRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Program;
use App\Models\User;
Use Exception;
// use DB;

class UserProgramController extends Controller
{
    public function index( Organization $organization, User $user )
    {
        if ( $organization->id != $user->organization_id )
        {
            return response(['errors' => 'Invalid Organization or User'], 422);
        }

        if( !$user->programs->isNotEmpty() ) return response( [] );

        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $programIds = [];
        $where = [];

        foreach($user->programs as $program)    {
            $programIds[] = $program->id;
        }

        if( $sortby == "name" )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Program::whereIn('id', $programIds)
        ->where($where);

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);
        
        if ( request()->has('minimal') )
        {
            $programs = $query->select('id', 'name')->get();
        }
        else 
        {
            $programs = $query->get();
        }

        if ( $programs->isNotEmpty() ) 
        { 
            return response( $programs );
        }

        return response( [] );
    }

    public function store( UserProgramRequest $request, Organization $organization, User $user )
    {
        if ( $organization->id != $user->organization_id )
        {
            return response(['errors' => 'Invalid Organization or User'], 422);
        }

        $validated = $request->validated();
        $program_id = $validated['program_id'];

        $columns = []; //any additional columns set here
        
        try {
            $user->programs()->sync( [ $validated['program_id'] => $columns ], false);

            //Add program specific permissions to user
            $roles = $validated['roles'];
            $permissions = [];
            foreach( $roles as $roleId)    {
                $permisssionName = "program.{$program_id}.role.{$roleId}";
                $permission = Permission::firstOrCreate(['name' => $permisssionName, 'organization_id' => $organization->id]);
                if( $permission )   {
                    array_push($permissions, $permission->id);
                }
            }

            if( $permissions )  {
                return $user->syncPermissionsByProgram($program_id, $permissions);
            }

        } catch( Exception $e) {
            return response(['errors' => 'Program adding failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }

    public function delete(Organization $organization, User $user, Program $program )
    {
        if ( $organization->id != $user->organization_id || $user->organization_id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or User or Program'], 422);
        }

        try{
            $user->programs()->detach( $program );
            $permissions = Permission::where('name', 'LIKE', "program.{$program->id}.role.%")->get()->pluck('name');
            foreach( $permissions as $permission )  {
                $user->revokePermissionTo( $permission );
            }
        }   catch( Exception $e) {
            return response(['errors' => 'Program removal failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }

    public function getPermission(Organization $organization, User $user, Program $program )
    {
        if ( $organization->id != $user->organization_id || $user->organization_id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or User or Program'], 422);
        }
        return Permission::select('permissions.*')
        ->join('model_has_permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
        ->where('name', 'LIKE', "program.{$program->id}.role.%")
        ->where('model_has_permissions.model_type', '=', 'App\\Models\\User')
        ->where('model_has_permissions.model_id', '=', $user->id)
        ->get()->pluck('name');
    }
}
