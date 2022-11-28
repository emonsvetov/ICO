<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Auth\Events\Registered;
use App\Events\OrganizationCreated;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
use App\Services\DomainService;
use App\Models\Organization;
use App\Models\Domain;
use App\Models\User;
use App\Models\Role;


class AuthController extends Controller
{
    
    public function register(UserRegisterRequest $request)
    {
        DB::beginTransaction();
        try{
            $registerFields = $request->validated();
            if( !empty($registerFields['organization_name']) )  {
                $organization = Organization::create([
                    'name' => $registerFields['organization_name']
                ]);
                OrganizationCreated::dispatch($organization);
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
    
            Registered:dispatch($user);

            DB::commit();
            
            return response([ 'user' => $user, 'access_token' => $accessToken]);
        }
        catch(\Exception $e)
        {
            $error = $e->getMessage();
            $response = ['errors' => $error];
            DB::rollBack();
            if(env('APP_ENV')=='local') {
                $response['stackTrace'] =  $e->getTrace();
            }
            return response($response, 422);
        }
    }

    public function login(UserLoginRequest $request, DomainService $domainService)
    {
        $validated = $request->validated();

        if (!auth()->guard('web')->attempt( ['email' => $validated['email'], 'password' => $validated['password']] )) {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $user = auth()->guard('web')->user();
        $user->load(['organization', 'roles']);

        $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

        $response = ['user' => $user, 'access_token' => $accessToken];

        if( $domainService->isAdminAppDomain()  && ($user->isSuperAdmin() || $user->isAdmin()) )
        {
            return response($response);
        }

        $domainName = $domainService->getRequestDomainName();
        $domain = $domainService->getDomainByName($domainName);
        
        $user->programRoles = $user->getProgramRolesByDomain( $domain );

        if( !$user->programRoles )  {
            return response(['message' => 'No program roles '], 422);
        }

        $response['domain'] = $domain;
        return response( $response );
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
