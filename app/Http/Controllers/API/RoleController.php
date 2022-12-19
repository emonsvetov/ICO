<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Organization $organization)
    {
        if ( ! $organization->exists() )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');
        $is_program_role = request()->get('is_program_role');
        $is_backend_role = request()->get('is_backend_role');
        $user = auth()->user();

        // $orWhere = ['organization_id' => $organization->id];
        $where = [];

        DB::enableQueryLog();

        $query = Role::where( $where );

        $query = $query->where(function($query1) use($organization) {
            $query1->orWhere('organization_id', $organization->id)
            ->orWhere('organization_id', null);
        });

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        if( !is_null($is_program_role) )  {
            $query->where('is_program_role', $is_program_role ? 1 : 0);
        }

        if( !is_null($is_backend_role) )  {
            $query->where('is_backend_role', $is_backend_role ? 1 : 0);
        }

        if( !$user->isSuperAdmin() ) {
            $query->where('name', '!=', config('roles.super_admin'));
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

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $roles = $query->select('id', 'name')->get();
        } else {
            $roles = $query->paginate(request()->get('limit', 20));
        }

        // dd(DB::getQueryLog());

        if ( $roles->isNotEmpty() )
        {
            return response( $roles );
        }

        return response( [] );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleRequest $request, Organization $organization)
    {
        if ( ! $organization->exists() )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $role = Role::create(['name' => $request->input('name'), 'organization_id' => $organization->id]);
        $role->syncPermissions($request->input('permissions'));

        return response([ 'role' => $role ]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show( Organization $organization, Role $role )
    {
        if ( $organization->id != $role->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Role'], 422);
        }

        $role->permissions;
        return response($role);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RoleRequest $request, Organization $organization, Role $role)
    {
        if ( $organization->id != $role->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Role'], 422);
        }

        // return $request->validated();

        $role->name = $request->input('name');
        $role->save();

        $role->syncPermissions($request->input('permissions'));

        return response([ 'role' => $role ]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organization $organization, Role $role)
    {
        if ( $organization->id != $role->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Role'], 422);
        }

        $role->delete();
        return response( ['deleted' => true] );
    }
}
