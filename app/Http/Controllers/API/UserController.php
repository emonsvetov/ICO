<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\UserRequest;
use App\Models\Organization;
use App\Models\User;

class UserController extends Controller
{
    public function index( Organization $organization )
    {
        
        if ( $organization )
        {
            $users = User::where('organization_id', $organization->id)
                        ->get();
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
       

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
}
