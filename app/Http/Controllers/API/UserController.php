<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Requests\UserRequest;
use App\Models\AccountHolder;
use App\Models\Organization;
use App\Models\User;
use DB;

class UserController extends Controller
{
    public function index( Organization $organization )
    {
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        
        $sortby = request()->get('sortby', 'id');
        $keyword = request()->get('keyword');
        $direction = request()->get('direction', 'asc');
        $limit = request()->get('limit', 10);

        $where[] = ['organization_id', $organization->id];

        // if( $keyword)
        // {
        //     $where[] = [ DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%" ];
            
        //     //more search criteria here
        // }
        $query = User::where($where);

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        if( $sortby == 'name' )
        {
            $orderByRaw = "first_name $direction, last_name $direction";
        }
        else
        {
            $orderByRaw = "$sortby $direction";
        }

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $users = $query->select('id', 'first_name', 'last_name')->with(['roles'])->get();
        } else {
            $users = $query->with(['roles'])->paginate(request()->get('limit', 10));
        }

        // return (DB::getQueryLog());
       
        if ( $users->isNotEmpty() ) 
        { 
            return response( $users );
        }

        return response( [] );
    }
    
    public function store(UserRequest $request, Organization $organization)
    {
        try {
            $validated = $request->validated();
            $validated['organization_id'] = $organization->id;
            $user = User::createAccount( $validated );
            return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
    }

    public function show( Organization $organization, User $user ): UserResource
    {
        return $this->UserResponse($user);
    }

    public function update(UserRequest $request, Organization $organization, User $user )
    {
        if ( ! ( $organization->id == $user->organization_id ) ) 
        { 
            return response(['errors' => 'No User Found'], 404);
        }

        $validated = $request->validated();
        $user->update( $validated );
        if( !empty($validated['roles']))   {
            $user->syncRoles( [$validated['roles']] );
        }
        return response([ 'user' => $user ]);
    }

    protected function userResponse(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }
}
