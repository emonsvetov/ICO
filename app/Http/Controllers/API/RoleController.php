<?php
    
namespace App\Http\Controllers\API;

use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;

use DB;
    
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        //  $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','store']]);
        //  $this->middleware('permission:role-create', ['only' => ['create','store']]);
        //  $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
        //  $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }
    
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

        // Permission::create(['name'=>'view-role-permission']);

        // $user = auth()->user();

        // $user->givePermissionTo('view-role-permission');

        // $role = Role::find(1);
        // $user = User::find(1);
        // $user->assignRole(1);
        // return $user->getAllPermissions();

        $where = [];

        $query = Role::where( $where );

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
            $roles = $query->select('id', 'name')->get();
        } else {
            $roles = $query->paginate(request()->get('limit', 20));
        }

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

        $role = Role::create(['name' => $request->input('name')]);
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
        if ( ! $organization->exists() || ! $role->exists() ) 
        {
            return response(['errors' => 'Invalid Organization or Role'], 422);
        }

        $role->permissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id", $role->id)
            ->get();

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
        if ( ! $organization->exists() || ! $role->exists() ) 
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
        if ( ! $organization->exists() || ! $role->exists() )
        { 
            return response(['errors' => 'Invalid Organization or Role'], 422);
        }

        $role->delete();
        return response( ['deleted' => true] );
    }
}