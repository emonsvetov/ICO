<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\ProgramLoginRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Role;

class ProgramLoginController extends Controller
{
    public function login(ProgramLoginRequest $request, Organization $organization, Program $program)
    {
        $requestRole = $request->validated()['role'];
        $user = auth()->user();

        if( $requestRole == 'manager' )    {
            if( $user->isManagerToProgram($program->id) ) {
                return response([
                    'role'=>Role::where('name', config('roles.manager'))->first(),
                    'program' => $program,
                ]);
            }
        }

        if( $requestRole == 'participant' )    {
            if( $user->isParticipantToProgram($program->id) ) {
                $program->getTemplate();
                return response([
                    'role'=>Role::where('name', config('roles.participant'))->first(),
                    'program' => $program
                ]);
            }
        }

        return response([]);
    }
}
