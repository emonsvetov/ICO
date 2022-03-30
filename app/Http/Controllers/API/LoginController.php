<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\LoginRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class LoginController extends Controller
{
    public function login(LoginRequest $request, Organization $organization, Program $program, User $user)
    {
        $requestRole = $request->get( 'role' );
        $user = auth()->user();

        if( $requestRole == 'program_manager' )    {
            if( $user->isManagerToProgram($program->id) ) {
                return response([
                    'manager'=>true,
                    'program_id' => $program->id
                ]);
            }
        }

        if( $requestRole == 'participant' )    {
            if( $user->isParticipantToProgram($program->id) ) {
                return response([
                    'participant'=>true,
                    'program_id' => $program->id
                ]);
            }
        }
    
        return response([]);
    }
}
