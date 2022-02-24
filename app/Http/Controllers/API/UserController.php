<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\UserRequest;
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
            $users = $query->select('id', 'first_name', 'last_name')->get();
        }
        else {
            $users = $query->paginate(request()->get('limit', 10));
        }

        // return (DB::getQueryLog());
       
        if ( $users->isNotEmpty() ) 
        { 
            return response( $users );
        }

        return response( [] );
    }

    public function show( Organization $organization, User $user )
    {
        if ( $organization->id == $user->organization_id ) 
        { 
            return response( $user );
        }

        return response( [] );
    }

    public function update(UserRequest $request, Organization $organization, User $user )
    {
        if ( ! ( $organization->id == $user->organization_id ) ) 
        { 
            return response(['errors' => 'No User Found'], 404);
        }

        $user->update( $request->validated() );

        return response([ 'user' => $user ]);
    }

    public function store(UserRequest $request, User $user)
    {
        try {
            $fields = $request->except('role');
            $fields['password'] = bcrypt('123');
            $id = $user->insertGetId( $fields );
            return response([ 'id' => $id ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
    }
}
