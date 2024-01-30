<?php

namespace App\Services;

class LoginService
{
    public bool $isCheckRole = true;
    public bool $newPassword = false;
    public function mobileAppLogin( $validated )
    {
        // return $validated;
        if( !empty($validated['step']) )    {
            switch( $validated['step'] ):
                case 'email':
                    $user = (new \App\Models\User)->getActiveOrNewUserByEmail( $validated['email'] );
                    if( $user ) {
                        if( $user->forcePasswordChange() ) {
                            return response(['forcePasswordChange' => true]);
                        }
                        return response([
                            'id' => $user->id,
                            'email' => $user->email
                        ]);
                    } else {
                        return response([
                            'error' => 'Email/username invalid or not found'
                        ], 404);
                    }
                break;
                case 'password':
                    $userByEmail = (new \App\Models\User)->getActiveOrNewUserByEmail( $validated['email'] );
                    if( $userByEmail ) {
                        if( $userByEmail->forcePasswordChange() ) {
                            return response(['forcePasswordChange' => true]);
                        }
                        return $this->__login($validated['email'], $validated['password']);
                    } else {
                        return response([
                            'error' => 'Email/username invalid or not found'
                        ], 404);
                    }
                break;
                case 'createpassword':
                    $user = (new \App\Models\User)->getActiveOrNewUserByEmail( $validated['email'] );
                    if( !$user ) {
                        return response([
                            'error' => 'User not found for the email'
                        ], 404);
                    }   else {
                        $activeUserStatusId = (new \App\Models\User)->getIdStatusActive();
                        $data['password'] = $validated['password']; //!! hashed in model hook
                        if( $user->forcePasswordChange() ) {
                            $data['user_status_id'] = $activeUserStatusId;
                        }
                        $user->forceFill($data)->save();
                        $this->newPassword = true;
                        return $this->__login($user->email, $data['password']);
                    }
                break;
                case 'programLogin':
                    return $this->loginToProgram($validated['email'], $validated['password'], $validated['program_id']);
                break;
                default:

                break;
            endswitch;
        }
        return response([
            'error' => 'Invalid request'
        ], 404);
    }

    private function __login($email, $password)  {
        if (!auth()->guard('web')->attempt( ['email' => $email, 'password' => $password] )) {
            return response(['error' => 'Invalid Credentials'], 422);
        }
        $user = auth()->guard('web')->user();
        unset($user->roles);
        $domainService = resolve(\App\Services\DomainService::class);
        $isValidDomain = $domainService->isValidDomain();
        if( !$isValidDomain )   {
            //This login request does not have a "domain" attached so we may need to get a program or list of programs for the user
            $user->programRoles = $user->getProgramRoles();
            if( !$user->programRoles )  {
                return response(['error' => 'No role found in program'], 422);
            }
            $programIds = [];
            foreach($user->programRoles as $programRole) {
                if( $programRole->name !== \App\Models\Role::ROLE_PARTICIPANT ) {
                    //For now, we only support "Participant" login to MobileApps
                    continue;
                }
                // pr($programRole->toArray());
                array_push($programIds, $programRole->pivot->program_id);
            }
            if( !$programIds )   {
                return response(['error' => 'Cannot login as participant to program'], 422);
            }

            if(sizeof($programIds) > 1)   {
                $programs = (new \App\Models\Program)->whereIn('id', $programIds)->select(['id', 'name'])->get();
                // pr($programs->toArray());
                return response(['programs' => $programs]);
            }   else {
                $this->isCheckRole = false;
                return $this->loginToProgram($email, $password, $programIds[0]);
            }
        }
    }

    private function loginToProgram($email, $password, $programId)   {

        if (!auth()->guard('web')->attempt( ['email' => $email, 'password' => $password] )) {
            return response(['error' => 'Invalid Credentials'], 422);
        }
        $user = auth()->guard('web')->user();

        $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

        $program = \App\Models\Program::select('id', 'name', 'organization_id', 'factor_valuation')->with(['template' => function ($query) {
            // $query->select(['id', 'small_logo', 'big_logo', 'name']);
        }])->find($programId);

        if( !$program ) {
            return response(['error' => 'Invalid program'], 422);
        }

        if($this->isCheckRole && !$user->isParticipantToProgram($program)) {
            return response(['error' => 'No participant role found in program'], 422);
        }

        $amount_balance = $user->readAvailableBalance($program, $user);
        $factor_valuation = $program->factor_valuation;
        $points_balance = $amount_balance * $program->factor_valuation;

        $user->balance = $amount_balance;
        $user->points_balance = $points_balance;
        $user->factor_valuation = $factor_valuation;

        return response([
                'program' => $program,
                'user' => $user,
                'access_token' => $accessToken
            ] +
            ($this->newPassword ? ['password_changed' => true] : [])
        );
    }
}
