<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use DB;

class ProgramParticipantController extends Controller
{
    public function index( Organization $organization, Program $program )
    {
        $users = User::getParticipants($program, true);
        if($users  )    {
            return response( $users );
        }
        return response( [] );
    }
}
