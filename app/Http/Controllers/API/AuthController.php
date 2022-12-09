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
        try {

            $validated = $request->validated();
            if (!auth()->guard('web')->attempt( ['email' => $validated['email'], 'password' => $validated['password']] )) {
                return response(['message' => 'Invalid Credentials'], 422);
            }
    
            $user = auth()->guard('web')->user();
            $user->load(['organization', 'roles']);
    
            $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;
    
            $response = ['user' => $user, 'access_token' => $accessToken];

            $isValidDomain = $domainService->isValidDomain();

            if( $isValidDomain )
            {
                $domain = $domainService->getDomain();
                $user->programRoles = $user->getCompiledProgramRoles(null, $domain );
                if( !$user->programRoles )  {
                    return response(['message' => 'No program roles '], 422);
                }
                $response['domain'] = $domain;
                return response( $response );
            }
    
            if( ($user->isSuperAdmin() || $user->isAdmin()) )
            {
                return response($response);
            }

            return response(['errors' => 'Login request failed', 'e' => 'Unknow error: Invalid domain or user'], 422);
        }
        catch(\Exception $e)
        {
            return response(['errors' => 'Login request failed', 'e' => $e->getMessage()], 422);
        }
        
    }

    public function logout (Request $request) {
       
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
