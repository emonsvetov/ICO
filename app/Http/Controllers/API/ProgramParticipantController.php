<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\Program;
Use Exception;
use DB;

class ProgramParticipantController extends Controller
{
    public function index( Organization $organization, Program $program )
    {
        return $program;
        return response( [] );
    }
}
