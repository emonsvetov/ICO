<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\ProgramLoginRequest;
use App\Models\Organization;
use App\Models\Program;

class ProgramLoginController extends Controller
{
    public function login(ProgramLoginRequest $request, Organization $organization, Program $program)
    {
        $requestRole = $request->validated()['role'];
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
