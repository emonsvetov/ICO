<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Auth\Events\Registered;
use App\Events\OrganizationCreated;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
use App\Models\Organization;
use App\Models\User;
use App\Models\Role;

class AuthController extends Controller
{
    
    public function register(UserRegisterRequest $request)
    {

        $registerFields = $request->validated();
        $registerFields['password'] = bcrypt($request->password);
        if( !empty($registerFields['organization_name']) )  {
            $organization = Organization::create([
                'name' => $registerFields['organization_name']
            ]);
            event( new OrganizationCreated($organization) );
            $registerFields['organization_id'] = $organization->id;
            unset($registerFields['organization_name']);
        }
        
        $user = User::createAccount( $registerFields );

        if ( !$user )
        {
            return response(['errors' => 'User registration failed'], 422);
        }

        $adminRole = Role::where('name', config('roles.admin'))->pluck('id');
        $user->syncRoles( $adminRole );

        $accessToken = $user->createToken('authToken')->accessToken;

        event(new Registered($user));
        
        return response([ 'user' => $user, 'access_token' => $accessToken]);
    }

    public function login(UserLoginRequest $request)
    {
        if (!auth()->guard('web')->attempt( $request->validated() )) {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $user = auth()->guard('web')->user();
        $user->organization;
        $user->roles;
        $user->programRoles = $user->getProgramsRoles();

        $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

        return response(['user' => $user, 'access_token' => $accessToken]);

    }

    public function adminLogin(UserLoginRequest $request)
    {
        if (!auth()->guard('web')->attempt( $request->validated() )) {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $user = auth()->guard('web')->user();

        if( ! ( $user->hasRole(config('roles.super_admin')) || $user->hasRole(config('roles.admin')) ) )   {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $user->organization;

        $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

        return response(['user' => $user, 'access_token' => $accessToken]);

    }

    public function logout (Request $request) {
       
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
