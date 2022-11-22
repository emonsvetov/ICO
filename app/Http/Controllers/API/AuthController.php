<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Auth\Events\Registered;
use App\Events\OrganizationCreated;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
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
    
            event(new Registered($user));

            DB::commit();
            
            return response([ 'user' => $user, 'access_token' => $accessToken]);
        }
        catch(\Exception $e)
        {
            $response = ['errors' => $e->getMessage()];
            DB::rollBack();
            if(env('APP_DEBUG')) {
                $response['stackTrace'] =  $e->getTrace();
            }
            return response($response, 422);
        }
    }

    public function login(UserLoginRequest $request)
    {
        $validated = $request->validated();

        $host = '';
        // $host = 'incentco.local';
        $referer = request()->headers->get('referer');
        $domain = request()->get('domain'); // For testing via postman

        if( $referer )   {
            $urlVars = parse_url($referer);
            if( !isset($urlVars['host']) )  {
                return response(['errors' => 'Invalid Host'], 422);
            }
            $host = $urlVars['host'];
        }   else if($domain)   {
            $host = $domain;
        }

        // return $host;

        if( $host == 'localhost' || !$host)  {
            // $host = null; //This needs to be revisted. More checks can be implemented here
            return response(['errors' => 'Invalid host or host not allowed'], 422);
        }

        $domain = Domain::where('name', $host)->first();

        if( !$domain )  {
            return response(['message' => 'Domain not found for given host'], 422);
        }

        if (!auth()->guard('web')->attempt( ['email' => $validated['email'], 'password' => $validated['password']] )) {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $user = auth()->guard('web')->user();
        $user->load(['organization', 'roles']);
        // DB::enableQueryLog();
        $user->programRoles = $user->getProgramRolesByDomain( $domain );
        // return $programRoles;
        // $user->programRoles = $user->getProgramsRoles(null, $domain->id);
        // pr(DB::getQueryLog());
        // return $programRoles;
        if( !$user->programRoles )  {
            return response(['message' => 'Invalid domain or no program'], 422);
        }

        // return $user;

        $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

        return response(['user' => $user, 'access_token' => $accessToken, 'domain' => $domain]);

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
