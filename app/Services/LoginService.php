<?php

namespace App\Services;

class LoginService
{
    public function mobileAppLogin( $validated )
    {
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
                        if (!auth()->guard('web')->attempt( ['email' => $validated['email'], 'password' => $validated['password']] )) {
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
                            $programId = null;
                            foreach($user->programRoles as $programRole) {
                                if( $programRole->name !== \App\Models\Role::ROLE_PARTICIPANT ) {
                                    //For now, we only support "Participant" login to MobileApps
                                    continue;
                                }
                                $programId = $programRole->pivot->program_id;
                            }
                            if( !$programId )   {
                                return response(['error' => 'Cannot login to program'], 422);
                            }

                            // $user->load(['organization']);
                            $accessToken = auth()->guard('web')->user()->createToken('authToken')->accessToken;

                            return response([
                                'program' => \App\Models\Program::select('id', 'name', 'organization_id')->with(['template' => function ($query) {
                                    // $query->select(['id', 'small_logo', 'big_logo', 'name']);
                                }])->find($programId),
                                'user' => $user,
                                'access_token' => $accessToken
                            ]);
                        }

                    } else {
                        return response([
                            'error' => 'Email/username invalid or not found'
                        ], 404);
                    }
                break;
                case 'createpassword':
                    $user = (new \App\Models\User)->getActiveOrNewUserByEmail( $validated['email'] );
                    if( $user->forcePasswordChange() ) {
                        $user->forceFill([
                            'password' => $validated['password']
                        ])->save();
                        return response([
                            'password_changed' => 'Password created successfully.'
                        ]);
                    }

                break;
                default:

                break;
            endswitch;
        }
    }
}
