<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests\UserRegisterFastEezyRequest;
use App\Models\Organization;
use App\Services\DomainService;

class AuthFastEezyController extends Controller
{
    //Create and set is_fasteezy if want to use same database
    public function register(UserRegisterFastEezyRequest $request)
    {
        DB::beginTransaction();
        try{
            $registerFields = $request->validated();            
            
            //Get fasteezy organization
            $registerFields['organization_id'] = env('FASTEEZY_ORGANIZATION_ID');

            $user = User::createAccount( $registerFields );

            if ( !$user )
            {
                return response(['errors' => 'User registration failed'], 422);
            }

            $managerRole = Role::where('name', config('roles.manager'))->pluck('id');            
            $participantRole = Role::where('name', config('roles.participant'))->pluck('id');            

            $user->syncRoles( $adminRole );            
            $user->syncRoles( $participantRole );
            

            $accessToken = $user->createToken('authToken')->accessToken;

            event(new Registered($user));

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

            $this->updateMD5PasswordToBcrypt($validated);

            if (!auth()->guard('web')->attempt( ['email' => $validated['email'], 'password' => $validated['password']] )) {
                return response(['message' => 'Invalid Credentials'], 422);
            }

            $user = auth()->guard('web')->user();

            $status = User::getStatusByName(User::STATUS_ACTIVE );
            if( !in_array($user->user_status_id, [$status->id])){
                return response(['message' => 'Invalid Credentials*'], 422);
            }

            if (!isset($validated['code']) && $domainService->isAdminAppDomain()) {
                return response(['message' => 'Code is required'], 403);
            }

            $user->twofa_verified = false;

            $user->save();

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
                unset($domain->programs); //keep it private
                $response['domain'] = $domain;
                return response( $response );
            }
            else if( is_null($isValidDomain) )
            {
                if( ($user->isSuperAdmin() || $user->isAdmin()) )
                {
                    $response['programCount'] = $user->organization->programs()->count();
                    return response($response);
                }
            }

            throw new \Exception ('Unknown error: Invalid domain or user');
        }
        catch(\Exception $e)
        {
            return response(
                [
                    'message'=>'Login request failed',
                    'errors' => [
                        'loginError' => $e->getMessage()
                    ]
                ],
                422);
        }
    }

    public function updateMD5PasswordToBcrypt($credentials)
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';
        $userMD5Password = User::where([
            ['email', $email],
            ['password', md5($password)],
        ])->first();

        if (!empty($userMD5Password)) {
            DB::table('users')
                ->where([
                    ['email', $email],
                    ['password', md5($password)],
                ])
                ->update(['password' => Hash::make($password)]);
        }
    }

    public function logout (Request $request) {

        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
