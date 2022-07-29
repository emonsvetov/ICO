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
        //pr($request->all()); die;
        if ( !( $organization->id == $program->organization_id ) )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }
       // $request->date_begin = date("Y-m-d");
        // Default custom expire date to 1 year from today
        //$request->request->add(['date_end'=>date('Y-m-d', strtotime('+1 year'))]);
        //$request->goal_measurement_label = '$';
        $validated = $request->validated();
        $new_goal_plan = GoalPlan::create(  $validated +
        [
            'organization_id' => $organization->id,
            'state_type_id'=>1,
            'program_id' => $program->id, 
            'progress_notification_email_id'=>1, //pending
            'created_by'=>1, //pending
            'date_end'=>date('Y-m-d', strtotime('+1 year'))
        ] );
        
        if ( !$new_goal_plan )
        {
        return response(['errors' => 'Goal plan Creation failed'], 422);
        }
        // unset($validated['custom_email_template']);
        return response([ 'new_goal_plan' => $new_goal_plan ]);
       
	}
}