<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Auth\Events\Registered;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
use App\Models\Organization;
use App\Models\User;

class AuthController extends Controller
{
    
    public function register(UserRegisterRequest $request, Organization $organization)
    {
        if ( ! $organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $registerFields = $request->validated();
        $registerFields['password'] = bcrypt($request->password);
        $registerFields['organization_id'] = $organization->id;
        $user = User::create( $registerFields );

        if ( !$user )
        {
            return response(['errors' => 'User registration failed'], 422);
        }

        $accessToken = $user->createToken('authToken')->accessToken;

        event(new Registered($user));
        
        return response([ 'user' => $user, 'access_token' => $accessToken]);
    }


    public function login(UserLoginRequest $request)
    {
       
        if (!auth()->guard('web')->attempt( $request->validated() )) {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

        auth()->guard('web')->user()->roles->map->permissions->flatten()->pluck('name')->unique();

        return response(['user' => auth()->guard('web')->user(), 'access_token' => $accessToken]);

    }


    public function logout (Request $request) {
       
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
