<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
//use App\Models\EmailTemplate;
//use App\Models\User;
//use App\Models\Role;
use DB;


class GoalPlanController extends Controller
{
    public function store(GoalPlanRequest $request, Organization $organization, Program $program)
    {
        if ( !( $organization->id == $program->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
        $validated = $request->validated();
        $new_goal_plan = GoalPlan::create(  $validated +
        [
            'organization_id' => $organization->id,
            'program_id' => $program->id
        ] );

        if ( !$new_goal_plan )
        {
        return response(['errors' => 'Goal plan Creation failed'], 422);
        }
        // unset($validated['custom_email_template']);
        return response([ 'new_goal_plan' => $new_goal_plan ]);
       
	}
}