<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExternalCallback;
use App\Models\Organization;
use App\Models\Program;

class ExternalCallbackController extends Controller
{
    public function getGoalMetProgramCallbacks(Organization $organization, Program $program) //Achieved
    {
        return response(ExternalCallback::getIndexData($organization,$program, ['type'=>ExternalCallback::CALLBACK_TYPE_GOAL_MET]));
    }
    public function getGoalExceededProgramCallbacks(Organization $organization, Program $program)
    {
        return response(ExternalCallback::getIndexData($organization,$program, ['type'=>ExternalCallback::CALLBACK_TYPE_GOAL_EXCEEDED]));
    }
}
