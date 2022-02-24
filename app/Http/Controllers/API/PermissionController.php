<?php
    
namespace App\Http\Controllers\API;

use Spatie\Permission\Models\Permission;
use App\Http\Requests\PermissionRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Models\User;

use DB;
    
class PermissionController extends Controller
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

        $where = ['organization_id' => $organization->id];

        $query = Permission::where( $where );

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
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
            $permissions = $query->select('id', 'name')->get();
        } else {
            $permissions = $query->paginate(request()->get('limit', 20));
        }

        if ( $permissions->isNotEmpty() ) 
        {
            return response( $permissions );
        }

        return response( [] );
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PermissionRequest $request, Organization $organization)
    {
        if ( ! $organization->exists() ) 
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $permission = Permission::create(['name' => $request->input('name'), 'organization_id' => $organization->id]);

        if( $request->input('roles') )  {
            $permission->syncRoles( $request->input('roles') );
        }

        return response([ 'permission' => $permission ]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show( Organization $organization, Permission $permission )
    {
        if ( $organization->id != $permission->organization_id ) 
        {
            return response(['errors' => 'Invalid Organization or Permission'], 422);
        }

        $permission->roles;

        return response($permission);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PermissionRequest $request, Organization $organization, Permission $permission)
    {
        if ( $organization->id != $permission->organization_id ) 
        { 
            return response(['errors' => 'Invalid Organization or Permission'], 422);
        }

        $permission->name = $request->input('name');
        $permission->save();

        if( $request->input('roles') )  {
            $permission->syncRoles( $request->input('roles') );
        }
        
        return response([ 'permission' => $permission ]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Organization $organization, Permission $permission)
    {
        if ( $organization->id != $permission->organization_id ) 
        { 
            return response(['errors' => 'Invalid Organization or Permission'], 422);
        }

        $permission->delete();
        return response( ['deleted' => true] );
    }
}