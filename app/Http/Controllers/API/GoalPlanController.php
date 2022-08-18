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
         // All goal plans use standard events except recognition goal
         $event_type_needed = 1;//standard
         //if Recognition Goal selected then set 
         if (isset($request->goal_plan_type_id) && ($request->goal_plan_type_id == 3)) {
            $event_type_needed = 5; // Badge event type;
         }
        // Get the appropriate events for this goal plan type - this is old site code - Pending
        //$events = $this->event_templates_model->readListByProgram((int) $this->program->account_holder_id, array(
        // $event_type_needed,
        // ), 0, 9999);\
        $validated = $request->validated();
        $new_goal_plan = GoalPlan::create(  $validated +
        [
            'organization_id' => $organization->id,
            'state_type_id'=>1, //not found in create function of old system
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
    public function index( Organization $organization, Program $program )
    {

        if ( $organization->id != $program->organization_id )
        {
            return response(['errors' => 'Invalid Organization or Program'], 422);
        }

        $goal_plans = GoalPlan::where('organization_id', $organization->id)
                        ->where('program_id', $program->id)
                        ->orderBy('name')
                        ->with(['goalPlanType'])
                        ->get();

        if ( $goal_plans->isNotEmpty() )
        {
            return response( $goal_plans );
        }

        return response( [] );
    }
}